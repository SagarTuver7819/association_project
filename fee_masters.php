<?php
// fee_masters.php
// Unified dynamic fee masters dashboard

require_once 'config/db.php';

$pageTitle = 'Fee & Income Masters';

$successMsg = '';
$errorMsg = '';
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'maint'; // 'maint' or 'other'
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle CRUD operations for Maintenance Periods
if ($tab === 'maint') {
    // Delete
    if ($action === 'delete' && $id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM maintenance_fees_master WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['success_msg'] = "Maintenance Period deleted successfully!";
            header("Location: fee_masters.php?tab=maint");
            exit;
        } catch (PDOException $e) {
            $errorMsg = "Cannot delete this period. It might be linked to existing receipt records.";
            $action = 'list';
        }
    }
    
    // Add or Edit Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
        $periodName = trim($_POST['period_name'] ?? '');
        $defaultAmount = floatval($_POST['default_amount'] ?? 0.00);
        
        if (!empty($periodName)) {
            try {
                if ($action === 'edit' && $id) {
                    $stmt = $pdo->prepare("UPDATE maintenance_fees_master SET period_name = :name, default_amount = :amount WHERE id = :id");
                    $stmt->execute(['name' => $periodName, 'amount' => $defaultAmount, 'id' => $id]);
                    $_SESSION['success_msg'] = "Maintenance Period updated successfully!";
                    header("Location: fee_masters.php?tab=maint");
                    exit;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO maintenance_fees_master (period_name, default_amount) VALUES (:name, :amount)");
                    $stmt->execute(['name' => $periodName, 'amount' => $defaultAmount]);
                    $_SESSION['success_msg'] = "New Maintenance Period added successfully!";
                    header("Location: fee_masters.php?tab=maint");
                    exit;
                }
            } catch (PDOException $e) {
                $errorMsg = "Maintenance Period already exists or a database error occurred.";
            }
        } else {
            $errorMsg = "Please fill out all mandatory fields.";
        }
    }
}

// Handle CRUD operations for Other Fees & Incomes
if ($tab === 'other') {
    // Delete
    if ($action === 'delete' && $id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM other_fees_master WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['success_msg'] = "Fee category deleted successfully!";
            header("Location: fee_masters.php?tab=other");
            exit;
        } catch (PDOException $e) {
            $errorMsg = "Cannot delete this category. It might be linked to existing receipt records.";
            $action = 'list';
        }
    }
    
    // Add or Edit Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
        $feeName = trim($_POST['fee_name'] ?? '');
        $defaultAmount = floatval($_POST['default_amount'] ?? 0.00);
        
        if (!empty($feeName)) {
            try {
                if ($action === 'edit' && $id) {
                    $stmt = $pdo->prepare("UPDATE other_fees_master SET fee_name = :name, default_amount = :amount WHERE id = :id");
                    $stmt->execute(['name' => $feeName, 'amount' => $defaultAmount, 'id' => $id]);
                    $_SESSION['success_msg'] = "Fee category updated successfully!";
                    header("Location: fee_masters.php?tab=other");
                    exit;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO other_fees_master (fee_name, default_amount) VALUES (:name, :amount)");
                    $stmt->execute(['name' => $feeName, 'amount' => $defaultAmount]);
                    $_SESSION['success_msg'] = "New Fee category added successfully!";
                    header("Location: fee_masters.php?tab=other");
                    exit;
                }
            } catch (PDOException $e) {
                $errorMsg = "Fee category already exists or a database error occurred.";
            }
        } else {
            $errorMsg = "Please fill out all mandatory fields.";
        }
    }
}

require_once 'includes/header.php';

// Load Redirect message alerts
if (isset($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Load edit data
$editData = [];
if ($action === 'edit' && $id) {
    if ($tab === 'maint') {
        $stmt = $pdo->prepare("SELECT * FROM maintenance_fees_master WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $editData = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM other_fees_master WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $editData = $stmt->fetch();
    }
}
?>

<div class="action-header">
    <div class="action-header-title">
        <h2>Fee & Income Masters Dashboard</h2>
        <span>Configure dynamic billing particulars and period schedules for receipts</span>
    </div>
</div>

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

<!-- Tabs navigation menu -->
<div class="form-card tab-navigation" style="padding: 0.5rem; margin-bottom: 2rem; background: var(--bg-card); border-radius: 8px;">
    <a href="fee_masters.php?tab=maint" class="btn <?php echo ($tab === 'maint') ? 'btn-primary' : 'btn-outline'; ?>" style="flex: 1; justify-content: center; padding: 0.8rem;">
        <i class="fa-solid fa-calendar-days"></i> Maintenance Periods Master (મેન્ટેનન્સ પિરિયડ)
    </a>
    <a href="fee_masters.php?tab=other" class="btn <?php echo ($tab === 'other') ? 'btn-primary' : 'btn-outline'; ?>" style="flex: 1; justify-content: center; padding: 0.8rem;">
        <i class="fa-solid fa-coins"></i> Other Fees & Incomes Master (અન્ય ફી અને આવક)
    </a>
</div>

<div class="dual-panel-grid fee-masters-grid">
    
    <!-- LEFT PANEL: REGISTRY LIST -->
    <div>
        <?php if ($tab === 'maint'): ?>
            <!-- MAINTENANCE REGISTRY LIST -->
            <?php
            $stmt = $pdo->query("SELECT * FROM maintenance_fees_master ORDER BY id DESC");
            $periods = $stmt->fetchAll();
            ?>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="datatable-premium" id="maintFeesTable">
                        <thead>
                            <tr>
                                <th>Maintenance Period Name (પિરિયડ)</th>
                                <th style="width: 150px; text-align: right;">Default Charge (રૂ.)</th>
                                <th style="width: 180px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($periods) > 0): ?>
                                <?php foreach ($periods as $p): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['period_name']); ?></strong></td>
                                        <td style="text-align: right; font-weight: 700; color: var(--primary);">Rs. <?php echo number_format($p['default_amount'], 2); ?></td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <a href="fee_masters.php?tab=maint&action=edit&id=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </a>
                                                <a 
                                                    href="fee_masters.php?tab=maint&action=delete&id=<?php echo $p['id']; ?>" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete this maintenance period? All receipt mapped records with this period will be permanently deleted!'); return false;"
                                                >
                                                    <i class="fa-solid fa-trash-can"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 3rem;">No maintenance periods configured.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php else: ?>
            <!-- OTHER FEES REGISTRY LIST -->
            <?php
            $stmt = $pdo->query("SELECT * FROM other_fees_master ORDER BY id DESC");
            $fees = $stmt->fetchAll();
            ?>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="datatable-premium" id="otherFeesTable">
                        <thead>
                            <tr>
                                <th>Fee Name / Income Particular (ફી નું નામ)</th>
                                <th style="width: 150px; text-align: right;">Default Charge (રૂ.)</th>
                                <th style="width: 180px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fees) > 0): ?>
                                <?php foreach ($fees as $f): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($f['fee_name']); ?></strong></td>
                                        <td style="text-align: right; font-weight: 700; color: var(--primary);">Rs. <?php echo number_format($f['default_amount'], 2); ?></td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <a href="fee_masters.php?tab=other&action=edit&id=<?php echo $f['id']; ?>" class="btn btn-outline btn-sm">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </a>
                                                <a 
                                                    href="fee_masters.php?tab=other&action=delete&id=<?php echo $f['id']; ?>" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete this fee category? All receipt mapped records with this category will be permanently deleted!'); return false;"
                                                >
                                                    <i class="fa-solid fa-trash-can"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 3rem;">No fee categories configured.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- RIGHT PANEL: ADD / EDIT CARD FORM -->
    <div class="form-card">
        <?php if ($tab === 'maint'): ?>
            <!-- MAINTENANCE ADD/EDIT FORM -->
            <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-calendar-plus"></i> <?php echo $action === 'edit' ? 'Edit Maintenance Period' : 'Add Maintenance Period'; ?>
            </h3>
            
            <form action="fee_masters.php?tab=maint&action=<?php echo $action === 'edit' ? 'edit' : 'add'; ?><?php echo $id ? '&id='.$id : ''; ?>" method="POST" autocomplete="off">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="period_name">Period Name / Dates (પિરિયડ) <span style="color:var(--danger)">*</span></label>
                    <input 
                        type="text" 
                        id="period_name" 
                        name="period_name" 
                        class="input-control" 
                        placeholder="e.g. 01.04.2026 To 31.03.2028" 
                        value="<?php echo htmlspecialchars($editData['period_name'] ?? ''); ?>" 
                        required
                    >
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="default_amount">Default Charge / Amount (ચાર્જ રકમ) <span style="color:var(--danger)">*</span></label>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="default_amount" 
                        name="default_amount" 
                        class="input-control" 
                        placeholder="0.00" 
                        value="<?php echo htmlspecialchars($editData['default_amount'] ?? '0.00'); ?>" 
                        required
                    >
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-accent" style="flex: 1; padding: 0.8rem;">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $action === 'edit' ? 'Update Period' : 'Save Period'; ?>
                    </button>
                    <?php if ($action === 'edit'): ?>
                        <a href="fee_masters.php?tab=maint" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
            
        <?php else: ?>
            <!-- OTHER FEE ADD/EDIT FORM -->
            <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-plus"></i> <?php echo $action === 'edit' ? 'Edit Fee Category' : 'Add Fee Category'; ?>
            </h3>
            
            <form action="fee_masters.php?tab=other&action=<?php echo $action === 'edit' ? 'edit' : 'add'; ?><?php echo $id ? '&id='.$id : ''; ?>" method="POST" autocomplete="off">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="fee_name">Fee Name / Income Description (ફી નામ) <span style="color:var(--danger)">*</span></label>
                    <input 
                        type="text" 
                        id="fee_name" 
                        name="fee_name" 
                        class="input-control" 
                        placeholder="e.g. Clubhouse Fee" 
                        value="<?php echo htmlspecialchars($editData['fee_name'] ?? ''); ?>" 
                        required
                    >
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="default_amount">Default Charge / Amount (ચાર્જ રકમ) <span style="color:var(--danger)">*</span></label>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="default_amount" 
                        name="default_amount" 
                        class="input-control" 
                        placeholder="0.00" 
                        value="<?php echo htmlspecialchars($editData['default_amount'] ?? '0.00'); ?>" 
                        required
                    >
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-accent" style="flex: 1; padding: 0.8rem;">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $action === 'edit' ? 'Update Fee' : 'Save Fee'; ?>
                    </button>
                    <?php if ($action === 'edit'): ?>
                        <a href="fee_masters.php?tab=other" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
