<?php
// plot_statuses.php
// Master CRUD for Plot Statuses

require_once 'config/db.php';

$pageTitle = 'Plot Status Master';
require_once 'includes/header.php';

$successMsg = '';
$errorMsg = '';

// Handle Delete Request
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM plot_statuses WHERE id = :id");
        $stmt->execute(['id' => $deleteId]);
        $successMsg = "Plot status deleted successfully!";
    } catch (PDOException $e) {
        $errorMsg = "Cannot delete this plot status because it is linked to existing plots.";
    }
}

// Handle Add / Edit Form Submission
$editId = null;
$editName = '';

if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM plot_statuses WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $statusToEdit = $stmt->fetch();
    if ($statusToEdit) {
        $editName = $statusToEdit['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusName = trim($_POST['name'] ?? '');
    $postEditId = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;
    
    if (!empty($statusName)) {
        try {
            if ($postEditId) {
                // Update
                $stmt = $pdo->prepare("UPDATE plot_statuses SET name = :name WHERE id = :id");
                $stmt->execute(['name' => $statusName, 'id' => $postEditId]);
                $successMsg = "Plot status updated successfully!";
                $editId = null; // Clear edit mode
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO plot_statuses (name) VALUES (:name)");
                $stmt->execute(['name' => $statusName]);
                $successMsg = "New plot status added successfully!";
            }
        } catch (PDOException $e) {
            $errorMsg = "This plot status already exists.";
        }
    } else {
        $errorMsg = "Please enter a valid plot status name.";
    }
}

// Fetch all plot statuses
try {
    $stmt = $pdo->query("SELECT * FROM plot_statuses ORDER BY id DESC");
    $plotStatuses = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading plot statuses: " . $e->getMessage();
    $plotStatuses = [];
}
?>

<div class="action-header">
    <div class="action-header-title">
        <h2>Plot Status Master</h2>
        <span>Manage the choices for Plot Statuses dynamically selected in the Main Plot Form</span>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($successMsg)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        showToast("<?php echo htmlspecialchars($successMsg); ?>", "success");
    });
    </script>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        showToast("<?php echo htmlspecialchars($errorMsg); ?>", "danger");
    });
    </script>
<?php endif; ?>

<div class="dual-panel-grid doc-types-grid">
    <!-- Add/Edit Form Card -->
    <div class="form-card">
        <h3><?php echo $editId ? '<i class="fa-solid fa-pen-to-square"></i> Edit Plot Status' : '<i class="fa-solid fa-square-plus"></i> Add Plot Status'; ?></h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Provide a descriptive name (e.g. Open Land, Residents, Under Construction, etc.)
        </p>
        
        <form action="plot_statuses.php" method="POST" autocomplete="off">
            <?php if ($editId): ?>
                <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
            <?php endif; ?>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="name">Plot Status Name (પ્લોટ સ્ટેટસ)</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="input-control" 
                    value="<?php echo htmlspecialchars($editName); ?>" 
                    placeholder="e.g. Residents" 
                    required
                >
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-accent">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $editId ? 'Update Master' : 'Save to Master'; ?>
                </button>
                <?php if ($editId): ?>
                    <a href="plot_statuses.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Table Grid -->
    <div class="table-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background-color: #fafdfb;">
            <h3 style="font-size: 1.1rem;"><i class="fa-solid fa-list-ol"></i> Registered Plot Statuses</h3>
        </div>
        
        <div class="table-responsive">
            <table class="datatable-premium" id="plotStatusesTable">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Plot Status Name (પ્લોટ સ્ટેટસ)</th>
                        <th style="width: 180px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($plotStatuses) > 0): ?>
                        <?php foreach ($plotStatuses as $status): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($status['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($status['name']); ?></strong></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="plot_statuses.php?edit=<?php echo $status['id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a 
                                            href="plot_statuses.php?delete=<?php echo $status['id']; ?>" 
                                            class="btn btn-danger btn-sm" 
                                            onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete this plot status? This might affect existing plots.'); return false;" 
                                            title="Delete"
                                        >
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 2rem;">No plot statuses registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
