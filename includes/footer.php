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

    <!-- Include JS Libraries for jQuery & DataTables Premium features (Search, Sort, PDF/Excel Export) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
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
                            extend: 'pdfHtml5',
                            text: '<i class="fa-solid fa-file-pdf"></i> Export to PDF',
                            className: 'dt-button buttons-pdf',
                            title: function() { return document.title || 'Export'; },
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: {
                                columns: ':not(.no-export)'
                            },
                            customize: function(doc) {
                                // Apply gorgeous corporate branding styles to PDF
                                doc.styles.tableHeader = {
                                    fillColor: '#1e3f20', // Forest Green
                                    color: '#ffffff',
                                    alignment: 'left',
                                    bold: true,
                                    fontSize: 8
                                };
                                doc.defaultStyle.fontSize = 7;
                                doc.styles.title = {
                                    color: '#1e3f20',
                                    fontSize: 12,
                                    bold: true,
                                    alignment: 'center',
                                    margin: [0, 0, 0, 10]
                                };
                                // Auto-adjust layout columns to fit within page
                                if (doc.content[1] && doc.content[1].table) {
                                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                                    
                                    // Add grid lines (borders)
                                    var objLayout = {};
                                    objLayout['hLineWidth'] = function(i) { return 0.5; };
                                    objLayout['vLineWidth'] = function(i) { return 0.5; };
                                    objLayout['hLineColor'] = function(i) { return '#cbd5e1'; };
                                    objLayout['vLineColor'] = function(i) { return '#cbd5e1'; };
                                    objLayout['paddingLeft'] = function(i) { return 6; };
                                    objLayout['paddingRight'] = function(i) { return 6; };
                                    objLayout['paddingTop'] = function(i) { return 4; };
                                    objLayout['paddingBottom'] = function(i) { return 4; };
                                    doc.content[1].layout = objLayout;
                                }
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
