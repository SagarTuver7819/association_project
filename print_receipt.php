<?php
// print_receipt.php
// Perfectly formats and prints a receipt replicating Screenshot 1

require_once 'config/db.php';


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
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Outfit:wght@400;600;800&display=swap');
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: #525659;
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
            color: #000000;
            padding: 30px 10px;
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
            max-width: 210mm;
            align-items: center;
            justify-content: space-between;
        }
        
        .toolbar-title {
            font-family: 'Outfit', sans-serif;
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
            font-family: 'Outfit', sans-serif;
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
        
        /* Paper Page container (A4 Portrait aspect ratio) */
        .paper-receipt {
            background-color: #ffffff;
            width: 210mm;
            min-height: 250mm;
            border: 3px double #000000;
            padding: 15mm 12mm;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
            margin: 0 auto;
        }
        
        /* Red Header Styles */
        .receipt-red-header {
            color: #b01c2e;
            font-family: 'Outfit', 'Inter', 'Segoe UI', Arial, sans-serif;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .reg-no-date {
            text-align: right;
            font-size: 13.5px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .header-main-title {
            text-align: center;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 6px 0;
            border-top: 3px double #b01c2e;
            border-bottom: 3px double #b01c2e;
            margin-bottom: 5px;
        }
        
        .header-address {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            padding-bottom: 6px;
            border-bottom: 2px solid #b01c2e;
            letter-spacing: 0.1px;
        }
        
        /* Receipt Title Header */
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .receipt-title-box {
            border: 2px solid #000000;
            border-radius: 10px;
            padding: 5px 30px;
            display: inline-block;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        
        /* Metadata Grid Layout */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 40px;
            margin-bottom: 25px;
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
        }
        
        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
            font-weight: bold;
        }
        
        .meta-row-full {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            font-size: 15px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .meta-label {
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .meta-value {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .meta-value-center {
            flex-grow: 1;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16px;
        }
        
        /* Particulars Ledger Table */
        .particulars-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
            border-left: 2px solid #000000;
            border-right: 2px solid #000000;
        }
        
        .particulars-table th {
            border-top: 3px double #000000;
            border-bottom: 3px double #000000;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .particulars-table td {
            font-size: 14px;
            font-weight: bold;
            padding: 6px 10px;
        }
        
        .col-particulars {
            width: 38%;
        }
        
        .col-amount {
            width: 12%;
            text-align: right;
        }
        
        .dotted-border-right {
            border-right: 1px dotted #000000 !important;
        }
        
        .vertical-divider {
            border-right: 2px dashed #000000 !important;
        }
        
        /* Footer Grid */
        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            margin-top: 20px;
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
        }
        
        .footer-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .received-by-section {
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            padding-right: 30px;
            padding-bottom: 10px;
        }
        
        .received-by-text {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: bold;
            color: #555555;
            letter-spacing: 0.5px;
        }
        
        /* Print and `@page` setup */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
            }
            .toolbar {
                display: none !important;
            }
            .paper-receipt {
                box-shadow: none !important;
                border: 3px double #000000 !important;
                margin: 0 !important;
                width: 100% !important;
                height: auto !important;
                min-height: auto !important;
                padding: 10mm 8mm !important;
                box-sizing: border-box;
                page-break-inside: avoid;
            }
        }
        
        /* Responsive scaling on screen */
        @media (max-width: 220mm) {
            body {
                padding: 10px;
            }
            .paper-receipt {
                width: 100%;
                height: auto;
                min-height: auto;
                padding: 15px;
            }
            .toolbar {
                width: 100%;
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
        
        <!-- Top Section -->
        <div class="receipt-section-top">
            
            <!-- Red Header Section replicating Screenshot -->
            <div class="receipt-red-header">
                <div class="reg-no-date">
                    Reg.No. : G-10186, Date : 01-08-1998
                </div>
                <div class="header-main-title">
                    GREEN CITY OWNER'S ASSOCIATION
                </div>
                <div class="header-address">
                    Junagadh - Veraval Bye-pass, Near Railway Crossing, Chobari, Junagadh. Email : Greencity10186@gmail.com
                </div>
            </div>
            
            <!-- Center Double bordered title -->
            <div class="receipt-header">
                <div class="receipt-title-box">
                    :: R E C E I P T ::
                </div>
            </div>
            
            <!-- Metadata fields -->
            <div class="meta-grid">
                <div class="meta-row">
                    <span class="meta-label">Recipt No. :</span>
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
                    <span class="meta-value-center"><?php echo htmlspecialchars($receipt['name']); ?></span>
                </div>
            </div>
            
        </div>
        
        <!-- Middle Section (Ledger Split Layout) -->
        <div class="receipt-section-middle">
            <?php
            // Build left side particulars & amounts
            $leftSide = [];
            $leftSide[] = ['name' => 'MAINTENANCE CHARGE', 'amount' => null];
            foreach ($maintFees as $mf) {
                $leftSide[] = ['name' => $mf['period_name'], 'amount' => $mf['amount']];
            }

            // Build right side particulars & amounts
            $rightSide = [];
            foreach ($otherFees as $of) {
                $rightSide[] = ['name' => $of['fee_name'], 'amount' => $of['amount']];
            }

            // Determine maximum rows for alignment (minimum 5 rows for elegant spacing)
            $maxRows = max(count($leftSide), count($rightSide));
            if ($maxRows < 5) {
                $maxRows = 5;
            }
            ?>
            <table class="particulars-table">
                <thead>
                    <tr>
                        <th style="width: 38%; text-align: center; border-right: 1px dotted #000000;">PARTICULARS</th>
                        <th style="width: 12%; text-align: right; border-right: 2px dashed #000000; padding-right: 15px;">AMT.Rs.</th>
                        <th style="width: 38%; text-align: center; border-right: 1px dotted #000000;">PARTICULARS</th>
                        <th style="width: 12%; text-align: right; padding-right: 15px;">AMT.Rs.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < $maxRows; $i++): ?>
                        <tr>
                            <!-- Left Side Column -->
                            <td class="col-particulars dotted-border-right" <?php if (isset($leftSide[$i]) && $leftSide[$i]['name'] === 'MAINTENANCE CHARGE') echo 'style="text-align: center;"'; ?>>
                                <?php echo isset($leftSide[$i]) ? htmlspecialchars($leftSide[$i]['name']) : '&nbsp;'; ?>
                            </td>
                            <td class="col-amount vertical-divider" style="padding-right: 15px;">
                                <?php 
                                if (isset($leftSide[$i]) && $leftSide[$i]['amount'] !== null) {
                                    echo number_format($leftSide[$i]['amount'], 2);
                                } else {
                                    echo '&nbsp;';
                                }
                                ?>
                            </td>
                            
                            <!-- Right Side Column -->
                            <td class="col-particulars dotted-border-right">
                                <?php echo isset($rightSide[$i]) ? htmlspecialchars($rightSide[$i]['name']) : '&nbsp;'; ?>
                            </td>
                            <td class="col-amount" style="padding-right: 15px;">
                                <?php 
                                if (isset($rightSide[$i]) && $rightSide[$i]['amount'] !== null) {
                                    echo number_format($rightSide[$i]['amount'], 2);
                                } else {
                                    echo '&nbsp;';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top: 3px double #000000; border-bottom: 3px double #000000; font-size: 15px; font-weight: bold;">
                        <!-- Left side blank with divider -->
                        <td colspan="2" style="border-right: 2px dashed #000000;">&nbsp;</td>
                        
                        <!-- Right side TOTAL Rs... -->
                        <td style="text-align: right; padding-right: 15px; font-weight: bold; border-right: 1px dotted #000000;">TOTAL Rs...</td>
                        <td style="text-align: right; padding-right: 15px; font-weight: bold; font-size: 16px;"><?php echo number_format($receipt['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Bottom Section -->
        <div class="receipt-section-bottom">
            <!-- Footer Meta & Signature sections -->
            <div class="footer-grid">
                <div class="footer-details">
                    <div style="display: flex; font-size: 14px;">
                        <span style="font-weight: bold; width: 160px;">Mode of Payment :</span>
                        <span style="font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($receipt['payment_mode']); ?></span>
                    </div>
                    <div style="display: flex; font-size: 14px; margin-top: 10px;">
                        <span style="font-weight: bold; width: 160px;">Remark :</span>
                        <span style="font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($receipt['remark'] ?: '-'); ?></span>
                    </div>
                </div>
                
                <div class="received-by-section">
                    <span class="received-by-text">RECEIVED BY</span>
                </div>
            </div>
        </div>
        
    </div>

</body>
</html>
