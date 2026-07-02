<?php
// public/super/settings/index.php — business name, logo, credentials
require_once __DIR__ . '/../../../app/app.php';
PageGuard::capability(Capabilities::SETTINGS_MANAGE);

$pdo = Database::pdo();
$tenantId = TenantContext::tenantId();
$tenantModel = new Models\TenantModel($pdo);
try {
    Models\TenantModel::ensureSettingsSchema($pdo);
} catch (\PDOException $e) {
    $_SESSION['flash']['error'] = 'Database update needed for Settings. Ask your host to run migration 024_tenant_business_credentials.sql.';
}
$base = Branding::PUBLIC_URL . '/super/settings/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'           => trim($_POST['name'] ?? ''),
        'phone'          => trim($_POST['phone'] ?? ''),
        'email'          => trim($_POST['email'] ?? ''),
        'website'        => trim($_POST['website'] ?? ''),
        'address'        => trim($_POST['address'] ?? ''),
        'location'       => trim($_POST['location'] ?? ''),
        'kra_pin'        => trim($_POST['kra_pin'] ?? ''),
        'currency'       => trim($_POST['currency'] ?? 'KES'),
        'receipt_footer' => trim($_POST['receipt_footer'] ?? ''),
    ];

    if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $logo = save_tenant_logo($_FILES['logo'], $tenantId);
        if ($logo['ok']) {
            $data['logo_path'] = $logo['path'];
        } else {
            $_SESSION['flash']['error'] = $logo['error'];
        }
    }

    if ($data['name'] === '') {
        $_SESSION['flash']['error'] = 'Business name is required.';
    } else {
        $tenantModel->updateSettings($tenantId, $data);
        if (empty($_SESSION['flash']['error'])) {
            $_SESSION['flash']['success'] = 'Business settings saved. Your logo now appears across the app.';
        }
    }
    header('Location: ' . $base);
    exit;
}

function save_tenant_logo(array $file, int $tenantId): array
{
    if ($file['error'] !== UPLOAD_ERR_OK)               return ['ok' => false, 'error' => 'Upload failed.'];
    if ($file['size'] > 2 * 1024 * 1024)                return ['ok' => false, 'error' => 'Logo must be under 2MB.'];
    $info = @getimagesize($file['tmp_name']);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']]))      return ['ok' => false, 'error' => 'Logo must be PNG, JPG or WEBP.'];

    $dir = ROOT_PATH . '/public/uploads/branding';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $name = 'tenant_' . $tenantId . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'error' => 'Could not save the logo.'];
    }
    return ['ok' => true, 'path' => Branding::PUBLIC_URL . '/uploads/branding/' . $name];
}

$__tenant = $tenantModel->find($tenantId);
$page_title = 'Settings';
$logoUrl = Branding::tenantLogo($__tenant);

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-1">Business settings</h2>
        <p class="text-muted small mb-4">Name and logo appear on your dashboard, staff screens, and login. KRA and location are stored here but not printed on receipts.</p>
        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label fw-semibold">Business name</label>
            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($__tenant['name'] ?? ''); ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($__tenant['phone'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($__tenant['email'] ?? ''); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Website</label>
              <input type="text" name="website" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($__tenant['website'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Currency</label>
              <input type="text" name="currency" class="form-control" maxlength="8" value="<?php echo htmlspecialchars($__tenant['currency'] ?? 'KES'); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Address</label>
            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($__tenant['address'] ?? ''); ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Location <span class="text-muted fw-normal">(area / town)</span></label>
              <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($__tenant['location'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">KRA PIN</label>
              <input type="text" name="kra_pin" class="form-control" value="<?php echo htmlspecialchars($__tenant['kra_pin'] ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Receipt footer</label>
            <input type="text" name="receipt_footer" class="form-control" placeholder="Thank you for shopping with us!"
                   value="<?php echo htmlspecialchars($__tenant['receipt_footer'] ?? ''); ?>">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Logo</label>
            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">Shown on login, sidebar, staff dashboard, and receipts. PNG/JPG/WEBP, under 2MB.</div>
          </div>
          <button class="btn btn-primary">Save settings</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body text-center p-4">
        <div class="text-muted small text-uppercase mb-2">Logo preview</div>
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" style="max-height:100px;max-width:100%;object-fit:contain;border-radius:8px;">
        <div class="fw-semibold mt-3"><?php echo htmlspecialchars($__tenant['name'] ?? ''); ?></div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/tenants/layout.php';
