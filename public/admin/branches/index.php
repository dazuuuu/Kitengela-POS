<?php
// public/admin/branches/index.php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/config/db_connect.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';
require_once __DIR__ . '/../../../app/models/BranchModel.php';
require_once __DIR__ . '/../../../app/helpers/TenantContext.php';

session_start();

// Check admin access
if (!isAdmin()) {
    redirect('/Modern/public/auth/login.php');
}

$db = getDBConnection();
$branchModel = new \Models\BranchModel($db);
$tenant = \Helpers\TenantContext::getCurrentTenant();

if (!$tenant) {
    // If no tenant, create one or redirect to setup
    redirect('/Modern/public/admin/settings/index.php');
}

$branches = $branchModel->getByTenant($tenant['id']);

// Handle branch creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $branchName = trim($_POST['branch_name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($branchName) || empty($location)) {
            $_SESSION['flash']['error'] = 'Branch name and location are required';
        } else {
            try {
                $branch = $branchModel->create($tenant['id'], $branchName, $location, $phone, $email);
                $_SESSION['flash']['success'] = 'Branch created successfully';
                redirect('/Modern/public/admin/branches/index.php');
            } catch (Exception $e) {
                $_SESSION['flash']['error'] = $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] === 'delete') {
        $branchId = (int) ($_POST['branch_id'] ?? 0);
        try {
            if ($branchModel->existsForTenant($tenant['id'], $branchId)) {
                $staffCount = $branchModel->getStaffCount($branchId);
                if ($staffCount > 0) {
                    $_SESSION['flash']['error'] = 'Cannot delete branch with active staff';
                } else {
                    $branchModel->delete($branchId);
                    $_SESSION['flash']['success'] = 'Branch deleted successfully';
                }
            } else {
                $_SESSION['flash']['error'] = 'Branch not found';
            }
        } catch (Exception $e) {
            $_SESSION['flash']['error'] = $e->getMessage();
        }
        redirect('/Modern/public/admin/branches/index.php');
    }
}

$page_title = 'Branches';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/Modern/public/admin/dashboard.php'],
    ['label' => 'Branches', 'active' => true]
];

ob_start();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h5>Manage Your Branches</h5>
        <p class="text-muted">Create and manage store branches</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal">
            <i class="fas fa-plus me-2"></i>Add New Branch
        </button>
    </div>
</div>

<div class="row">
    <?php if (empty($branches)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-store fa-3x text-muted mb-3"></i>
                    <h5>No Branches Created Yet</h5>
                    <p class="text-muted">Create your first branch to start managing locations</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal">
                        <i class="fas fa-plus me-2"></i>Create First Branch
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($branches as $branch): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-store me-2 text-primary"></i>
                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                        </h5>
                        <p class="card-text">
                            <small class="text-muted d-block">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($branch['location']); ?>
                            </small>
                            <?php if ($branch['phone']): ?>
                                <small class="text-muted d-block">
                                    <i class="fas fa-phone me-2"></i>
                                    <?php echo htmlspecialchars($branch['phone']); ?>
                                </small>
                            <?php endif; ?>
                            <?php if ($branch['email']): ?>
                                <small class="text-muted d-block">
                                    <i class="fas fa-envelope me-2"></i>
                                    <?php echo htmlspecialchars($branch['email']); ?>
                                </small>
                            <?php endif; ?>
                            <small class="text-muted d-block">
                                <i class="fas fa-users me-2"></i>
                                <?php echo $branch['staff_count'] ?? 0; ?> Staff
                            </small>
                        </p>
                        <div class="d-flex gap-2 mt-3">
                            <a href="/Modern/public/admin/branches/edit.php?id=<?php echo $branch['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="/Modern/public/admin/staff/index.php?branch_id=<?php echo $branch['id']; ?>" 
                               class="btn btn-sm btn-outline-info">
                                <i class="fas fa-users"></i> Staff
                            </a>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDelete(<?php echo $branch['id']; ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Code: <code><?php echo htmlspecialchars($branch['branch_code']); ?></code>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Branch Modal -->
<div class="modal fade" id="createBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="branch_name" class="form-label">Branch Name *</label>
                        <input type="text" class="form-control" id="branch_name" name="branch_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" required 
                               placeholder="e.g., Nairobi, CBD">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete branch <strong id="deleteBranchName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
                <input type="hidden" id="deleteBranchId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteBranchName').textContent = name;
    document.getElementById('deleteBranchId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
    const id = document.getElementById('deleteBranchId').value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="branch_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../templates/admin/layout.php';
?>