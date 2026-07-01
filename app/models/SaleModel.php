<?php
// app/models/SaleModel.php
namespace Models;

class SaleModel extends Model
{
    protected string $table = 'sales';

    /**
     * Record a sale atomically: validate stock, snapshot prices from the DB,
     * write the sale + items, and decrement stock. All-or-nothing.
     *
     * @param array $in branch_id, staff_id, sale_type, payment_method,
     *                  cash_amount, mpesa_amount, amount_given, discount_amount,
     *                  customer_name/phone/email, items[[product_id,quantity]]
     */
    public function record(array $in): array
    {
        $tid = \TenantContext::tenantId();
        if ($tid === null) {
            return ['ok' => false, 'errors' => ['_' => 'No shop in context.']];
        }

        $saleType = in_array($in['sale_type'] ?? '', ['retail', 'wholesale'], true) ? $in['sale_type'] : 'retail';
        $method = in_array($in['payment_method'] ?? '', ['cash', 'mpesa', 'split'], true) ? $in['payment_method'] : null;
        if (!$method) {
            return ['ok' => false, 'errors' => ['payment_method' => 'Choose how the customer paid.']];
        }

        $items = array_values(array_filter($in['items'] ?? [], fn($i) => (int)($i['product_id'] ?? 0) > 0 && (float)($i['quantity'] ?? 0) > 0));
        if (!$items) {
            return ['ok' => false, 'errors' => ['_' => 'Add at least one product to the sale.']];
        }

        $discount = max(0, round((float) ($in['discount_amount'] ?? 0), 2));

        $db = $this->db;
        try {
            $db->beginTransaction();

            $priceCol = $saleType === 'wholesale' ? 'wholesale_price' : 'retail_price';
            $sel = $db->prepare(
                "SELECT id, name, selling_price, wholesale_price, retail_price, quantity, unit
                   FROM products WHERE id = ? AND tenant_id = ? AND status = 'active' FOR UPDATE"
            );
            $subtotal = 0.0;
            $lines = [];
            foreach ($items as $it) {
                $pid = (int) $it['product_id'];
                $qty = (float) $it['quantity'];
                $sel->execute([$pid, $tid]);
                $p = $sel->fetch();
                if (!$p) {
                    $db->rollBack();
                    return ['ok' => false, 'errors' => ['_' => 'One of the products is no longer available. Refresh and try again.']];
                }
                if ($qty > (float) $p['quantity']) {
                    $db->rollBack();
                    return ['ok' => false, 'errors' => ['_' => "Not enough stock for {$p['name']} — only " . rtrim(rtrim(number_format((float)$p['quantity'], 2), '0'), '.') . " left."]];
                }
                $unitPrice = (float) ($p[$priceCol] ?? 0);
                if ($unitPrice <= 0) {
                    $unitPrice = (float) ($p['selling_price'] ?? 0);
                }
                $lineTotal = round($unitPrice * $qty, 2);
                $subtotal += $lineTotal;
                $lines[] = [
                    'product_id'   => $pid,
                    'product_name' => $p['name'],
                    'unit'         => $p['unit'],
                    'unit_price'   => $unitPrice,
                    'price_type'   => $saleType,
                    'quantity'     => $qty,
                    'line_total'   => $lineTotal,
                ];
            }
            $subtotal = round($subtotal, 2);
            if ($discount > $subtotal) {
                $db->rollBack();
                return ['ok' => false, 'errors' => ['discount_amount' => 'Discount cannot exceed the subtotal.']];
            }
            $total = round($subtotal - $discount, 2);

            $cashAmount  = max(0, round((float) ($in['cash_amount'] ?? 0), 2));
            $mpesaAmount = max(0, round((float) ($in['mpesa_amount'] ?? 0), 2));
            $amountGiven = null;
            $change = null;

            if ($method === 'cash') {
                $cashAmount = $total;
                $mpesaAmount = 0.0;
                $amountGiven = (float) ($in['amount_given'] ?? 0);
                if ($amountGiven + 0.0001 < $total) {
                    $db->rollBack();
                    return ['ok' => false, 'errors' => ['amount_given' => 'Cash given is less than the total.']];
                }
                $change = round($amountGiven - $total, 2);
            } elseif ($method === 'mpesa') {
                $cashAmount = 0.0;
                $mpesaAmount = $total;
                $amountGiven = $total;
                $change = 0.0;
            } else {
                if (abs(($cashAmount + $mpesaAmount) - $total) > 0.01) {
                    $db->rollBack();
                    return ['ok' => false, 'errors' => ['_' => 'Cash and M-Pesa amounts must add up to the total (KES ' . number_format($total, 0) . ').']];
                }
                if ($cashAmount > 0) {
                    $amountGiven = (float) ($in['amount_given'] ?? $cashAmount);
                    if ($amountGiven + 0.0001 < $cashAmount) {
                        $db->rollBack();
                        return ['ok' => false, 'errors' => ['amount_given' => 'Cash given is less than the cash portion.']];
                    }
                    $change = round($amountGiven - $cashAmount, 2);
                } else {
                    $amountGiven = $mpesaAmount;
                    $change = 0.0;
                }
            }

            $ins = $db->prepare(
                "INSERT INTO sales (tenant_id, branch_id, staff_id, sale_type, receipt_number, payment_method,
                    total, subtotal, discount_amount, amount_given, change_given, cash_amount, mpesa_amount,
                    customer_name, customer_phone, customer_email, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'completed')"
            );
            $ins->execute([
                $tid,
                !empty($in['branch_id']) ? (int) $in['branch_id'] : null,
                (int) $in['staff_id'],
                $saleType,
                'PENDING',
                $method,
                $total,
                $subtotal,
                $discount,
                $amountGiven,
                $change,
                $cashAmount > 0 ? $cashAmount : null,
                $mpesaAmount > 0 ? $mpesaAmount : null,
                ($in['customer_name'] ?? '') !== '' ? trim($in['customer_name']) : null,
                ($in['customer_phone'] ?? '') !== '' ? trim($in['customer_phone']) : null,
                ($in['customer_email'] ?? '') !== '' ? trim($in['customer_email']) : null,
            ]);
            $saleId  = (int) $db->lastInsertId();
            $receipt = 'RCP-' . str_pad((string) $saleId, 6, '0', STR_PAD_LEFT);
            $db->prepare("UPDATE sales SET receipt_number = ? WHERE id = ?")->execute([$receipt, $saleId]);

            $insItem = $db->prepare(
                "INSERT INTO sale_items (tenant_id, sale_id, product_id, product_name, unit, unit_price, price_type, quantity, line_total)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $dec = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND tenant_id = ? AND quantity >= ?");
            foreach ($lines as $l) {
                $insItem->execute([
                    $tid, $saleId, $l['product_id'], $l['product_name'], $l['unit'],
                    $l['unit_price'], $l['price_type'], $l['quantity'], $l['line_total'],
                ]);
                $dec->execute([$l['quantity'], $l['product_id'], $tid, $l['quantity']]);
                if ($dec->rowCount() !== 1) {
                    $db->rollBack();
                    return ['ok' => false, 'errors' => ['_' => "Stock changed for {$l['product_name']} while saving. Please redo the sale."]];
                }
            }

            $db->commit();
            return ['ok' => true, 'sale_id' => $saleId, 'receipt_number' => $receipt, 'errors' => []];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            return ['ok' => false, 'errors' => ['_' => 'Could not complete the sale. Please try again.']];
        }
    }

    public function findScoped(int $id): ?array
    {
        return $this->find($id);
    }

    public function items(int $saleId): array
    {
        $tid = \TenantContext::tenantId();
        $stmt = $this->db->prepare("SELECT * FROM sale_items WHERE sale_id = ? AND tenant_id = ? ORDER BY id ASC");
        $stmt->execute([$saleId, $tid]);
        return $stmt->fetchAll();
    }

    public function forStaff(int $staffId, int $limit = 500, ?string $date = null): array
    {
        $tid = \TenantContext::tenantId();
        $dateSql = $date ? "AND DATE(s.created_at) = '" . preg_replace('/[^0-9-]/', '', $date) . "'" : '';
        $stmt = $this->db->prepare(
            "SELECT s.*, (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
               FROM sales s
              WHERE s.tenant_id = ? AND s.staff_id = ? {$dateSql}
           ORDER BY s.created_at DESC, s.id DESC
              LIMIT ?"
        );
        $stmt->bindValue(1, $tid, \PDO::PARAM_INT);
        $stmt->bindValue(2, $staffId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function forTenant(int $limit = 1000, string $period = 'all'): array
    {
        $tid = \TenantContext::tenantId();
        $periodSql = match ($period) {
            'today' => "AND DATE(s.created_at) = CURDATE()",
            'week'  => "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => '',
        };
        $stmt = $this->db->prepare(
            "SELECT s.*, u.username AS staff_name, b.title AS branch_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
               FROM sales s
          LEFT JOIN users u ON u.id = s.staff_id
          LEFT JOIN branches b ON b.id = s.branch_id
              WHERE s.tenant_id = ? {$periodSql}
           ORDER BY s.created_at DESC, s.id DESC
              LIMIT ?"
        );
        $stmt->bindValue(1, $tid, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function forTenantId(int $tenantId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.username AS staff_name, b.title AS branch_name
               FROM sales s
          LEFT JOIN users u ON u.id = s.staff_id
          LEFT JOIN branches b ON b.id = s.branch_id
              WHERE s.tenant_id = ? AND DATE(s.created_at) = ?
           ORDER BY s.created_at ASC"
        );
        $stmt->execute([$tenantId, $date]);
        return $stmt->fetchAll();
    }

    public static function staffBreakdown(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $name = $r['staff_name'] ?? 'Unknown';
            if (!isset($out[$name])) { $out[$name] = ['count' => 0, 'revenue' => 0.0]; }
            $out[$name]['count']++;
            $out[$name]['revenue'] += (float) $r['total'];
        }
        uasort($out, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return $out;
    }

    public static function branchBreakdown(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $name = $r['branch_name'] ?: 'No branch';
            if (!isset($out[$name])) { $out[$name] = ['count' => 0, 'revenue' => 0.0]; }
            $out[$name]['count']++;
            $out[$name]['revenue'] += (float) $r['total'];
        }
        uasort($out, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return $out;
    }

    /** Totals for a set of sales rows (revenue, count, by method, by sale type). */
    public static function summarize(array $rows): array
    {
        $sum = [
            'count' => 0, 'revenue' => 0.0, 'cash' => 0.0, 'mpesa' => 0.0,
            'retail' => 0, 'wholesale' => 0, 'discount' => 0.0,
        ];
        foreach ($rows as $r) {
            $sum['count']++;
            $sum['revenue'] += (float) $r['total'];
            $sum['discount'] += (float) ($r['discount_amount'] ?? 0);
            $stype = $r['sale_type'] ?? 'retail';
            $sum[$stype] = ($sum[$stype] ?? 0) + 1;

            $method = $r['payment_method'] ?? 'cash';
            if ($method === 'split') {
                $sum['cash'] += (float) ($r['cash_amount'] ?? 0);
                $sum['mpesa'] += (float) ($r['mpesa_amount'] ?? 0);
            } else {
                $sum[$method] = ($sum[$method] ?? 0) + (float) $r['total'];
            }
        }
        $sum['revenue'] = round($sum['revenue'], 2);
        $sum['discount'] = round($sum['discount'], 2);
        $sum['cash'] = round($sum['cash'], 2);
        $sum['mpesa'] = round($sum['mpesa'], 2);
        return $sum;
    }

    /** Human-readable payment summary for a sale row. */
    public static function paymentLabel(array $sale): string
    {
        $method = $sale['payment_method'] ?? 'cash';
        if ($method === 'split') {
            $parts = [];
            if ((float) ($sale['cash_amount'] ?? 0) > 0) {
                $parts[] = 'Cash KES ' . number_format((float) $sale['cash_amount'], 0);
            }
            if ((float) ($sale['mpesa_amount'] ?? 0) > 0) {
                $parts[] = 'M-Pesa KES ' . number_format((float) $sale['mpesa_amount'], 0);
            }
            return $parts ? implode(' + ', $parts) : 'Split';
        }
        return $method === 'cash' ? 'Cash' : 'M-Pesa';
    }

    /** Badge HTML for sale type. */
    public static function saleTypeBadge(array $sale): string
    {
        $t = $sale['sale_type'] ?? 'retail';
        if ($t === 'wholesale') {
            return '<span class="badge bg-info text-dark">Wholesale</span>';
        }
        return '<span class="badge bg-primary">Retail</span>';
    }
}
