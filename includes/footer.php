        </main>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <footer style="text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border); background-color: #ffffff; margin-top: auto;" class="no-print">
            &copy; <?php echo date('Y'); ?> <strong>Green City Association Management System</strong>. Created for Premium Plot and Member Tracking.
        </footer>
        <?php endif; ?>
    </div>

    <!-- Custom Theme-based Delete Confirmation Dialog Modal -->
    <div id="deleteConfirmModal" class="custom-modal">
        <div class="custom-modal-backdrop" onclick="closeDeleteModal()"></div>
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <i class="fa-solid fa-circle-exclamation modal-icon"></i>
                <h3>Confirm Deletion</h3>
            </div>
            <div class="custom-modal-body">
                <p id="deleteModalMessage">Are you sure you want to delete this record?</p>
            </div>
            <div class="custom-modal-footer">
                <button class="btn btn-outline" onclick="closeDeleteModal()"><i class="fa-solid fa-xmark"></i> Cancel</button>
                <a id="deleteModalConfirmBtn" href="#" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Yes, Delete</a>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container no-print"></div>

    <!-- Toast & Custom Dialog Script Engine -->
    <script>
    // Toast Engine
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconClass = 'fa-circle-check';
        if (type === 'danger' || type === 'error') iconClass = 'fa-circle-exclamation';
        else if (type === 'warning') iconClass = 'fa-triangle-exclamation';
        
        toast.innerHTML = `
            <i class="fa-solid ${iconClass}"></i>
            <div class="toast-message">${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        container.appendChild(toast);
        
        // Auto remove toast after 4.5 seconds
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    // Modal Deletion Engine
    let confirmRedirectUrl = '';
    function triggerCustomConfirmDelete(url, message = 'Are you sure you want to delete this record?') {
        confirmRedirectUrl = url;
        document.getElementById('deleteModalMessage').innerText = message;
        document.getElementById('deleteModalConfirmBtn').href = url;
        
        const modal = document.getElementById('deleteConfirmModal');
        modal.style.display = 'flex';
        // Small delay to trigger smooth scale transition
        setTimeout(() => {
            modal.classList.add('active');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 250);
    }

    // PHP Session success redirection alert interceptor
    document.addEventListener('DOMContentLoaded', () => {
        <?php if (isset($_SESSION['success_msg'])): ?>
            showToast("<?php echo htmlspecialchars($_SESSION['success_msg']); ?>", "success");
            // Clear message immediately to avoid duplicate fire
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            showToast("<?php echo htmlspecialchars($_SESSION['error_msg']); ?>", "danger");
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
    });
    </script>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- pdfmake removed - PDF now uses browser native print for full Gujarati support -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
    $(document).ready(function() {
        // Automatically convert any table with the datatable-premium class
        if ($('.datatable-premium').length > 0) {
            $('.datatable-premium').each(function() {
                var $table = $(this);
                
                // Retrieve initial search value from URL parameters if present
                var urlParams = new URLSearchParams(window.location.search);
                var initialSearch = urlParams.get('search') || '';
                
                // Exclude sorting on action columns automatically (normally the last column)
                var columnDefs = [];
                var lastColIndex = $table.find('thead th').length - 1;
                
                // We want to make sure the Actions column (normally the last one) is not sortable
                var $lastHeader = $table.find('thead th').eq(lastColIndex);
                var lastHeaderText = $lastHeader.text().trim().toLowerCase();
                if (lastHeaderText === 'actions' || lastHeaderText === 'action') {
                    columnDefs.push({
                        targets: lastColIndex,
                        orderable: false,
                        searchable: false,
                        className: 'no-export'
                    });
                }
                
                // Initialize DataTable
                var table = $table.DataTable({
                    dom: 'Bfrtip',
                    pageLength: 10,
                    ordering: true,
                    searching: true,
                    info: true,
                    paging: true,
                    scrollX: true,
                    order: [], // keep default natural SQL sorting on load
                    columnDefs: columnDefs,
                    language: {
                        search: "Instant Grid Search:",
                        lengthMenu: "Show _MENU_ records per page",
                        zeroRecords: "No matching records found",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total records)"
                    },
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa-solid fa-file-excel"></i> Export to Excel',
                            className: 'dt-button buttons-excel',
                            title: function() { return document.title || 'Export'; },
                            exportOptions: {
                                columns: ':not(.no-export)'
                            }
                        },
                        {
                            // Custom Print-to-PDF button using browser native rendering
                            // This ensures 100% Gujarati Unicode support via Google Fonts
                            text: '<i class="fa-solid fa-file-pdf"></i> Export to PDF',
                            className: 'dt-button buttons-pdf',
                            action: function(e, dt, button, config) {
                                // Collect headers (exclude no-export columns)
                                var headers = [];
                                dt.columns(':not(.no-export)').every(function() {
                                    headers.push($(this.header()).text().trim());
                                });

                                // Collect all visible rows data
                                var rows = [];
                                dt.rows({ search: 'applied' }).every(function() {
                                    var rowData = [];
                                    var $tr = $(this.node());
                                    $tr.find('td').each(function(idx) {
                                        // Skip no-export columns
                                        var colIdx = dt.cell(this).index().column;
                                        if (!$(dt.column(colIdx).header()).hasClass('no-export')) {
                                            rowData.push($(this).text().trim());
                                        }
                                    });
                                    rows.push(rowData);
                                });

                                // Build header row HTML
                                var theadHtml = '<tr>' + headers.map(function(h) {
                                    return '<th>' + h + '</th>';
                                }).join('') + '</tr>';

                                // Build body rows HTML
                                var tbodyHtml = rows.map(function(row) {
                                    return '<tr>' + row.map(function(cell) {
                                        return '<td>' + cell + '</td>';
                                    }).join('') + '</tr>';
                                }).join('');

                                var pageTitle = document.title || 'Report';

                                // Generate print window with Noto Sans Gujarati from Google Fonts
                                var printWindow = window.open('', '_blank', 'width=1200,height=800');
                                printWindow.document.write(`<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${pageTitle}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Noto Sans Gujarati', sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            padding: 20px;
            background: #fff;
        }
        h1 {
            text-align: center;
            font-size: 16px;
            color: #1e3f20;
            margin-bottom: 4px;
            font-family: 'Inter', 'Noto Sans Gujarati', sans-serif;
            font-weight: 700;
        }
        .subtitle {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            font-family: 'Inter', 'Noto Sans Gujarati', sans-serif;
        }
        thead tr th {
            background-color: #1e3f20;
            color: #ffffff;
            padding: 7px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
            border: 1px solid #16321a;
        }
        tbody tr td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            font-family: 'Inter', 'Noto Sans Gujarati', sans-serif;
        }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }
        tbody tr:hover td { background-color: #f0fdf4; }
        .footer-note {
            text-align: right;
            font-size: 9px;
            color: #94a3b8;
            margin-top: 12px;
        }
        @media print {
            body { padding: 10px; }
            @page { size: A4 landscape; margin: 10mm; }
            thead { display: table-header-group; }
            tbody tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <h1>${pageTitle}</h1>
    <div class="subtitle">Generated on: ${new Date().toLocaleDateString('en-IN', {day:'2-digit',month:'2-digit',year:'numeric'})} | Total Records: ${rows.length}</div>
    <table>
        <thead>${theadHtml}</thead>
        <tbody>${tbodyHtml}</tbody>
    </table>
    <div class="footer-note">Green City Association Management System &copy; ${new Date().getFullYear()}</div>
    <script>
        // Wait for fonts to load then print
        document.fonts.ready.then(function() {
            setTimeout(function() { window.print(); }, 500);
        });
    <\/script>
</body>
</html>`);
                                printWindow.document.close();
                            }
                        }
                    ]
                });
                
                // If there is an initial search term from the URL, apply it
                if (initialSearch) {
                    table.search(initialSearch).draw();
                }
                
                // Intercept existing custom search inputs on the page to trigger instant client-side filtering
                var $customSearchInput = $('input[name="search"]');
                if ($customSearchInput.length > 0) {
                    // Update value
                    if (initialSearch) {
                        $customSearchInput.val(initialSearch);
                    }
                    
                    // Bind real-time keyup and input events to search table instantly
                    $customSearchInput.on('keyup input change', function() {
                        table.search(this.value).draw();
                    });
                    
                    // Intercept and prevent the search form from submitting and reloading the page
                    $customSearchInput.closest('form').on('submit', function(e) {
                        e.preventDefault();
                        table.search($customSearchInput.val()).draw();
                        return false;
                    });
                }
                
                // Intercept existing custom status dropdown filter (if any, like in plots.php)
                var $statusDropdown = $('select[name="status_filter"]');
                if ($statusDropdown.length > 0) {
                    var applyStatusFilter = function(val) {
                        if (val) {
                            // Find the index of the Status or Entry Status column (e.g. column index 7 in plots.php)
                            var entryStatusColIdx = -1;
                            $table.find('thead th').each(function(idx) {
                                var headerText = $(this).text().trim().toLowerCase();
                                if (headerText.indexOf('entry status') !== -1 || headerText.indexOf('status') !== -1) {
                                    entryStatusColIdx = idx;
                                    return false; // break loop
                                }
                            });
                            
                            if (entryStatusColIdx !== -1) {
                                // Search exact term in the specific column
                                table.column(entryStatusColIdx).search(val).draw();
                            } else {
                                // General search as fallback
                                table.search(val).draw();
                            }
                        } else {
                            // Clear column-specific filters
                            table.columns().search('').draw();
                            // Apply custom search input value if any
                            if ($customSearchInput.length > 0) {
                                table.search($customSearchInput.val()).draw();
                            }
                        }
                    };
                    
                    // Intercept and prevent dropdown form submission
                    $statusDropdown.closest('form').find('select[name="status_filter"]').off('change').on('change', function(e) {
                        e.preventDefault();
                        applyStatusFilter($(this).val());
                        return false;
                    });
                    
                    // Initialize on load if there's an active status filter value
                    if ($statusDropdown.val()) {
                        applyStatusFilter($statusDropdown.val());
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
