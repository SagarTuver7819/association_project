<?php
// print_receipt.php
// Perfectly formats and prints a receipt replicating Screenshot 1

require_once 'config/db.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$receiptId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$receiptId) {
    die("Error: No receipt ID specified.");
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, p.plot_no, p.plot_size_sq_vaar 
        FROM receipts r
        JOIN plots p ON r.plot_id = p.id
        WHERE r.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $receiptId]);
    $receipt = $stmt->fetch();
    
    if (!$receipt) {
        die("Error: Receipt not found.");
    }
    
    // Fetch dynamic maintenance fees
    $maintStmt = $pdo->prepare("
        SELECT rmf.*, mfm.period_name 
        FROM receipt_maintenance_fees rmf
        JOIN maintenance_fees_master mfm ON rmf.maintenance_fee_id = mfm.id
        WHERE rmf.receipt_id = :receipt_id
        ORDER BY rmf.id ASC
    ");
    $maintStmt->execute(['receipt_id' => $receiptId]);
    $maintFees = $maintStmt->fetchAll();
    
    // Fetch dynamic other fees
    $otherStmt = $pdo->prepare("
        SELECT rof.*, ofm.fee_name 
        FROM receipt_other_fees rof
        JOIN other_fees_master ofm ON rof.other_fee_id = ofm.id
        WHERE rof.receipt_id = :receipt_id
        ORDER BY rof.id ASC
    ");
    $otherStmt->execute(['receipt_id' => $receiptId]);
    $otherFees = $otherStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt No. <?php echo htmlspecialchars($receipt['receipt_no']); ?> - Print</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap');
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: #525659;
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
            color: #000000;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        /* Floating Toolbar */
        .toolbar {
            background: #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 10px 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            width: 100%;
            max-width: 900px;
            align-items: center;
            justify-content: space-between;
        }
        
        .toolbar-title {
            font-family: sans-serif;
            font-size: 14px;
            font-weight: bold;
            color: #1e3f20;
        }
        
        .btn {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: sans-serif;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-print {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #059669;
        }
        
        .btn-back {
            background-color: #f3f4f6;
            color: #1f2937;
            border: 1px solid #d1d5db;
        }
        .btn-back:hover {
            background-color: #e5e7eb;
        }
        
        /* Paper Page container */
        .paper-receipt {
            background-color: #ffffff;
            width: 100%;
            max-width: 900px;
            min-height: 550px;
            border: 3px double #000000;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
        }
        
        /* Receipt Header Title */
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .receipt-title-box {
            border: 2px solid #000000;
            border-radius: 10px;
            padding: 6px 30px;
            display: inline-block;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        
        /* Meta Grid Layout */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 40px;
            margin-bottom: 20px;
        }
        
        .meta-row-full {
            grid-column: 1 / -1;
            display: flex;
            align-items: baseline;
            margin-bottom: 12px;
        }
        
        .meta-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 12px;
        }
        
        .meta-label {
            font-weight: bold;
            font-size: 15px;
            flex-shrink: 0;
            width: 220px;
        }
        
        .meta-value {
            flex-grow: 1;
            border-bottom: 1px dotted #000000;
            padding-left: 10px;
            font-size: 16px;
            font-weight: bold;
            min-height: 20px;
        }
        
        /* Particulars Ledger Table */
        .particulars-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .particulars-table th, .particulars-table td {
            border: 1px solid #000000;
            padding: 6px 10px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .particulars-table th {
            text-align: center;
            background-color: #f2f2f2;
        }
        
        .col-half {
            width: 50%;
            vertical-align: top;
            padding: 0 !important;
        }
        
        .inner-table {
            width: 100%;
            border-collapse: collapse;
        }
        .inner-table td {
            border: none;
            border-bottom: 1px dashed #cccccc;
            padding: 6px 8px;
        }
        
        .inner-table tr:last-child td {
            border-bottom: none;
        }
        
        .amt-col {
            text-align: right;
            width: 120px;
            border-left: 1px solid #000000 !important;
        }
        
        .text-indent {
            padding-left: 20px !important;
        }
        
        /* Total Amount Row */
        .total-row {
            border: 2px solid #000000;
            border-top: none;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 8px 20px;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 25px;
        }
        
        /* Footnotes */
        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            margin-top: 25px;
        }
        
        .footer-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .received-by-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 90px;
        }
        
        .signature-line {
            width: 220px;
            border-top: 1px solid #000000;
            text-align: center;
            padding-top: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        
        /* Print Styles */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .paper-receipt {
                box-shadow: none !important;
                border: 3px double #000000 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Utility Bar -->
    <div class="toolbar">
        <span class="toolbar-title"><i class="fa-solid fa-mountain-city"></i> Green City - Receipt Printing Control</span>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-print"><i class="fa-solid fa-print"></i> Print Receipt</button>
            <a href="receipts.php" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Receipts</a>
            <a href="dashboard.php" class="btn btn-back"><i class="fa-solid fa-house"></i> Home</a>
        </div>
    </div>

    <!-- Official Printed Document Body -->
    <div class="paper-receipt">
        
        <!-- Center Double bordered title -->
        <div class="receipt-header">
            <div class="receipt-title-box">
                :: R E C E I P T ::
            </div>
        </div>
        
        <!-- Metadata fields -->
        <div class="meta-grid">
            <div class="meta-row">
                <span class="meta-label">Receipt No. :</span>
                <span class="meta-value"><?php echo htmlspecialchars($receipt['receipt_no']); ?></span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Date :</span>
                <span class="meta-value"><?php echo date('d-m-Y', strtotime($receipt['receipt_date'])); ?></span>
            </div>
            
            <div class="meta-row">
                <span class="meta-label">Plot No. :</span>
                <span class="meta-value"><?php echo htmlspecialchars($receipt['plot_no']); ?></span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Mobile No. :</span>
                <span class="meta-value"><?php echo htmlspecialchars($receipt['mobile_no']); ?></span>
            </div>
            
            <div class="meta-row">
                <span class="meta-label">Plot Size (Sq.Vaar) :</span>
                <span class="meta-value"><?php echo number_format($receipt['plot_size_sq_vaar'], 2); ?></span>
            </div>
            <div class="meta-row">
                <span class="meta-label">City :</span>
                <span class="meta-value"><?php echo htmlspecialchars($receipt['city']); ?></span>
            </div>
            
            <div class="meta-row-full">
                <span class="meta-label">Name :</span>
                <span class="meta-value"><?php echo htmlspecialchars($receipt['name']); ?></span>
            </div>
        </div>
        
        <!-- Ledger split layout -->
        <?php
        $maxRows = max(count($maintFees), count($otherFees));
        if ($maxRows < 5) {
            $maxRows = 5; // Maintain a minimum posture height for elegant aesthetics
        }
        ?>
        <table class="particulars-table">
            <thead>
                <tr>
                    <th style="width: 50%;">PARTICULARS</th>
                    <th style="width: 50%;">PARTICULARS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- LEFT COLUMN: MAINTENANCE CHARGES -->
                    <td class="col-half">
                        <table class="inner-table">
                            <tr>
                                <td colspan="2" style="text-align: center; border-bottom: 2px solid #000000; font-size:12px; font-weight: bold;">MAINTENANCE CHARGES</td>
                            </tr>
                            <?php for ($i = 0; $i < $maxRows; $i++): ?>
                                <?php if (isset($maintFees[$i])): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($maintFees[$i]['period_name']); ?></td>
                                        <td class="amt-col"><?php echo number_format($maintFees[$i]['amount'], 2); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td class="amt-col">&nbsp;</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </table>
                    </td>
                    
                    <!-- RIGHT COLUMN: OTHER PARTICULARS -->
                    <td class="col-half" style="border-left: 2px solid #000000;">
                        <table class="inner-table">
                            <tr>
                                <td colspan="2" style="text-align: center; border-bottom: 2px solid #000000; font-size:12px; font-weight: bold;">OTHER FEES & CHARGES</td>
                            </tr>
                            <?php for ($i = 0; $i < $maxRows; $i++): ?>
                                <?php if (isset($otherFees[$i])): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($otherFees[$i]['fee_name']); ?></td>
                                        <td class="amt-col"><?php echo number_format($otherFees[$i]['amount'], 2); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td class="amt-col">&nbsp;</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Summary Total line -->
        <div class="total-row">
            <span style="letter-spacing: 2px; margin-right: 15px;">TOTAL Rs...</span>
            <span style="border-bottom: 2px double #000000; padding: 2px 10px; font-size: 18px;"><?php echo number_format($receipt['total_amount'], 2); ?></span>
        </div>
        
        <!-- Footer Meta & Signature sections -->
        <div class="footer-grid">
            <div class="footer-details">
                <div style="display: flex;">
                    <span style="font-weight: bold; width: 180px;">Mode of Payment :</span>
                    <span style="border-bottom: 1px dotted #000000; flex-grow: 1; font-weight: bold;"><?php echo htmlspecialchars($receipt['payment_mode']); ?></span>
                </div>
                <div style="display: flex; margin-top: 10px;">
                    <span style="font-weight: bold; width: 180px;">Remark :</span>
                    <span style="border-bottom: 1px dotted #000000; flex-grow: 1; font-weight: bold;"><?php echo htmlspecialchars($receipt['remark'] ?: '-'); ?></span>
                </div>
            </div>
            
            <div class="received-by-section">
                <div class="signature-line">
                    RECEIVED BY<br>
                    <span style="font-size: 11px; font-weight: normal; color: #444444; margin-top: 3px; display: inline-block;">
                        (<?php echo htmlspecialchars($receipt['received_by']); ?>)
                    </span>
                </div>
            </div>
        </div>
        
    </div>

</body>
</html>
