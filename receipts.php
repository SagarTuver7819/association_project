<?php
// receipts.php
// Receipt issuance registry corresponding to Screenshot 1

require_once 'config/db.php';

$pageTitle = 'Receipt Master';
require_once 'includes/header.php';

$successMsg = '';
$errorMsg = '';
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$receiptId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle Delete Request
if ($action === 'delete' && $receiptId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM receipts WHERE id = :id");
        $stmt->execute(['id' => $receiptId]);
        $_SESSION['success_msg'] = "Receipt deleted successfully!";
        header("Location: receipts.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Database error deleting receipt: " . htmlspecialchars($e->getMessage());
        $action = 'list';
    }
}

// Redirect alert messages on redirection
if (isset($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Auto-generate next receipt number (e.g. max receipt + 1, starting from 2590)
$nextReceiptNo = '2590';
try {
    $stmt = $pdo->query("SELECT MAX(CAST(receipt_no AS UNSIGNED)) as max_no FROM receipts");
    $maxVal = $stmt->fetch()['max_no'];
    if ($maxVal && $maxVal >= 2590) {
        $nextReceiptNo = strval($maxVal + 1);
    }
} catch (PDOException $e) {
    // Fail silently, use default
}

// Handle Receipt Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $receiptNo = trim($_POST['receipt_no'] ?? '');
    $receiptDate = !empty($_POST['receipt_date']) ? $_POST['receipt_date'] : date('Y-m-d');
    $plotId = intval($_POST['plot_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $mobileNo = trim($_POST['mobile_no'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    // Dynamic Fee Arrays
    $maintFeeIds = $_POST['maint_fee_ids'] ?? [];
    $maintFeeAmounts = $_POST['maint_fee_amounts'] ?? [];
    $otherFeeIds = $_POST['other_fee_ids'] ?? [];
    $otherFeeAmounts = $_POST['other_fee_amounts'] ?? [];
    
    // Calculate total amount
    $totalAmount = 0.00;
    foreach ($maintFeeAmounts as $amt) {
        $totalAmount += floatval($amt);
    }
    foreach ($otherFeeAmounts as $amt) {
        $totalAmount += floatval($amt);
    }
    
    $paymentMode = trim($_POST['payment_mode'] ?? 'Cash');
    $remark = trim($_POST['remark'] ?? '');
    $receivedBy = trim($_POST['received_by'] ?? $_SESSION['full_name']);
    
    if (!empty($receiptNo) && $plotId > 0 && !empty($name)) {
        try {
            $pdo->beginTransaction();
            
            if ($action === 'edit' && $receiptId) {
                // Update basic receipt metadata
                $stmt = $pdo->prepare("
                    UPDATE receipts SET 
                        receipt_no = :receipt_no,
                        receipt_date = :receipt_date,
                        plot_id = :plot_id,
                        name = :name,
                        mobile_no = :mobile_no,
                        city = :city,
                        total_amount = :total,
                        payment_mode = :pay_mode,
                        remark = :remark,
                        received_by = :received_by
                    WHERE id = :id
                ");
                $stmt->execute([
                    'receipt_no' => $receiptNo, 'receipt_date' => $receiptDate, 'plot_id' => $plotId,
                    'name' => $name, 'mobile_no' => $mobileNo, 'city' => $city,
                    'total' => $totalAmount, 'pay_mode' => $paymentMode, 'remark' => $remark ?: null,
                    'received_by' => $receivedBy, 'id' => $receiptId
                ]);
                
                // Delete existing mapping linkages to rewrite them cleanly
                $pdo->prepare("DELETE FROM receipt_maintenance_fees WHERE receipt_id = :id")->execute(['id' => $receiptId]);
                $pdo->prepare("DELETE FROM receipt_other_fees WHERE receipt_id = :id")->execute(['id' => $receiptId]);
            } else {
                // Add receipt row first
                $stmt = $pdo->prepare("
                    INSERT INTO receipts (
                        receipt_no, receipt_date, plot_id, name, mobile_no, city,
                        total_amount, payment_mode, remark, received_by
                    ) VALUES (
                        :receipt_no, :receipt_date, :plot_id, :name, :mobile_no, :city,
                        :total, :pay_mode, :remark, :received_by
                    )
                ");
                $stmt->execute([
                    'receipt_no' => $receiptNo, 'receipt_date' => $receiptDate, 'plot_id' => $plotId,
                    'name' => $name, 'mobile_no' => $mobileNo, 'city' => $city,
                    'total' => $totalAmount, 'pay_mode' => $paymentMode, 'remark' => $remark ?: null,
                    'received_by' => $receivedBy
                ]);
                $receiptId = $pdo->lastInsertId();
            }
            
            // Link selected maintenance fees
            if (!empty($maintFeeIds)) {
                $maintLinkStmt = $pdo->prepare("INSERT INTO receipt_maintenance_fees (receipt_id, maintenance_fee_id, amount) VALUES (:receipt_id, :fee_id, :amount)");
                foreach ($maintFeeIds as $index => $feeId) {
                    $amt = floatval($maintFeeAmounts[$index] ?? 0.00);
                    $maintLinkStmt->execute([
                        'receipt_id' => $receiptId,
                        'fee_id' => intval($feeId),
                        'amount' => $amt
                    ]);
                }
            }
            
            // Link selected other fees
            if (!empty($otherFeeIds)) {
                $otherLinkStmt = $pdo->prepare("INSERT INTO receipt_other_fees (receipt_id, other_fee_id, amount) VALUES (:receipt_id, :fee_id, :amount)");
                foreach ($otherFeeIds as $index => $feeId) {
                    $amt = floatval($otherFeeAmounts[$index] ?? 0.00);
                    $otherLinkStmt->execute([
                        'receipt_id' => $receiptId,
                        'fee_id' => intval($feeId),
                        'amount' => $amt
                    ]);
                }
            }
            
            $pdo->commit();
            
            $_SESSION['success_msg'] = ($action === 'edit') ? "Receipt updated successfully!" : "Receipt issued successfully!";
            header("Location: receipts.php");
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = "Database error saving receipt: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errorMsg = "Please make sure to fill out Receipt Number, Plot Number, and Customer Name.";
    }
}

// Fetch all registered plots for the auto-fill dropdown
$plotsList = [];
try {
    $plotsStmt = $pdo->query("SELECT id, plot_no, plot_size_sq_vaar, purchaser_name, purchaser_mobile, purchaser_city FROM plots ORDER BY plot_no ASC");
    $plotsList = $plotsStmt->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading plots dropdown: " . $e->getMessage();
}

// Edit Mode - Load receipt data
$receiptData = [];
$existingMaint = [];
$existingOther = [];
$maintMasters = [];
$otherMasters = [];

try {
    $maintMasters = $pdo->query("SELECT * FROM maintenance_fees_master ORDER BY id ASC")->fetchAll();
    $otherMasters = $pdo->query("SELECT * FROM other_fees_master ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading fee masters: " . $e->getMessage();
}

if ($action === 'edit' && $receiptId) {
    $stmt = $pdo->prepare("SELECT * FROM receipts WHERE id = :id");
    $stmt->execute(['id' => $receiptId]);
    $receiptData = $stmt->fetch();
    if (!$receiptData) {
        $errorMsg = "Receipt record not found.";
        $action = 'list';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM receipt_maintenance_fees WHERE receipt_id = :id ORDER BY id ASC");
            $stmt->execute(['id' => $receiptId]);
            $existingMaint = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT * FROM receipt_other_fees WHERE receipt_id = :id ORDER BY id ASC");
            $stmt->execute(['id' => $receiptId]);
            $existingOther = $stmt->fetchAll();
        } catch (PDOException $e) {
            $errorMsg = "Error loading receipt fee details: " . $e->getMessage();
        }
    }
}
?>

<?php if ($action === 'list'): ?>
    <!-- -------------------------------- LIST VIEW -------------------------------- -->
    <?php
    $search = trim($_GET['search'] ?? '');
    $receipts = [];
    try {
        if (!empty($search)) {
            $stmt = $pdo->prepare("
                SELECT r.*, p.plot_no 
                FROM receipts r
                JOIN plots p ON r.plot_id = p.id
                WHERE r.receipt_no LIKE :search
                   OR r.name LIKE :search
                   OR p.plot_no LIKE :search
                ORDER BY r.id DESC
            ");
            $stmt->execute(['search' => "%$search%"]);
        } else {
            $stmt = $pdo->query("
                SELECT r.*, p.plot_no 
                FROM receipts r
                JOIN plots p ON r.plot_id = p.id
                ORDER BY r.id DESC
            ");
        }
        $receipts = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Error fetching receipts list: " . $e->getMessage();
    }
    ?>
    
    <div class="action-header">
        <div class="action-header-title">
            <h2>Receipts Registry</h2>
            <span>Issue payments and trace maintenance financial ledgers</span>
        </div>
        <div>
            <a href="receipts.php?action=add" class="btn btn-primary"><i class="fa-solid fa-receipt"></i> Issue New Receipt</a>
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
    
    <!-- Search Bar Card -->
    <div class="form-card" style="padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
        <form action="receipts.php" method="GET" style="display: flex; gap: 10px;">
            <input type="hidden" name="action" value="list">
            <input 
                type="text" 
                name="search" 
                class="input-control" 
                placeholder="Search by Receipt No, Plot No, or Customer Name..." 
                value="<?php echo htmlspecialchars($search); ?>"
            >
            <button type="submit" class="btn btn-accent"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if (!empty($search)): ?>
                <a href="receipts.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Table Grid -->
    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 110px;">Receipt No</th>
                        <th style="width: 120px;">Date</th>
                        <th style="width: 100px;">Plot No</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th style="width: 150px;">Total Amount</th>
                        <th style="width: 250px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($receipts) > 0): ?>
                        <?php foreach ($receipts as $receipt): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($receipt['receipt_no']); ?></strong></td>
                                <td><?php echo date('d-m-Y', strtotime($receipt['receipt_date'])); ?></td>
                                <td><span style="background: var(--accent-light); padding: 2px 8px; border-radius: 4px; font-weight:700; color: var(--primary);"><?php echo htmlspecialchars($receipt['plot_no']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($receipt['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($receipt['mobile_no']); ?></td>
                                <td><strong>Rs. <?php echo number_format($receipt['total_amount'], 2); ?></strong></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="print_receipt.php?id=<?php echo $receipt['id']; ?>" class="btn btn-accent btn-sm" target="_blank" title="Print/View PDF">
                                            <i class="fa-solid fa-print"></i> Print
                                        </a>
                                        <a href="receipts.php?action=edit&id=<?php echo $receipt['id']; ?>" class="btn btn-outline btn-sm" title="Edit Receipt">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a 
                                            href="receipts.php?action=delete&id=<?php echo $receipt['id']; ?>" 
                                            class="btn btn-danger btn-sm" 
                                            onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete this receipt record permanently?'); return false;"
                                            title="Delete Receipt"
                                        >
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No receipts found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- -------------------------------- ADD / EDIT VIEW -------------------------------- -->
    <div class="action-header">
        <div class="action-header-title">
            <h2><?php echo $action === 'edit' ? 'Modify Issued Receipt' : 'Issue New Receipt'; ?></h2>
            <span>Compile the form fields to print the official association receipt.</span>
        </div>
        <div>
            <a href="receipts.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Ledger</a>
        </div>
    </div>
    
    <?php if (!empty($errorMsg)): ?>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?php echo htmlspecialchars($errorMsg); ?>", "danger");
        });
        </script>
    <?php endif; ?>
    
    <form action="receipts.php?action=<?php echo $action; ?><?php echo $receiptId ? '&id='.$receiptId : ''; ?>" method="POST" autocomplete="off">
        <div class="receipt-split">
            <!-- Left Side: Owner/Plot metadata -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="form-card">
                    <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;"><i class="fa-solid fa-user-tag"></i> General Particulars</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="receipt_no">Receipt No. (રસીદ નં.) <span style="color:var(--danger)">*</span></label>
                            <input 
                                type="text" 
                                id="receipt_no" 
                                name="receipt_no" 
                                class="input-control" 
                                value="<?php echo htmlspecialchars($receiptData['receipt_no'] ?? $nextReceiptNo); ?>" 
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="receipt_date">Date (તારીખ) <span style="color:var(--danger)">*</span></label>
                            <input 
                                type="date" 
                                id="receipt_date" 
                                name="receipt_date" 
                                class="input-control" 
                                value="<?php echo htmlspecialchars($receiptData['receipt_date'] ?? date('Y-m-d')); ?>" 
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="plot_id">Plot No. (પ્લોટ નં.) <span style="color:var(--danger)">*</span></label>
                            <select id="plot_id" name="plot_id" class="input-control" onchange="autoFillPlotDetails()" required>
                                <option value="">-- Choose Plot --</option>
                                <?php foreach ($plotsList as $plot): ?>
                                    <option 
                                        value="<?php echo $plot['id']; ?>"
                                        <?php echo (isset($receiptData['plot_id']) && $receiptData['plot_id'] == $plot['id']) ? 'selected' : ''; ?>
                                    >
                                        Plot No: <?php echo htmlspecialchars($plot['plot_no']); ?> (<?php echo htmlspecialchars($plot['purchaser_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="plot_size_sq_vaar">Plot Size (Sq.Vaar) (ચો.વાર)</label>
                            <input 
                                type="text" 
                                id="plot_size_sq_vaar" 
                                class="input-control" 
                                placeholder="Auto-filled" 
                                readonly
                            >
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="name">Name (નામ) <span style="color:var(--danger)">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($receiptData['name'] ?? ''); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="mobile_no">Mobile No. (મોબાઈલ નં.)</label>
                            <input 
                                type="text" 
                                id="mobile_no" 
                                name="mobile_no" 
                                class="input-control" 
                                value="<?php echo htmlspecialchars($receiptData['mobile_no'] ?? ''); ?>"
                            >
                        </div>
                        <div class="form-group">
                            <label for="city">City (શહેર)</label>
                            <input 
                                type="text" 
                                id="city" 
                                name="city" 
                                class="input-control" 
                                value="<?php echo htmlspecialchars($receiptData['city'] ?? 'Green City'); ?>" 
                                required
                            >
                        </div>
                    </div>
                </div>
                
                <div class="form-card">
                    <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;"><i class="fa-solid fa-credit-card"></i> Payment & Accounting</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="payment_mode">Mode of Payment</label>
                            <select id="payment_mode" name="payment_mode" class="input-control" style="font-weight: 700;">
                                <option value="Cash" <?php echo (isset($receiptData['payment_mode']) && $receiptData['payment_mode'] === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="Cheque" <?php echo (isset($receiptData['payment_mode']) && $receiptData['payment_mode'] === 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                <option value="UPI / Online" <?php echo (isset($receiptData['payment_mode']) && $receiptData['payment_mode'] === 'UPI / Online') ? 'selected' : ''; ?>>UPI / Online</option>
                                <option value="Bank Transfer" <?php echo (isset($receiptData['payment_mode']) && $receiptData['payment_mode'] === 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="received_by">Received By</label>
                            <input 
                                type="text" 
                                id="received_by" 
                                name="received_by" 
                                class="input-control" 
                                value="<?php echo htmlspecialchars($receiptData['received_by'] ?? $_SESSION['full_name']); ?>" 
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="remark">Remark (નોંધ / રીમાર્ક)</label>
                        <textarea 
                            id="remark" 
                            name="remark" 
                            class="input-control" 
                            rows="2"
                        ><?php echo htmlspecialchars($receiptData['remark'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Details and Math calculations (live preview) -->
            <div class="form-card">
                <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;"><i class="fa-solid fa-calculator"></i> Billing Particulars (Rs.)</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Maintenance charges column -->
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <span style="font-size:0.8rem; font-weight:700; color: var(--text-muted); text-transform:uppercase; border-bottom:1px solid var(--border); padding-bottom:3px; display: block;">Maintenance Fees (મેન્ટેનન્સ)</span>
                        
                        <div style="display: flex; gap: 8px;">
                            <select id="select-maint" class="input-control" style="flex: 1; padding: 6px 10px; font-size: 0.9rem;">
                                <option value="">-- Choose Period --</option>
                                <?php foreach ($maintMasters as $mm): ?>
                                    <option value="<?php echo $mm['id']; ?>">
                                        <?php echo htmlspecialchars($mm['period_name']); ?> (Rs. <?php echo number_format($mm['default_amount'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-accent" id="btn-add-maint" style="padding: 6px 12px; min-width: auto; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                        
                        <div id="maint-rows-container" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.5rem;">
                            <?php if ($action === 'edit'): ?>
                                <?php foreach ($existingMaint as $em): ?>
                                    <?php
                                    $periodName = '';
                                    foreach ($maintMasters as $mm) {
                                        if ($mm['id'] == $em['maintenance_fee_id']) {
                                            $periodName = $mm['period_name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="fee-row-item">
                                        <span style="font-weight: 700; font-size: 0.9rem; color: var(--text);"><?php echo htmlspecialchars($periodName); ?></span>
                                        <input type="number" step="0.01" name="maint_fee_amounts[]" class="input-control amount-input" value="<?php echo htmlspecialchars($em['amount']); ?>" oninput="calcReceiptTotal()" style="padding: 6px 10px; font-weight: 700;">
                                        <input type="hidden" name="maint_fee_ids[]" value="<?php echo $em['maintenance_fee_id']; ?>">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.fee-row-item').remove(); calcReceiptTotal();" style="padding: 6px 10px; min-width: auto;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Clean elegant divider -->
                    <div style="border-bottom: 1px dashed var(--border); margin: 0.25rem 0;"></div>
                    
                    <!-- Other Fees column -->
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <span style="font-size:0.8rem; font-weight:700; color: var(--text-muted); text-transform:uppercase; border-bottom:1px solid var(--border); padding-bottom:3px; display: block;">Other Fees & Incomes (અન્ય ચાર્જ)</span>
                        
                        <div style="display: flex; gap: 8px;">
                            <select id="select-other" class="input-control" style="flex: 1; padding: 6px 10px; font-size: 0.9rem;">
                                <option value="">-- Choose Fee / Particular --</option>
                                <?php foreach ($otherMasters as $om): ?>
                                    <option value="<?php echo $om['id']; ?>">
                                        <?php echo htmlspecialchars($om['fee_name']); ?> (Rs. <?php echo number_format($om['default_amount'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-accent" id="btn-add-other" style="padding: 6px 12px; min-width: auto; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                        
                        <div id="other-rows-container" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.5rem;">
                            <?php if ($action === 'edit'): ?>
                                <?php foreach ($existingOther as $eo): ?>
                                    <?php
                                    $feeName = '';
                                    foreach ($otherMasters as $om) {
                                        if ($om['id'] == $eo['other_fee_id']) {
                                            $feeName = $om['fee_name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="fee-row-item">
                                        <span style="font-weight: 700; font-size: 0.9rem; color: var(--text);"><?php echo htmlspecialchars($feeName); ?></span>
                                        <input type="number" step="0.01" name="other_fee_amounts[]" class="input-control amount-input" value="<?php echo htmlspecialchars($eo['amount']); ?>" oninput="calcReceiptTotal()" style="padding: 6px 10px; font-weight: 700;">
                                        <input type="hidden" name="other_fee_ids[]" value="<?php echo $eo['other_fee_id']; ?>">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.fee-row-item').remove(); calcReceiptTotal();" style="padding: 6px 10px; min-width: auto;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Live mathematical calculations summary box -->
                <div class="receipt-math-box">
                    <div class="receipt-math-row">
                        <span>Maintenance Total (મેન્ટેનન્સ સરવાળો):</span>
                        <span id="preview_maint">Rs. 0.00</span>
                    </div>
                    <div class="receipt-math-row">
                        <span>Other Fees Total (અન્ય ફી સરવાળો):</span>
                        <span id="preview_others">Rs. 0.00</span>
                    </div>
                    <div class="receipt-math-row">
                        <span>Grand Total (કુલ સરવાળો):</span>
                        <span id="preview_grand" style="font-size:1.2rem; font-weight:800; color: var(--accent-hover);">Rs. 0.00</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.95rem; font-size: 1rem;">
                        <i class="fa-solid fa-print"></i> Save & Issue Receipt
                    </button>
                    <a href="receipts.php" class="btn btn-outline" style="flex: 0.4;">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <script>
    // Embed plot registry and fee master lists dynamically in JS
    const plotsRegistry = <?php echo json_encode($plotsList); ?>;
    const maintMastersList = <?php echo json_encode($maintMasters); ?>;
    const otherMastersList = <?php echo json_encode($otherMasters); ?>;
    
    function autoFillPlotDetails() {
        const plotSelect = document.getElementById('plot_id');
        const selectedPlotId = parseInt(plotSelect.value);
        
        // Fields to auto-fill
        const sizeField = document.getElementById('plot_size_sq_vaar');
        const nameField = document.getElementById('name');
        const mobileField = document.getElementById('mobile_no');
        const cityField = document.getElementById('city');
        
        if (!isNaN(selectedPlotId)) {
            const plotObj = plotsRegistry.find(p => parseInt(p.id) === selectedPlotId);
            if (plotObj) {
                sizeField.value = parseFloat(plotObj.plot_size_sq_vaar).toFixed(2);
                nameField.value = plotObj.purchaser_name;
                mobileField.value = plotObj.purchaser_mobile;
                if (cityField) {
                    cityField.value = plotObj.purchaser_city ? plotObj.purchaser_city : 'Green City';
                }
            }
        } else {
            sizeField.value = '';
            nameField.value = '';
            mobileField.value = '';
            if (cityField) {
                cityField.value = 'Green City';
            }
        }
    }
    
    // Add dynamic row for Maintenance Periods
    document.getElementById('btn-add-maint').addEventListener('click', () => {
        const selector = document.getElementById('select-maint');
        const masterId = parseInt(selector.value);
        if (!masterId) return;

        // Check if already added to prevent duplicates
        const existingIds = Array.from(document.querySelectorAll('input[name="maint_fee_ids[]"]')).map(el => parseInt(el.value));
        if (existingIds.includes(masterId)) {
            showToast("This maintenance period has already been added!", "danger");
            return;
        }

        const masterItem = maintMastersList.find(item => parseInt(item.id) === masterId);
        if (!masterItem) return;

        const container = document.getElementById('maint-rows-container');
        const rowHtml = `
            <div class="fee-row-item">
                <span style="font-weight: 700; font-size: 0.9rem; color: var(--text);">${escapeHtml(masterItem.period_name)}</span>
                <input type="number" step="0.01" name="maint_fee_amounts[]" class="input-control amount-input" value="${parseFloat(masterItem.default_amount).toFixed(2)}" oninput="calcReceiptTotal()" style="padding: 6px 10px; font-weight: 700;">
                <input type="hidden" name="maint_fee_ids[]" value="${masterItem.id}">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.fee-row-item').remove(); calcReceiptTotal();" style="padding: 6px 10px; min-width: auto;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', rowHtml);
        selector.value = ''; // Reset selector
        calcReceiptTotal();
    });

    // Add dynamic row for Other Fees
    document.getElementById('btn-add-other').addEventListener('click', () => {
        const selector = document.getElementById('select-other');
        const masterId = parseInt(selector.value);
        if (!masterId) return;

        // Check if already added to prevent duplicates
        const existingIds = Array.from(document.querySelectorAll('input[name="other_fee_ids[]"]')).map(el => parseInt(el.value));
        if (existingIds.includes(masterId)) {
            showToast("This fee category has already been added!", "danger");
            return;
        }

        const masterItem = otherMastersList.find(item => parseInt(item.id) === masterId);
        if (!masterItem) return;

        const container = document.getElementById('other-rows-container');
        const rowHtml = `
            <div class="fee-row-item">
                <span style="font-weight: 700; font-size: 0.9rem; color: var(--text);">${escapeHtml(masterItem.fee_name)}</span>
                <input type="number" step="0.01" name="other_fee_amounts[]" class="input-control amount-input" value="${parseFloat(masterItem.default_amount).toFixed(2)}" oninput="calcReceiptTotal()" style="padding: 6px 10px; font-weight: 700;">
                <input type="hidden" name="other_fee_ids[]" value="${masterItem.id}">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.fee-row-item').remove(); calcReceiptTotal();" style="padding: 6px 10px; min-width: auto;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', rowHtml);
        selector.value = ''; // Reset selector
        calcReceiptTotal();
    });

    // Escape HTML in JavaScript
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function calcReceiptTotal() {
        let maintenanceTotal = 0.00;
        const maintInputs = document.querySelectorAll('input[name="maint_fee_amounts[]"]');
        maintInputs.forEach(input => {
            maintenanceTotal += parseFloat(input.value) || 0.00;
        });

        let othersTotal = 0.00;
        const otherInputs = document.querySelectorAll('input[name="other_fee_amounts[]"]');
        otherInputs.forEach(input => {
            othersTotal += parseFloat(input.value) || 0.00;
        });

        const grandTotal = maintenanceTotal + othersTotal;
        
        // Update HTML preview values
        document.getElementById('preview_maint').innerText = 'Rs. ' + maintenanceTotal.toFixed(2);
        document.getElementById('preview_others').innerText = 'Rs. ' + othersTotal.toFixed(2);
        document.getElementById('preview_grand').innerText = 'Rs. ' + grandTotal.toFixed(2);
    }
    
    // Auto-fire calculations and fill if editing on load
    window.addEventListener('load', () => {
        autoFillPlotDetails();
        calcReceiptTotal();
    });
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
