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
</body>
</html>
