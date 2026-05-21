<?php
// dashboard.php
// Main landing dashboard displaying stats and recent activities

require_once 'config/db.php';

$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Fetch Statistics
try {
    // 1. Total Plots
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plots");
    $totalPlots = $stmt->fetch()['count'];
    
    // 2. Total Receipts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM receipts");
    $totalReceipts = $stmt->fetch()['count'];
    
    // 3. Total Funds Collected
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM receipts");
    $totalFunds = $stmt->fetch()['total'] ?? 0.00;
    
    // Fetch Recent Plots (Last 5)
    $recentPlotsStmt = $pdo->query("
        SELECT p.*, dt.name as document_type 
        FROM plots p 
        LEFT JOIN document_types dt ON p.document_type_id = dt.id 
        ORDER BY p.id DESC LIMIT 5
    ");
    $recentPlots = $recentPlotsStmt->fetchAll();
    
    // Fetch Recent Receipts (Last 5)
    $recentReceiptsStmt = $pdo->query("
        SELECT r.*, p.plot_no 
        FROM receipts r 
        JOIN plots p ON r.plot_id = p.id 
        ORDER BY r.id DESC LIMIT 5
    ");
    $recentReceipts = $recentReceiptsStmt->fetchAll();
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error fetching statistics: " . htmlspecialchars($e->getMessage()) . "</div>";
    $totalPlots = 0;
    $totalReceipts = 0;
    $totalFunds = 0.00;
    $recentPlots = [];
    $recentReceipts = [];
}
?>

<div class="action-header">
    <div class="action-header-title">
        <h2>System Overview Dashboard</h2>
        <span>Real-time statistics and summary of Green City Association</span>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <a href="plots.php?action=add" class="btn btn-primary"><i class="fa-solid fa-square-plus"></i> Add Plot</a>
        <a href="receipts.php?action=add" class="btn btn-accent"><i class="fa-solid fa-receipt"></i> Issue Receipt</a>
    </div>
</div>

<!-- Statistics Cards Grid -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background-color: #ecfdf5; color: #10b981;">
            <i class="fa-solid fa-map"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Registered Plots</span>
            <span class="stat-value"><?php echo number_format($totalPlots); ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background-color: #eff6ff; color: #3b82f6;">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Receipts Processed</span>
            <span class="stat-value"><?php echo number_format($totalReceipts); ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background-color: #fef3c7; color: #f59e0b;">
            <i class="fa-solid fa-indian-rupee-sign"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Revenue Collected</span>
            <span class="stat-value">Rs. <?php echo number_format($totalFunds, 2); ?></span>
        </div>
    </div>
</div>

<!-- Quick Actions Panel -->
<div class="form-card" style="margin-bottom: 2.5rem; padding: 1.5rem;">
    <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-bolt"></i> Quick Operations Control</h3>
    <div class="dashboard-actions-grid">
        <a href="plots.php" class="btn btn-outline" style="justify-content: flex-start; text-align: left; padding: 1rem;">
            <i class="fa-solid fa-map-location-dot" style="color: var(--primary); font-size: 1.2rem;"></i>
            <div>
                <strong>Plots Registry</strong>
                <div style="font-size:0.75rem; font-weight:normal;">Manage plot sizes, owners</div>
            </div>
        </a>
        <a href="receipts.php" class="btn btn-outline" style="justify-content: flex-start; text-align: left; padding: 1rem;">
            <i class="fa-solid fa-file-invoice-dollar" style="color: var(--accent); font-size: 1.2rem;"></i>
            <div>
                <strong>Receipt Master</strong>
                <div style="font-size:0.75rem; font-weight:normal;">Issue & print fee records</div>
            </div>
        </a>
        <a href="document_types.php" class="btn btn-outline" style="justify-content: flex-start; text-align: left; padding: 1rem;">
            <i class="fa-solid fa-file-signature" style="color: #6366f1; font-size: 1.2rem;"></i>
            <div>
                <strong>Dastavej (Doc) Master</strong>
                <div style="font-size:0.75rem; font-weight:normal;">Configure document types</div>
            </div>
        </a>
        <a href="plot_statuses.php" class="btn btn-outline" style="justify-content: flex-start; text-align: left; padding: 1rem;">
            <i class="fa-solid fa-list-check" style="color: #f59e0b; font-size: 1.2rem;"></i>
            <div>
                <strong>Plot Status Master</strong>
                <div style="font-size:0.75rem; font-weight:normal;">Configure plot status choices</div>
            </div>
        </a>
    </div>
</div>

<!-- Recent Rows Grid -->
<div class="dual-panel-grid dashboard-recent-grid">
    <!-- Recent Plots -->
    <div class="table-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background-color: #fafdfb;">
            <h3 style="font-size: 1.1rem;"><i class="fa-solid fa-map-pin"></i> Recently Added Plots</h3>
            <a href="plots.php" style="font-size: 0.85rem; font-weight: 600;"><i class="fa-solid fa-eye"></i> View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Plot No</th>
                        <th>Purchaser Name</th>
                        <th>Size (Sq.Vaar)</th>
                        <th>Transfer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentPlots) > 0): ?>
                        <?php foreach ($recentPlots as $plot): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($plot['plot_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($plot['purchaser_name']); ?></td>
                                <td><?php echo number_format($plot['plot_size_sq_vaar'], 2); ?></td>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No plots registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Receipts -->
    <div class="table-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background-color: #fafdfb;">
            <h3 style="font-size: 1.1rem;"><i class="fa-solid fa-receipt"></i> Recent Issued Receipts</h3>
            <a href="receipts.php" style="font-size: 0.85rem; font-weight: 600;"><i class="fa-solid fa-eye"></i> View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Plot No</th>
                        <th>Name</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentReceipts) > 0): ?>
                        <?php foreach ($recentReceipts as $receipt): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($receipt['receipt_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($receipt['plot_no']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['name']); ?></td>
                                <td><strong>Rs. <?php echo number_format($receipt['total_amount'], 2); ?></strong></td>
                                <td>
                                    <a href="print_receipt.php?id=<?php echo $receipt['id']; ?>" class="btn btn-outline btn-sm" target="_blank" title="Print Receipt">
                                        <i class="fa-solid fa-print"></i> Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No receipts generated yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
