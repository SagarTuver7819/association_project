<?php
// plots.php
// Plot management registry corresponding to Screenshot 2

require_once 'config/db.php';

$pageTitle = 'Plots Management';

$successMsg = '';
$errorMsg = '';
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$plotId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle Plot Status Toggle Request
if ($action === 'toggle_status' && $plotId) {
    $newStatus = isset($_GET['status']) && $_GET['status'] === 'Deactive' ? 'Deactive' : 'Active';
    try {
        $stmt = $pdo->prepare("UPDATE plots SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $plotId]);
        $_SESSION['success_msg'] = "Plot status updated to " . $newStatus . " successfully!";
        header("Location: plots.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Error updating plot status: " . $e->getMessage();
        $action = 'list';
    }
}

// Handle Plot Delete Request
if ($action === 'delete' && $plotId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM plots WHERE id = :id");
        $stmt->execute(['id' => $plotId]);
        $_SESSION['success_msg'] = "Plot deleted successfully!";
        header("Location: plots.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Cannot delete this plot. It might be linked to existing receipts.";
        $action = 'list';
    }
}

// Redirect alert messages on redirection
if (isset($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Handle Form Submission (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $plotNo = trim($_POST['plot_no'] ?? '');
    $sizeSqMt = floatval($_POST['plot_size_sq_mt'] ?? 0.00);
    $sizeSqVaar = floatval($_POST['plot_size_sq_vaar'] ?? 0.00);
    $plotStatusId = !empty($_POST['plot_status_id']) ? intval($_POST['plot_status_id']) : null;
    $purchaserName = trim($_POST['purchaser_name'] ?? '');
    $purchaserAddress = trim($_POST['purchaser_address'] ?? '');
    $purchaserCity = trim($_POST['purchaser_city'] ?? '');
    $purchaserMobile = trim($_POST['purchaser_mobile'] ?? '');
    $purchaserAltMobile = trim($_POST['purchaser_alt_mobile'] ?? '');
    $purchaserCo = trim($_POST['purchaser_co'] ?? '');
    
    $documentNo = trim($_POST['document_no'] ?? '');
    $documentDate = !empty($_POST['document_date']) ? $_POST['document_date'] : null;
    $documentTypeId = !empty($_POST['document_type_id']) ? intval($_POST['document_type_id']) : null;
    
    $sellerName = trim($_POST['seller_name'] ?? '');
    $sellerAddress = trim($_POST['seller_address'] ?? '');
    $sellerCity = null;
    $sellerMobile = trim($_POST['seller_mobile'] ?? '');
    $sellerAltMobile = trim($_POST['seller_alt_mobile'] ?? '');
    $sellerCo = null;
    
    $note = trim($_POST['note'] ?? '');
    $plotTransfer = trim($_POST['plot_transfer'] ?? 'NO');
    
    if (!empty($plotNo) && $sizeSqMt > 0) {
        try {
            if ($action === 'edit' && $plotId) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE plots SET 
                        plot_no = :plot_no,
                        plot_size_sq_mt = :size_mt,
                        plot_size_sq_vaar = :size_vaar,
                        plot_status_id = :status_id,
                        purchaser_name = :p_name,
                        purchaser_address = :p_address,
                        purchaser_city = :p_city,
                        purchaser_mobile = :p_mobile,
                        purchaser_alt_mobile = :p_alt_mobile,
                        purchaser_co = :p_co,
                        document_no = :doc_no,
                        document_date = :doc_date,
                        document_type_id = :doc_type,
                        seller_name = :s_name,
                        seller_address = :s_address,
                        seller_city = :s_city,
                        seller_mobile = :s_mobile,
                        seller_alt_mobile = :s_alt_mobile,
                        seller_co = :s_co,
                        note = :note,
                        plot_transfer = :transfer
                    WHERE id = :id
                ");
                $stmt->execute([
                    'plot_no' => $plotNo,
                    'size_mt' => $sizeSqMt,
                    'size_vaar' => $sizeSqVaar,
                    'status_id' => $plotStatusId,
                    'p_name' => $purchaserName,
                    'p_address' => $purchaserAddress,
                    'p_city' => $purchaserCity ?: null,
                    'p_mobile' => $purchaserMobile,
                    'p_alt_mobile' => $purchaserAltMobile ?: null,
                    'p_co' => $purchaserCo ?: null,
                    'doc_no' => $documentNo ?: null,
                    'doc_date' => $documentDate,
                    'doc_type' => $documentTypeId,
                    's_name' => $sellerName ?: null,
                    's_address' => $sellerAddress ?: null,
                    's_city' => $sellerCity ?: null,
                    's_mobile' => $sellerMobile ?: null,
                    's_alt_mobile' => $sellerAltMobile ?: null,
                    's_co' => $sellerCo ?: null,
                    'note' => $note ?: null,
                    'transfer' => $plotTransfer,
                    'id' => $plotId
                ]);
                $_SESSION['success_msg'] = "Plot details updated successfully!";
                header("Location: plots.php");
                exit;
            } else {
                // Add
                $stmt = $pdo->prepare("
                    INSERT INTO plots (
                        plot_no, plot_size_sq_mt, plot_size_sq_vaar, plot_status_id, 
                        purchaser_name, purchaser_address, purchaser_city, purchaser_mobile, purchaser_alt_mobile, purchaser_co,
                        document_no, document_date, document_type_id, 
                        seller_name, seller_address, seller_city, seller_mobile, seller_alt_mobile, seller_co,
                        note, plot_transfer
                    ) VALUES (
                        :plot_no, :size_mt, :size_vaar, :status_id, 
                        :p_name, :p_address, :p_city, :p_mobile, :p_alt_mobile, :p_co,
                        :doc_no, :doc_date, :doc_type, 
                        :s_name, :s_address, :s_city, :s_mobile, :s_alt_mobile, :s_co,
                        :note, :transfer
                    )
                ");
                $stmt->execute([
                    'plot_no' => $plotNo,
                    'size_mt' => $sizeSqMt,
                    'size_vaar' => $sizeSqVaar,
                    'status_id' => $plotStatusId,
                    'p_name' => $purchaserName,
                    'p_address' => $purchaserAddress,
                    'p_city' => $purchaserCity ?: null,
                    'p_mobile' => $purchaserMobile,
                    'p_alt_mobile' => $purchaserAltMobile ?: null,
                    'p_co' => $purchaserCo ?: null,
                    'doc_no' => $documentNo ?: null,
                    'doc_date' => $documentDate,
                    'doc_type' => $documentTypeId,
                    's_name' => $sellerName ?: null,
                    's_address' => $sellerAddress ?: null,
                    's_city' => $sellerCity ?: null,
                    's_mobile' => $sellerMobile ?: null,
                    's_alt_mobile' => $sellerAltMobile ?: null,
                    's_co' => $sellerCo ?: null,
                    'note' => $note ?: null,
                    'transfer' => $plotTransfer
                ]);
                $_SESSION['success_msg'] = "New plot added successfully!";
                header("Location: plots.php");
                exit;
            }
        } catch (PDOException $e) {
            $errorMsg = "A database error occurred: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errorMsg = "Please fill out all mandatory fields: Plot Number and Size (Sq. Mt.).";
    }
}

require_once 'includes/header.php';

// Fetch document types for dropdown
$docTypes = [];
try {
    $stmt = $pdo->query("SELECT * FROM document_types ORDER BY name ASC");
    $docTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading document types: " . $e->getMessage();
}

// Fetch plot statuses for dropdown
$plotStatuses = [];
try {
    $stmt = $pdo->query("SELECT * FROM plot_statuses ORDER BY name ASC");
    $plotStatuses = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading plot statuses: " . $e->getMessage();
}

// Edit Mode - Load current plot details
$plotData = [];
if ($action === 'edit' && $plotId) {
    $stmt = $pdo->prepare("SELECT * FROM plots WHERE id = :id");
    $stmt->execute(['id' => $plotId]);
    $plotData = $stmt->fetch();
    if (!$plotData) {
        $errorMsg = "Plot not found.";
        $action = 'list';
    }
}
?>

<?php if ($action === 'list'): ?>
    <!-- -------------------------------- LIST VIEW -------------------------------- -->
    <?php
    // Search and Status filter query
    $search = trim($_GET['search'] ?? '');
    $statusFilter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
    $plots = [];
    try {
        $query = "
            SELECT p.*, dt.name as document_type, ps.name as plot_status 
            FROM plots p 
            LEFT JOIN document_types dt ON p.document_type_id = dt.id 
            LEFT JOIN plot_statuses ps ON p.plot_status_id = ps.id
        ";
        
        $params = [];
        if ($statusFilter !== '') {
            $query .= " WHERE p.status = :status ";
            $params['status'] = $statusFilter;
        }
        
        $query .= " ORDER BY p.id DESC ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $plots = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Error loading plots list: " . $e->getMessage();
    }
    ?>
    
    <div class="action-header">
        <div class="action-header-title">
            <h2>Plots Registry</h2>
            <span>Manage registered plots, purchasers, and document transfer statuses</span>
        </div>
        <div>
            <a href="plots.php?action=add" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i> Register New Plot</a>
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
        <form action="plots.php" method="GET" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="action" value="list">
            <input 
                type="text" 
                name="search" 
                class="input-control" 
                placeholder="Search by Plot No, Purchaser Name, or Seller Name..." 
                value="<?php echo htmlspecialchars($search); ?>"
                style="flex: 1;"
            >
            
            <div style="display: flex; gap: 5px; align-items: center; border-left: 2px solid var(--border); padding-left: 10px; margin-left: 5px;">
                <label for="min_date" style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">From:</label>
                <input type="date" id="min_date" class="input-control" style="width: 140px;">
                
                <label for="max_date" style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-left: 5px;">To:</label>
                <input type="date" id="max_date" class="input-control" style="width: 140px;">
            </div>
            
            <select name="status_filter" class="input-control" style="width: 180px; font-weight: 600; cursor: pointer;" onchange="this.form.submit()">
                <option value="">-- All Statuses --</option>
                <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Deactive" <?php echo $statusFilter === 'Deactive' ? 'selected' : ''; ?>>Deactive</option>
            </select>
            
            <button type="submit" class="btn btn-accent"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="plots.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Table Grid -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="datatable-premium" id="plotsTable" data-date-col="7">
                <thead>
                    <tr>
                        <th style="width: 130px; text-align: center;">Actions</th>
                        <th style="width: 80px;">Plot Number</th>
                        <th style="width: 130px;">Purchaser's Name</th>
                        <th>Address</th>
                        <th style="width: 115px;">Mobile No</th>
                        <th style="width: 115px;">Alt Mobile No</th>
                        <th style="width: 100px;">Doc No</th>
                        <th style="width: 100px;">Doc Date</th>
                        <th style="width: 130px;">Plot Status</th>
                        <th style="width: 105px;">Size (Sq. Mt.)</th>
                        <th style="width: 105px;">Size (Sq. Vaar)</th>
                        <th style="width: 100px;">Doc Type</th>
                        <th style="width: 105px;">Plot Transfer</th>
                        <th style="width: 100px; text-align: center;">Entry Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($plots) > 0): ?>
                        <?php foreach ($plots as $plot): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="plots.php?action=edit&id=<?php echo $plot['id']; ?>" class="btn btn-outline btn-sm" title="Edit Plot">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a 
                                            href="plots.php?action=delete&id=<?php echo $plot['id']; ?>" 
                                            class="btn btn-danger btn-sm" 
                                            onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete this plot registry? All receipts associated with this plot will be permanently deleted.'); return false;"
                                            title="Delete Plot"
                                        >
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </a>
                                    </div>
                                </td>
                                <td><strong><?php echo htmlspecialchars($plot['plot_no']); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($plot['purchaser_name'] ?: 'N/A'); ?></strong></td>
                                <td style="font-size: 0.82rem; color: var(--text-muted);"><?php echo htmlspecialchars($plot['purchaser_address'] ?: '—'); ?></td>
                                <!-- Mobile No - Primary only -->
                                <td>
                                    <?php if (!empty($plot['purchaser_mobile'])): ?>
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:0.85rem;">
                                            <i class="fa-solid fa-phone" style="color:var(--primary); font-size:0.75rem;"></i>
                                            <?php echo htmlspecialchars($plot['purchaser_mobile']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Alt Mobile No - Separate column -->
                                <td>
                                    <?php if (!empty($plot['purchaser_alt_mobile'])): ?>
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:0.85rem; color:var(--text-muted);">
                                            <i class="fa-solid fa-phone" style="font-size:0.75rem;"></i>
                                            <?php echo htmlspecialchars($plot['purchaser_alt_mobile']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Document Number - Separate column -->
                                <td style="font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($plot['document_no'] ?: '—'); ?>
                                </td>
                                <!-- Document Date - Separate column -->
                                <td style="font-size: 0.85rem; white-space: nowrap;">
                                    <?php echo !empty($plot['document_date']) ? date('d/m/Y', strtotime($plot['document_date'])) : '—'; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: #eef2f6; color: #475569; border: 1px solid #cbd5e1; text-transform: none;">
                                        <?php echo htmlspecialchars($plot['plot_status'] ?: 'N/A'); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($plot['plot_size_sq_mt'], 2); ?> ચો.મી.</td>
                                <td><?php echo number_format($plot['plot_size_sq_vaar'], 2); ?> ચો.વાર</td>
                                <td><?php echo htmlspecialchars($plot['document_type'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $badgeClass = 'badge-no';
                                    if ($plot['plot_transfer'] === 'YES') {
                                        $badgeClass = 'badge-yes';
                                    } elseif ($plot['plot_transfer'] === 'Not Applicable') {
                                        $badgeClass = 'badge-na';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($plot['plot_transfer']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php 
                                    $statusClass = $plot['status'] === 'Active' ? 'badge-yes' : 'badge-no';
                                    $toggleStatus = $plot['status'] === 'Active' ? 'Deactive' : 'Active';
                                    ?>
                                    <a href="plots.php?action=toggle_status&id=<?php echo $plot['id']; ?>&status=<?php echo $toggleStatus; ?>" 
                                       class="badge <?php echo $statusClass; ?>" 
                                       style="text-decoration: none; cursor: pointer; transition: all 0.2s; display: inline-block;"
                                       title="Click to toggle status"
                                    >
                                        <?php echo htmlspecialchars($plot['status']); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="15" style="text-align: center; color: var(--text-muted); padding: 3rem;">No plots found matching your criteria.</td>
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
            <h2><?php echo $action === 'edit' ? 'Modify Plot Registry' : 'Register New Plot'; ?></h2>
            <span>Complete the fields below according to physical document records</span>
        </div>
        <div>
            <a href="plots.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Registry</a>
        </div>
    </div>
    
    <?php if (!empty($errorMsg)): ?>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?php echo htmlspecialchars($errorMsg); ?>", "danger");
        });
        </script>
    <?php endif; ?>
    
    <form action="plots.php?action=<?php echo $action; ?><?php echo $plotId ? '&id='.$plotId : ''; ?>" method="POST" autocomplete="off" onsubmit="return validateForm()">
        
        <!-- Interactive Plot Size Converter Header (Screenshot 2 Top) -->
        <div class="plot-size-display-header">
            <div class="size-header-item">
                <label for="plot_no">પ્લોટ નંબર (Plot No):</label>
                <input 
                    type="text" 
                    id="plot_no" 
                    name="plot_no" 
                    value="<?php echo htmlspecialchars($plotData['plot_no'] ?? ''); ?>" 
                    required 
                    placeholder="000"
                >
            </div>
            
            <div class="size-header-item">
                <label for="plot_size_sq_mt">ચો.મી. (Sq. Mt.):</label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="plot_size_sq_mt" 
                    name="plot_size_sq_mt" 
                    value="<?php echo htmlspecialchars($plotData['plot_size_sq_mt'] ?? ''); ?>" 
                    required 
                    placeholder="0.00" 
                    oninput="convertSize('mt')"
                >
            </div>
            
            <div class="size-header-item">
                <label for="plot_size_sq_vaar">ચો.વાર (Sq. Vaar):</label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="plot_size_sq_vaar" 
                    name="plot_size_sq_vaar" 
                    value="<?php echo htmlspecialchars($plotData['plot_size_sq_vaar'] ?? ''); ?>" 
                    required 
                    placeholder="0.00" 
                    oninput="convertSize('vaar')"
                >
            </div>

            <div class="size-header-item">
                <label for="plot_status_id">સ્ટેટસ (Status):</label>
                <select id="plot_status_id" name="plot_status_id">
                    <option value="">-- Select Status --</option>
                    <?php foreach ($plotStatuses as $status): ?>
                        <option value="<?php echo $status['id']; ?>" <?php echo (isset($plotData['plot_status_id']) && $plotData['plot_status_id'] == $status['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="size-header-item" style="gap: 6px;">
                <input 
                    type="checkbox" 
                    id="auto_calc" 
                    checked 
                    style="width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; border: 2px solid var(--primary);"
                >
                <label for="auto_calc" style="cursor: pointer; user-select: none; font-size: 0.85rem; color: var(--primary);">ઓટો કન્વર્ઝન (Auto-Convert)</label>
            </div>
        </div>
        
        <div class="dual-panel-grid plots-grid">
            <!-- Column 1: Purchaser (Buyer) & Document Details -->
            <div class="form-card" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;"><i class="fa-solid fa-cart-shopping"></i> Purchaser's Details (ખરીદનારની વિગત)</h3>
                
                <div class="form-group">
                    <label for="purchaser_name">ખરીદનારનું નામ (Purchaser's Full Name)</label>
                    <input 
                        type="text" 
                        id="purchaser_name" 
                        name="purchaser_name" 
                        class="input-control" 
                        value="<?php echo htmlspecialchars($plotData['purchaser_name'] ?? ''); ?>" 
                    >
                </div>
                
                <div class="form-group">
                    <label for="purchaser_address">સરનામું (Address)</label>
                    <textarea 
                        id="purchaser_address" 
                        name="purchaser_address" 
                        class="input-control" 
                        rows="3" 
                    ><?php echo htmlspecialchars($plotData['purchaser_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="purchaser_city">શહેર (City)</label>
                    <input 
                        type="text" 
                        id="purchaser_city" 
                        name="purchaser_city" 
                        class="input-control" 
                        value="<?php echo htmlspecialchars($plotData['purchaser_city'] ?? ''); ?>" 
                    >
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="purchaser_mobile">મોબાઈલ નંબર (Mobile Number)</label>
                        <input 
                            type="text" 
                            id="purchaser_mobile" 
                            name="purchaser_mobile" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($plotData['purchaser_mobile'] ?? ''); ?>" 
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="purchaser_alt_mobile">વૈકલ્પિક મોબાઈલ નંબર (Alternative Mobile No)</label>
                        <input 
                            type="text" 
                            id="purchaser_alt_mobile" 
                            name="purchaser_alt_mobile" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($plotData['purchaser_alt_mobile'] ?? ''); ?>"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="purchaser_co">સી/ઓ (C/o.)</label>
                    <input 
                        type="text" 
                        id="purchaser_co" 
                        name="purchaser_co" 
                        class="input-control" 
                        value="<?php echo htmlspecialchars($plotData['purchaser_co'] ?? ''); ?>"
                    >
                </div>
                
                <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-top: 1rem;"><i class="fa-solid fa-file-invoice"></i> Document Ledger (દસ્તાવેજ રજીસ્ટર)</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="document_no">દસ્તાવેજ નંબર (Doc No)</label>
                        <input 
                            type="text" 
                            id="document_no" 
                            name="document_no" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($plotData['document_no'] ?? ''); ?>"
                        >
                    </div>
                    <div class="form-group">
                        <label for="document_date">દસ્તાવેજ તારીખ (Doc Date)</label>
                        <input 
                            type="date" 
                            id="document_date" 
                            name="document_date" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($plotData['document_date'] ?? ''); ?>"
                        >
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="document_type_id">દસ્તાવેજનો પ્રકાર (Document Type)</label>
                        <select id="document_type_id" name="document_type_id" class="input-control">
                            <option value="">-- Select Type --</option>
                            <?php foreach ($docTypes as $type): ?>
                                <option 
                                    value="<?php echo $type['id']; ?>" 
                                    <?php echo (isset($plotData['document_type_id']) && $plotData['document_type_id'] == $type['id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="plot_transfer">પ્લોટ ટ્રાન્સફર (Plot Transfer?)</label>
                        <select id="plot_transfer" name="plot_transfer" class="input-control" style="font-weight: 700;">
                            <option value="NO" <?php echo (isset($plotData['plot_transfer']) && $plotData['plot_transfer'] === 'NO') ? 'selected' : ''; ?>>NO</option>
                            <option value="YES" <?php echo (isset($plotData['plot_transfer']) && $plotData['plot_transfer'] === 'YES') ? 'selected' : ''; ?>>YES</option>
                            <option value="Not Applicable" <?php echo (isset($plotData['plot_transfer']) && $plotData['plot_transfer'] === 'Not Applicable') ? 'selected' : ''; ?>>Not Applicable</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Column 2: Seller Details & Internal Notes -->
            <div class="form-card" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;"><i class="fa-solid fa-handshake"></i> Seller's Details (વેચાણ આપનારની વિગત)</h3>
                
                <div class="form-group">
                    <label for="seller_name">વેચાણ આપનારનું નામ (Seller's Full Name)</label>
                    <input 
                        type="text" 
                        id="seller_name" 
                        name="seller_name" 
                        class="input-control" 
                        value="<?php echo htmlspecialchars($plotData['seller_name'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="seller_address">સરનામું (Seller's Address)</label>
                    <textarea 
                        id="seller_address" 
                        name="seller_address" 
                        class="input-control" 
                        rows="3"
                    ><?php echo htmlspecialchars($plotData['seller_address'] ?? ''); ?></textarea>
                </div>

                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="seller_mobile">મોબાઈલ નંબર (Seller's Mobile No)</label>
                        <input 
                            type="text" 
                            id="seller_mobile" 
                            name="seller_mobile" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($plotData['seller_mobile'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="seller_alt_mobile">વૈકલ્પિક મોબાઈલ નંબર (Alternative Mobile No)</label>
                        <input 
                            type="text" 
                            id="seller_alt_mobile" 
                            name="seller_alt_mobile" 
                            class="input-control" 
                            value="<?php echo htmlspecialchars($plotData['seller_alt_mobile'] ?? ''); ?>"
                        >
                    </div>
                </div>

                
                <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-top: 1rem;"><i class="fa-solid fa-note-sticky"></i> Remarks</h3>
                
                <div class="form-group">
                    <label for="note">નોંધ (Office Notes / Remark)</label>
                    <textarea 
                        id="note" 
                        name="note" 
                        class="input-control" 
                        rows="4" 
                        placeholder="Enter office notes here..."
                    ><?php echo htmlspecialchars($plotData['note'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-accent" style="flex: 1; padding: 0.9rem;">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $action === 'edit' ? 'Update Plot Details' : 'Register Plot Record'; ?>
                    </button>
                    <a href="plots.php" class="btn btn-outline" style="flex: 0.5;">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <script>
    // Real-time Size Conversion Logic
    // Formula: 1 Square Meter = 1.19599 Square Yards (Vaar)
    // Formula: 1 Square Vaar = 0.836127 Square Meters
    const SQ_MT_TO_VAAR = 1.196;

    function convertSize(origin) {
        const autoCalcCheck = document.getElementById('auto_calc');
        if (autoCalcCheck && !autoCalcCheck.checked) {
            return; // Manual input mode active
        }

        const mtField = document.getElementById('plot_size_sq_mt');
        const vaarField = document.getElementById('plot_size_sq_vaar');
        
        let mtVal = parseFloat(mtField.value);
        let vaarVal = parseFloat(vaarField.value);
        
        if (origin === 'mt') {
            if (!isNaN(mtVal)) {
                vaarField.value = (mtVal * SQ_MT_TO_VAAR).toFixed(2);
            } else {
                vaarField.value = '';
            }
        } else if (origin === 'vaar') {
            if (!isNaN(vaarVal)) {
                mtField.value = (vaarVal / SQ_MT_TO_VAAR).toFixed(2);
            } else {
                mtField.value = '';
            }
        }
    }

    // Connect checkbox listener to instantly re-align when turned back on
    document.addEventListener('DOMContentLoaded', () => {
        const autoCalcCheck = document.getElementById('auto_calc');
        if (autoCalcCheck) {
            autoCalcCheck.addEventListener('change', function() {
                if (this.checked) {
                    convertSize('mt');
                }
            });
        }
    });
    
    function validateForm() {
        // Enforce Plot Number input is filled in the header
        const plotNo = document.getElementById('plot_no').value.trim();
        if (plotNo === '') {
            alert('Please provide a Plot Number in the header display!');
            document.getElementById('plot_no').focus();
            return false;
        }
        return true;
    }
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
