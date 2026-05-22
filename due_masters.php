<?php
// due_masters.php
// Due Master registry and dynamic calculations corresponding to user request

require_once 'config/db.php';

$pageTitle = 'Due Master';


$successMsg = '';
$errorMsg = '';
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$dueId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle Delete Request
if ($action === 'delete' && $dueId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM due_masters WHERE id = :id");
        $stmt->execute(['id' => $dueId]);
        $_SESSION['success_msg'] = "Due Master record deleted successfully!";
        header("Location: due_masters.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Database error deleting record: " . htmlspecialchars($e->getMessage());
        $action = 'list';
    }
}

// Redirect alert messages on redirection
if (isset($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $errorMsg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Handle Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $plotId = intval($_POST['plot_id'] ?? 0);
    $startDate1 = $_POST['start_date_1'] ?? '1999-04-01';
    $endDate1 = $_POST['end_date_1'] ?? '2022-03-31';
    $years1 = intval($_POST['years_1'] ?? 23);
    $rate1 = floatval($_POST['rate_1'] ?? 2.00);
    
    // Get plot details to calculate amounts
    $plotStmt = $pdo->prepare("SELECT plot_size_sq_vaar FROM plots WHERE id = :id");
    $plotStmt->execute(['id' => $plotId]);
    $plot = $plotStmt->fetch();
    
    if ($plot && $plotId > 0) {
        $size = floatval($plot['plot_size_sq_vaar']);
        
        // Period 1 amount formula: round(Size * Years * Rate)
        $amount1 = round($size * $years1 * $rate1);
        
        // Post-2022 years details
        $postYears = $_POST['post_years'] ?? []; // Array of year keys like '2022-23', '2023-24'
        
        $totalAmount = $amount1;
        $yearCalculations = [];
        
        foreach ($postYears as $fy) {
            $rate = 5.00; // post-2022 rate is Rs. 5
            $amt = round($size * 1 * $rate);
            $totalAmount += $amt;
            $yearCalculations[] = [
                'fy' => $fy,
                'rate' => $rate,
                'amount' => $amt
            ];
        }
        
        try {
            $pdo->beginTransaction();
            
            if ($action === 'edit' && $dueId) {
                // Update parent record
                $stmt = $pdo->prepare("
                    UPDATE due_masters SET 
                        plot_id = :plot_id,
                        start_date_1 = :start_date,
                        end_date_1 = :end_date,
                        years_1 = :years,
                        rate_1 = :rate,
                        amount_1 = :amount,
                        total_amount = :total
                    WHERE id = :id
                ");
                $stmt->execute([
                    'plot_id' => $plotId,
                    'start_date' => $startDate1,
                    'end_date' => $endDate1,
                    'years' => $years1,
                    'rate' => $rate1,
                    'amount' => $amount1,
                    'total' => $totalAmount,
                    'id' => $dueId
                ]);
                
                // Clear existing post-2022 years to rewrite them cleanly
                $pdo->prepare("DELETE FROM due_master_years WHERE due_master_id = :id")->execute(['id' => $dueId]);
            } else {
                // Insert parent record
                // Check if due master already exists for this plot
                $checkStmt = $pdo->prepare("SELECT id FROM due_masters WHERE plot_id = :plot_id");
                $checkStmt->execute(['plot_id' => $plotId]);
                if ($checkStmt->fetch()) {
                    throw new Exception("Due master record already exists for this Plot Number!");
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO due_masters (
                        plot_id, start_date_1, end_date_1, years_1, rate_1, amount_1, total_amount
                    ) VALUES (
                        :plot_id, :start_date, :end_date, :years, :rate, :amount, :total
                    )
                ");
                $stmt->execute([
                    'plot_id' => $plotId,
                    'start_date' => $startDate1,
                    'end_date' => $endDate1,
                    'years' => $years1,
                    'rate' => $rate1,
                    'amount' => $amount1,
                    'total' => $totalAmount
                ]);
                $dueId = $pdo->lastInsertId();
            }
            
            // Insert dynamic post-2022 years
            if (!empty($yearCalculations)) {
                $yearStmt = $pdo->prepare("
                    INSERT INTO due_master_years (
                        due_master_id, financial_year, rate, amount
                    ) VALUES (
                        :due_master_id, :fy, :rate, :amount
                    )
                ");
                foreach ($yearCalculations as $yc) {
                    $yearStmt->execute([
                        'due_master_id' => $dueId,
                        'fy' => $yc['fy'],
                        'rate' => $yc['rate'],
                        'amount' => $yc['amount']
                    ]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success_msg'] = ($action === 'edit') ? "Due Master updated successfully!" : "Due Master created successfully!";
            header("Location: due_masters.php");
            exit;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = $e->getMessage();
        }
    } else {
        $errorMsg = "Please select a valid plot.";
    }
}

require_once 'includes/header.php';
?>
<style>
.due-master-table {
    border-collapse: collapse;
    width: 100%;
}
.due-master-table th, 
.due-master-table td {
    padding: 0.9rem 1rem !important;
    border: 1px solid #cbd5e1 !important;
    vertical-align: middle;
}
.due-master-table th {
    background-color: #fef08a !important; /* Vibrant custom yellow matching the Excel */
    color: #1e293b !important;
    font-weight: 800;
    font-size: 0.85rem;
    text-align: center;
}
.due-master-table tr:hover td {
    background-color: #fefcbf !important; /* Subtle yellow highlight hover */
}
.post-year-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fafdfb;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0.75rem 1rem;
    transition: var(--transition);
}
.post-year-row:hover {
    border-color: var(--accent);
    background-color: #f0fdf4;
}
</style>
<?php

// Fetch all registered plots for the auto-fill dropdown
$plotsList = [];
try {
    // If adding, only show plots that don't have a due master, or if editing, include the current plot
    if ($action === 'add') {
        $plotsStmt = $pdo->query("
            SELECT id, plot_no, plot_size_sq_vaar, purchaser_name 
            FROM plots 
            WHERE id NOT IN (SELECT plot_id FROM due_masters)
            ORDER BY CAST(plot_no AS UNSIGNED), plot_no ASC
        ");
    } else {
        $plotsStmt = $pdo->query("
            SELECT id, plot_no, plot_size_sq_vaar, purchaser_name 
            FROM plots 
            ORDER BY CAST(plot_no AS UNSIGNED), plot_no ASC
        ");
    }
    $plotsList = $plotsStmt->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading plots: " . $e->getMessage();
}

// Edit Mode - Load record details
$dueData = [];
$existingYears = [];
if ($action === 'edit' && $dueId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM due_masters WHERE id = :id");
        $stmt->execute(['id' => $dueId]);
        $dueData = $stmt->fetch();
        if (!$dueData) {
            $_SESSION['error_msg'] = "Due Master record not found.";
            header("Location: due_masters.php");
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM due_master_years WHERE due_master_id = :id ORDER BY financial_year ASC");
        $stmt->execute(['id' => $dueId]);
        $existingYears = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Error loading due master details: " . $e->getMessage();
    }
}
?>

<?php if ($action === 'list'): ?>
    <!-- -------------------------------- LIST VIEW -------------------------------- -->
    <?php
    $search = trim($_GET['search'] ?? '');
    $dueRecords = [];
    try {
        // Query to load due masters and aggregate post-2022 years
        // To build columns dynamically corresponding to the user's Excel sheet
        $queryStr = "
            SELECT dm.*, p.plot_no, p.plot_size_sq_vaar, p.purchaser_name
            FROM due_masters dm
            JOIN plots p ON dm.plot_id = p.id
        ";
        if (!empty($search)) {
            $queryStr .= " WHERE p.plot_no LIKE :search OR p.purchaser_name LIKE :search ";
            $stmt = $pdo->prepare($queryStr . " ORDER BY CAST(p.plot_no AS UNSIGNED), p.plot_no ASC");
            $stmt->execute(['search' => "%$search%"]);
        } else {
            $stmt = $pdo->query($queryStr . " ORDER BY CAST(p.plot_no AS UNSIGNED), p.plot_no ASC");
        }
        $dueRecords = $stmt->fetchAll();
        
        // Fetch all mapped post-2022 years for all due masters
        $yearsStmt = $pdo->query("SELECT * FROM due_master_years");
        $allYears = $yearsStmt->fetchAll();
        
        // Group years by due_master_id
        $groupedYears = [];
        foreach ($allYears as $y) {
            $groupedYears[$y['due_master_id']][$y['financial_year']] = $y['amount'];
        }
    } catch (PDOException $e) {
        $errorMsg = "Error fetching due masters: " . $e->getMessage();
    }
    ?>
    
    <div class="action-header">
        <div class="action-header-title">
            <h2>Due Master Dashboard</h2>
            <span>Track, calculate, and record dynamic plot maintenance dues over the years</span>
        </div>
        <div>
            <a href="due_masters.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Due Master Entry</a>
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
    
    <!-- Search Box -->
    <div class="form-card" style="padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
        <form action="due_masters.php" method="GET" style="display: flex; gap: 10px;">
            <input type="hidden" name="action" value="list">
            <input 
                type="text" 
                name="search" 
                class="input-control" 
                placeholder="Search by Plot No or Owner Name..." 
                value="<?php echo htmlspecialchars($search); ?>"
            >
            <button type="submit" class="btn btn-accent"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if (!empty($search)): ?>
                <a href="due_masters.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Premium Grid Table matching the excel sheet style -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="due-master-table">
                <thead>
                    <tr style="background-color: #fef08a;">
                        <th style="width: 80px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center;">Plot No.</th>
                        <th style="width: 120px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: right;">Size (Sq.Vaar)</th>
                        <th style="color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center; font-size: 0.85rem;">
                            01/04/1999 To 31/03/2022<br>
                            <span style="font-weight: normal; font-size: 0.75rem; color: #475569;">Total 23 Years x Rs.2/- Per Vaar</span>
                        </th>
                        <th style="width: 110px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center; font-size: 0.85rem;">2022-23<br><span style="font-weight: normal; font-size: 0.75rem; color: #475569;">(Rs.5/- Per Vaar)</span></th>
                        <th style="width: 110px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center; font-size: 0.85rem;">2023-24<br><span style="font-weight: normal; font-size: 0.75rem; color: #475569;">(Rs.5/- Per Vaar)</span></th>
                        <th style="width: 110px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center; font-size: 0.85rem;">2024-25<br><span style="font-weight: normal; font-size: 0.75rem; color: #475569;">(Rs.5/- Per Vaar)</span></th>
                        <th style="width: 110px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center; font-size: 0.85rem;">2025-26<br><span style="font-weight: normal; font-size: 0.75rem; color: #475569;">(Rs.5/- Per Vaar)</span></th>
                        <th style="width: 110px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center; font-size: 0.85rem;">2026-27<br><span style="font-weight: normal; font-size: 0.75rem; color: #475569;">(Rs.5/- Per Vaar)</span></th>
                        <th style="width: 120px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: right;">Total (Rs.)</th>
                        <th style="width: 180px; color: #1e293b; font-weight: 800; border: 1px solid #e2e8f0; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($dueRecords) > 0): ?>
                        <?php foreach ($dueRecords as $row): ?>
                            <?php
                            $dmId = $row['id'];
                            $rowYears = isset($groupedYears[$dmId]) ? $groupedYears[$dmId] : [];
                            
                            // Check if dates differ from standard 23 years, to customize display header if needed
                            $period1Text = number_format($row['amount_1']) . " (" . $row['years_1'] . " yrs)";
                            ?>
                            <tr>
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 700;">
                                    <span style="background: var(--accent-light); padding: 4px 10px; border-radius: 4px; color: var(--primary); font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($row['plot_no']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; border: 1px solid #e2e8f0; font-weight: 700; color: #334155;">
                                    <?php echo number_format($row['plot_size_sq_vaar'], 2); ?>
                                </td>
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 600; color: #0f172a; background-color: #fafaf9;">
                                    <?php
                                    // Match format in excel: Size x Years x Rate = Total
                                    echo number_format($row['plot_size_sq_vaar'], 2) . " &times; " . $row['years_1'] . " &times; " . number_format($row['rate_1'], 0) . " = " . number_format($row['amount_1']);
                                    ?>
                                </td>
                                <!-- Dynamic Post-2022 Years columns -->
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 700; color: var(--primary);">
                                    <?php echo isset($rowYears['2022-23']) ? number_format($rowYears['2022-23']) : '0'; ?>
                                </td>
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 700; color: var(--primary);">
                                    <?php echo isset($rowYears['2023-24']) ? number_format($rowYears['2023-24']) : '0'; ?>
                                </td>
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 700; color: var(--primary);">
                                    <?php echo isset($rowYears['2024-25']) ? number_format($rowYears['2024-25']) : '0'; ?>
                                </td>
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 700; color: var(--primary);">
                                    <?php echo isset($rowYears['2025-26']) ? number_format($rowYears['2025-26']) : '0'; ?>
                                </td>
                                <td style="text-align: center; border: 1px solid #e2e8f0; font-weight: 700; color: var(--primary);">
                                    <?php echo isset($rowYears['2026-27']) ? number_format($rowYears['2026-27']) : '0'; ?>
                                </td>
                                <td style="text-align: right; border: 1px solid #e2e8f0; font-weight: 800; color: #15803d; font-size: 1.1rem; background-color: #f0fdf4;">
                                    <?php echo number_format($row['total_amount']); ?>
                                </td>
                                <td style="text-align: center; border: 1px solid #e2e8f0;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="due_masters.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a 
                                            href="due_masters.php?action=delete&id=<?php echo $row['id']; ?>" 
                                            class="btn btn-danger btn-sm"
                                            onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete the due calculations for plot <?php echo htmlspecialchars($row['plot_no']); ?>?'); return false;"
                                        >
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 4rem;">
                                <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                                No Due Master entries recorded yet. Click the button above to add one.
                            </td>
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
            <h2><?php echo $action === 'edit' ? 'Edit Due Master Entry' : 'Create Due Master Entry'; ?></h2>
            <span>Configure starting dates, post-2022 financial periods, and dynamic charges.</span>
        </div>
        <div>
            <a href="due_masters.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Ledger</a>
        </div>
    </div>
    
    <?php if (!empty($errorMsg)): ?>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?php echo htmlspecialchars($errorMsg); ?>", "danger");
        });
        </script>
    <?php endif; ?>
    
    <form id="due-master-form" action="due_masters.php?action=<?php echo $action; ?><?php echo $dueId ? '&id='.$dueId : ''; ?>" method="POST" autocomplete="off">
        <div class="receipt-split">
            <!-- Left Side: Basic parameters and dynamic selectors -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <div class="form-card">
                    <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-mountain-city"></i> Plot Specifications
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="plot_id">Plot No. (પ્લોટ નં.) <span style="color:var(--danger)">*</span></label>
                            <?php if ($action === 'edit'): ?>
                                <!-- Lock plot edit to protect unique mapping -->
                                <?php
                                $selectedPlotNo = '';
                                $selectedSize = 0.00;
                                foreach ($plotsList as $p) {
                                    if ($p['id'] == $dueData['plot_id']) {
                                        $selectedPlotNo = $p['plot_no'];
                                        $selectedSize = $p['plot_size_sq_vaar'];
                                        break;
                                    }
                                }
                                ?>
                                <input type="hidden" name="plot_id" id="plot_id" value="<?php echo $dueData['plot_id']; ?>">
                                <input type="text" class="input-control" value="Plot No: <?php echo htmlspecialchars($selectedPlotNo); ?>" readonly style="font-weight: 700; background-color: #f1f5f9;">
                            <?php else: ?>
                                <select id="plot_id" name="plot_id" class="input-control" onchange="autoFillPlotDetails()" required>
                                    <option value="">-- Select Plot --</option>
                                    <?php foreach ($plotsList as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            Plot No: <?php echo htmlspecialchars($p['plot_no']); ?> (<?php echo htmlspecialchars($p['purchaser_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="plot_size">Plot Size (Sq.Vaar) (ચો.વાર)</label>
                            <input 
                                type="text" 
                                id="plot_size" 
                                class="input-control" 
                                value="<?php echo ($action === 'edit') ? number_format($selectedSize, 2) : ''; ?>"
                                placeholder="Auto-filled on plot selection" 
                                readonly 
                                style="font-weight: 700; background-color: #f8fafc;"
                            >
                        </div>
                    </div>
                </div>
                
                <div class="form-card">
                    <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-calendar-days"></i> Period 1: Historical Maintenance Dues
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Initial historical dues window. Calculated automatically at **Rs. 2/- per Sq. Vaar per year**.
                    </p>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="start_date_1">Start Date (શરૂઆતની તારીખ)</label>
                            <input 
                                type="date" 
                                id="start_date_1" 
                                name="start_date_1" 
                                class="input-control" 
                                min="1999-04-01" 
                                max="2022-03-31" 
                                value="<?php echo $dueData['start_date_1'] ?? '1999-04-01'; ?>" 
                                onchange="calculatePeriod1()"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date_1">End Date (અંતિમ તારીખ)</label>
                            <input 
                                type="date" 
                                id="end_date_1" 
                                name="end_date_1" 
                                class="input-control" 
                                min="1999-04-01" 
                                max="2022-03-31" 
                                value="<?php echo $dueData['end_date_1'] ?? '2022-03-31'; ?>" 
                                onchange="calculatePeriod1()"
                            >
                        </div>
                    </div>
                    
                    <div class="form-grid" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label for="years_1">Calculated Years (કુલ વર્ષ)</label>
                            <input 
                                type="number" 
                                id="years_1" 
                                name="years_1" 
                                class="input-control" 
                                value="<?php echo $dueData['years_1'] ?? 23; ?>" 
                                readonly 
                                style="font-weight: 700; background-color: #f8fafc;"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="rate_1">Rate per Sq. Vaar (દર રૂ.)</label>
                            <input 
                                type="number" 
                                id="rate_1" 
                                name="rate_1" 
                                class="input-control" 
                                value="<?php echo $dueData['rate_1'] ?? 2.00; ?>" 
                                readonly 
                                style="font-weight: 700; background-color: #f8fafc;"
                            >
                        </div>
                    </div>
                </div>
                
                <div class="form-card">
                    <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-calendar-plus"></i> Period 2: Post-2022 Financial Years
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                        Subsequent years after 31/03/2022. Calculated dynamically at **Rs. 5/- per Sq. Vaar per year**.
                    </p>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 1.5rem;">
                        <select id="financial-year-picker" class="input-control" style="flex: 1;">
                            <option value="">-- Choose Financial Year to Add --</option>
                            <option value="2022-23">2022-23 (Rs. 5/- Per Vaar)</option>
                            <option value="2023-24">2023-24 (Rs. 5/- Per Vaar)</option>
                            <option value="2024-25">2024-25 (Rs. 5/- Per Vaar)</option>
                            <option value="2025-26">2025-26 (Rs. 5/- Per Vaar)</option>
                            <option value="2026-27">2026-27 (Rs. 5/- Per Vaar)</option>
                            <option value="2027-28">2027-28 (Rs. 5/- Per Vaar)</option>
                            <option value="2028-29">2028-29 (Rs. 5/- Per Vaar)</option>
                            <option value="2029-30">2029-30 (Rs. 5/- Per Vaar)</option>
                        </select>
                        <button type="button" class="btn btn-accent" id="btn-add-year" style="min-width: auto; padding: 0.8rem 1.2rem;">
                            <i class="fa-solid fa-plus"></i> Add Year
                        </button>
                    </div>
                    
                    <!-- Container for Post-2022 Rows -->
                    <div id="post-years-container" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php if ($action === 'edit'): ?>
                            <?php foreach ($existingYears as $ey): ?>
                                <div class="fee-row-item post-year-row" data-fy="<?php echo htmlspecialchars($ey['financial_year']); ?>">
                                    <span style="font-weight: 700; font-size: 0.95rem; color: #1e293b;"><i class="fa-regular fa-calendar"></i> FY <?php echo htmlspecialchars($ey['financial_year']); ?> (Rs. 5/- per Sq. Vaar)</span>
                                    <input type="hidden" name="post_years[]" value="<?php echo htmlspecialchars($ey['financial_year']); ?>">
                                    <span class="year-calculated-amount-label" style="font-weight: 800; color: var(--primary); font-size: 1.05rem;">Rs. <?php echo number_format($ey['amount']); ?></span>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.post-year-row').remove(); calculateGrandTotal();" style="padding: 6px 10px; min-width: auto;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Mathematics Live Board -->
            <div class="form-card" style="height: fit-content; position: sticky; top: 2rem;">
                <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;">
                    <i class="fa-solid fa-calculator"></i> Live Calculations Summary
                </h3>
                
                <div style="background-color: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.5rem;">
                        <span style="font-size: 0.9rem; color: var(--text-muted);">Plot Area (Sq. Vaar):</span>
                        <span id="label-plot-area" style="font-weight: 700; color: var(--text);">0.00</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.5rem;">
                        <span style="font-size: 0.9rem; color: var(--text-muted);">Period 1 Dues (@ Rs. 2):</span>
                        <span id="label-period1-dues" style="font-weight: 700; color: var(--text);">Rs. 0</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.5rem;">
                        <span style="font-size: 0.9rem; color: var(--text-muted);">Period 2 Dues (@ Rs. 5):</span>
                        <span id="label-period2-dues" style="font-weight: 700; color: var(--text);">Rs. 0</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                        <span style="font-size: 1.05rem; font-weight: 800; color: #0f172a;">Grand Total:</span>
                        <span id="label-grand-total" style="font-size: 1.4rem; font-weight: 900; color: #16a34a;">Rs. 0</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.9rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Due Sheet
                    </button>
                    <a href="due_masters.php" class="btn btn-outline">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <script>
    // Embed plot registry in JS
    const plotsData = <?php echo json_encode($plotsList); ?>;
    
    function autoFillPlotDetails() {
        const plotSelect = document.getElementById('plot_id');
        if (!plotSelect) return;
        
        const selectedPlotId = parseInt(plotSelect.value);
        const sizeInput = document.getElementById('plot_size');
        const areaLabel = document.getElementById('label-plot-area');
        
        if (!isNaN(selectedPlotId)) {
            const plot = plotsData.find(p => parseInt(p.id) === selectedPlotId);
            if (plot) {
                const sqVaar = parseFloat(plot.plot_size_sq_vaar);
                sizeInput.value = sqVaar.toFixed(2);
                areaLabel.innerText = sqVaar.toFixed(2);
                
                // Trigger recalculation of everything
                calculatePeriod1();
                recalculatePost2022Amounts();
                calculateGrandTotal();
            }
        } else {
            sizeInput.value = '';
            areaLabel.innerText = '0.00';
            document.getElementById('label-period1-dues').innerText = 'Rs. 0';
            document.getElementById('label-period2-dues').innerText = 'Rs. 0';
            document.getElementById('label-grand-total').innerText = 'Rs. 0';
        }
    }
    
    function calculatePeriod1() {
        const sizeInput = document.getElementById('plot_size');
        const startDateVal = document.getElementById('start_date_1').value;
        const endDateVal = document.getElementById('end_date_1').value;
        const yearsInput = document.getElementById('years_1');
        const labelP1 = document.getElementById('label-period1-dues');
        
        if (!sizeInput.value || !startDateVal || !endDateVal) {
            yearsInput.value = 0;
            labelP1.innerText = 'Rs. 0';
            return;
        }
        
        const size = parseFloat(sizeInput.value);
        const startDate = new Date(startDateVal);
        const endDate = new Date(endDateVal);
        
        // Calculate years cleanly
        // Match the user's Excel style: (End Date - Start Date) / 365.25 rounded to nearest integer
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // inclusive of both dates
        
        let years = Math.round(diffDays / 365.25);
        if (years < 0) years = 0;
        
        yearsInput.value = years;
        
        // Rate is Rs. 2
        const rate = 2.00;
        const amount = Math.round(size * years * rate);
        
        labelP1.innerText = 'Rs. ' + amount.toLocaleString('en-IN');
        calculateGrandTotal();
    }
    
    function recalculatePost2022Amounts() {
        const sizeInput = document.getElementById('plot_size');
        if (!sizeInput.value) return;
        
        const size = parseFloat(sizeInput.value);
        const rows = document.querySelectorAll('.post-year-row');
        
        rows.forEach(row => {
            const amountLabel = row.querySelector('.year-calculated-amount-label');
            const amt = Math.round(size * 1 * 5.00); // 1 year, rate is Rs 5
            amountLabel.innerText = 'Rs. ' + amt.toLocaleString('en-IN');
        });
    }
    
    function calculateGrandTotal() {
        const sizeInput = document.getElementById('plot_size');
        if (!sizeInput.value) {
            document.getElementById('label-grand-total').innerText = 'Rs. 0';
            return;
        }
        
        const size = parseFloat(sizeInput.value);
        
        // Period 1
        const years1 = parseInt(document.getElementById('years_1').value) || 0;
        const p1Amount = Math.round(size * years1 * 2.00);
        
        // Period 2 (Post-2022 Years)
        let p2Amount = 0;
        const rows = document.querySelectorAll('.post-year-row');
        rows.forEach(() => {
            p2Amount += Math.round(size * 1 * 5.00);
        });
        
        const grandTotal = p1Amount + p2Amount;
        
        document.getElementById('label-period1-dues').innerText = 'Rs. ' + p1Amount.toLocaleString('en-IN');
        document.getElementById('label-period2-dues').innerText = 'Rs. ' + p2Amount.toLocaleString('en-IN');
        document.getElementById('label-grand-total').innerText = 'Rs. ' + grandTotal.toLocaleString('en-IN');
    }
    
    // Add Post-2022 Financial Year row
    document.getElementById('btn-add-year').addEventListener('click', () => {
        const plotSelect = document.getElementById('plot_id');
        const sizeInput = document.getElementById('plot_size');
        
        if (!plotSelect.value || !sizeInput.value) {
            showToast("Please choose a Plot first!", "danger");
            return;
        }
        
        const selector = document.getElementById('financial-year-picker');
        const fyVal = selector.value;
        if (!fyVal) return;
        
        // Prevent duplicate years
        const existingRows = document.querySelectorAll(`.post-year-row[data-fy="${fyVal}"]`);
        if (existingRows.length > 0) {
            showToast("This financial year has already been added!", "danger");
            return;
        }
        
        const size = parseFloat(sizeInput.value);
        const amount = Math.round(size * 1 * 5.00);
        
        const container = document.getElementById('post-years-container');
        const rowHtml = `
            <div class="fee-row-item post-year-row" data-fy="${fyVal}">
                <span style="font-weight: 700; font-size: 0.95rem; color: #1e293b;"><i class="fa-regular fa-calendar"></i> FY ${fyVal} (Rs. 5/- per Sq. Vaar)</span>
                <input type="hidden" name="post_years[]" value="${fyVal}">
                <span class="year-calculated-amount-label" style="font-weight: 800; color: var(--primary); font-size: 1.05rem;">Rs. ${amount.toLocaleString('en-IN')}</span>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.post-year-row').remove(); calculateGrandTotal();" style="padding: 6px 10px; min-width: auto;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', rowHtml);
        selector.value = ''; // reset selection dropdown
        calculateGrandTotal();
    });
    
    // Run calculations on load
    window.addEventListener('DOMContentLoaded', () => {
        <?php if ($action === 'edit'): ?>
            // Set up initial values
            const areaLabel = document.getElementById('label-plot-area');
            const sizeVal = parseFloat(document.getElementById('plot_size').value);
            areaLabel.innerText = sizeVal.toFixed(2);
            calculatePeriod1();
            calculateGrandTotal();
        <?php endif; ?>
    });
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
