<?php
// public/super/staff/index.php
require_once __DIR__ . '/../../../app/app.php';
PageGuard::auth();
require_once ROOT_PATH . '/app/services/emails/staff_invite_email.php';

$pdo = Database::pdo();
$tenantId = TenantContext::tenantId();
$bm = new Models\BranchModel($pdo);
$branches = $bm->all([], 'title ASC');
$svc = new StaffService($pdo);

$errors = [];
$old = ['email' => '', 'name' => '', 'branch_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $old = [
        'email'     => trim($_POST['email'] ?? ''),
        'name'      => trim($_POST['name'] ?? ''),
        'branch_id' => $_POST['branch_id'] ?? '',
    ];

    $appCfg  = is_file(ROOT_PATH . '/app/config/app.php') ? require ROOT_PATH . '/app/config/app.php' : [];
    $loginUrl = rtrim($appCfg['url'] ?? 'http://localhost/Kitale', '/') . '/public/auth/login.php';

    $notify = function (array $info) use ($loginUrl) {
        $msg = build_staff_invite_email($info['name'], $info['temp_password'], $loginUrl, $info['shop']);
        (new MailService())->send($info['email'], $msg['subject'], $msg['html']);
    };

    $res = $svc->create((int) $tenantId, [
        'email' => $old['email'], 'name' => $old['name'], 'branch_id' => (int) $old['branch_id'],
    ], $notify);

    if ($res['ok']) {
        $_SESSION['flash']['success'] = 'Staff account created — an invite with a temporary password was emailed to ' . $old['email'] . '.';
        header('Location: /Kitale/public/super/staff/');
        exit;
    }
    $errors = $res['errors'];
}

$staff = $svc->listForTenant((int) $tenantId);
$page_title = 'Staff';
ob_start();
?>
<?php if (!$branches): ?>
  <div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-4 text-center">
      <i class="fas fa-code-branch text-muted mb-2" style="font-size:1.6rem;"></i>
      <h2 class="h5">Create a branch first</h2>
      <p class="text-muted">Staff are assigned to a branch, so you'll need at least one branch before adding staff.</p>
      <a class="btn btn-primary" href="/Kitale/public/super/branches/">Go to Branches</a>
    </div>
  </div>
<?php else: ?>
<div class="row g-4">
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-1">Add staff</h2>
        <p class="text-muted small mb-3">We'll email them a temporary password. They'll set their own the first time they log in.</p>
        <?php if (!empty($errors['_'])): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($errors['_']); ?></div><?php endif; ?>
        <form method="post" novalidate>
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label">Branch</label>
            <select name="branch_id" class="form-select">
              <option value="">Choose a branch…</option>
              <?php foreach ($branches as $b): ?>
                <option value="<?php echo (int)$b['id']; ?>" <?php echo ((string)$old['branch_id']===(string)$b['id'])?'selected':''; ?>><?php echo htmlspecialchars($b['title']); ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['branch_id'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['branch_id']); ?></small><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Staff name <span class="text-muted">(optional)</span></label>
            <input name="name" class="form-control" placeholder="e.g. Alice Wanjiru" value="<?php echo htmlspecialchars($old['name']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" placeholder="staff@example.com" value="<?php echo htmlspecialchars($old['email']); ?>">
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?php echo htmlspecialchars($errors['email']); ?></small><?php endif; ?>
          </div>
          <button class="btn btn-primary">Create &amp; send invite</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">Your staff <span class="badge bg-light text-dark"><?php echo count($staff); ?></span></h2>
        <?php if (!$staff): ?>
          <div class="text-muted">No staff yet. Add your first team member on the left.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr class="text-muted small text-uppercase"><th>Name</th><th>Email</th><th>Branch</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($staff as $s): ?>
                <tr>
                  <td class="fw-semibold"><?php echo htmlspecialchars($s['username']); ?></td>
                  <td class="text-muted"><?php echo htmlspecialchars($s['email']); ?></td>
                  <td><?php echo htmlspecialchars($s['branch_title'] ?? '—'); ?></td>
                  <td>
                    <?php if ((int)$s['must_reset_password'] === 1): ?>
                      <span class="badge bg-warning text-dark">Pending first login</span>
                    <?php elseif ((int)$s['is_active'] === 1): ?>
                      <span class="badge bg-success">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Disabled</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif;
$content = ob_get_clean();
include __DIR__ . '/../../templates/tenants/layout.php';