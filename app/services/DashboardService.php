<?php
// app/services/DashboardService.php
// Aggregated stats for the owner dashboard overview.

class DashboardService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function overview(int $tenantId): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ? AND status = 'active'");
        $stmt->execute([$tenantId]);
        $products = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS revenue
               FROM sales WHERE tenant_id = ? AND DATE(created_at) = CURDATE() AND status = 'completed'"
        );
        $stmt->execute([$tenantId]);
        $today = $stmt->fetch() ?: ['c' => 0, 'revenue' => 0];

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS revenue
               FROM sales WHERE tenant_id = ? AND status = 'completed'"
        );
        $stmt->execute([$tenantId]);
        $all = $stmt->fetch() ?: ['c' => 0, 'revenue' => 0];

        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT COALESCE(NULLIF(customer_phone,''), customer_email, customer_name))
               FROM sales
              WHERE tenant_id = ? AND status = 'completed'
                AND (COALESCE(customer_phone,'') != '' OR COALESCE(customer_email,'') != '' OR COALESCE(customer_name,'') != '')"
        );
        $stmt->execute([$tenantId]);
        $customers = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(customer_phone,'') != ''
                              OR COALESCE(customer_email,'') != ''
                              OR COALESCE(customer_name,'') != ''
                         THEN 1 ELSE 0 END) AS with_customer
               FROM sales
              WHERE tenant_id = ? AND status = 'completed'"
        );
        $stmt->execute([$tenantId]);
        $custRow = $stmt->fetch() ?: ['total' => 0, 'with_customer' => 0];
        $salesTotal = (int) ($custRow['total'] ?? 0);
        $salesWithCustomer = (int) ($custRow['with_customer'] ?? 0);
        $customerRate = $salesTotal > 0 ? round($salesWithCustomer / $salesTotal * 100) : 0;

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(buying_price * quantity),0) FROM products WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $stockValue = (float) $stmt->fetchColumn();

        return [
            'products'        => $products,
            'sales_today'     => (int) $today['c'],
            'revenue_today'   => round((float) $today['revenue'], 2),
            'sales_all'       => (int) $all['c'],
            'revenue_all'     => round((float) $all['revenue'], 2),
            'customers'       => $customers,
            'customer_rate'   => $customerRate,
            'sales_with_customer' => $salesWithCustomer,
            'profit_today'    => $this->estimateProfit($tenantId, 'today'),
            'profit_all'      => $this->estimateProfit($tenantId, 'all'),
            'retail_sales'    => $this->saleTypeCount($tenantId, 'retail'),
            'wholesale_sales' => $this->saleTypeCount($tenantId, 'wholesale'),
            'stock_value'     => round($stockValue, 2),
            'trend'           => $this->salesTrend($tenantId, 7),
            'payment_split'   => $this->paymentBreakdown($tenantId),
        ];
    }

    private function estimateProfit(int $tid, string $period): float
    {
        $dateSql = $period === 'today' ? 'AND DATE(s.created_at) = CURDATE()' : '';
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(si.line_total - (si.quantity * COALESCE(p.buying_price, 0))), 0)
               FROM sale_items si
               JOIN sales s ON s.id = si.sale_id
          LEFT JOIN products p ON p.id = si.product_id
              WHERE si.tenant_id = ? AND s.status = 'completed' {$dateSql}"
        );
        $stmt->execute([$tid]);
        return round((float) $stmt->fetchColumn(), 2);
    }

    private function saleTypeCount(int $tid, string $type): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM sales WHERE tenant_id = ? AND sale_type = ? AND status = 'completed'"
            );
            $stmt->execute([$tid, $type]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    private function salesTrend(int $tid, int $days): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(total),0) AS revenue
               FROM sales
              WHERE tenant_id = ? AND status = 'completed'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
           GROUP BY DATE(created_at)
           ORDER BY d ASC"
        );
        $stmt->execute([$tid, $days - 1]);
        return $stmt->fetchAll() ?: [];
    }

    private function paymentBreakdown(int $tid): array
    {
        $stmt = $this->db->prepare(
            "SELECT payment_method, COALESCE(SUM(total),0) AS total
               FROM sales WHERE tenant_id = ? AND status = 'completed'
           GROUP BY payment_method"
        );
        $stmt->execute([$tid]);
        $out = ['cash' => 0.0, 'mpesa' => 0.0, 'split' => 0.0];
        foreach ($stmt->fetchAll() as $r) {
            $key = $r['payment_method'] ?? 'cash';
            $out[$key] = round((float) $r['total'], 2);
        }
        return $out;
    }
}
