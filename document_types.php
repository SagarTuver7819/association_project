<?php
// document_types.php
// Master CRUD for Dastavej (Document) Types

require_once 'config/db.php';

$pageTitle = 'Document Type Master';
require_once 'includes/header.php';

$successMsg = '';
$errorMsg = '';

// Handle Delete Request
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM document_types WHERE id = :id");
        $stmt->execute(['id' => $deleteId]);
        $successMsg = "Document type deleted successfully!";
    } catch (PDOException $e) {
        $errorMsg = "Cannot delete this document type because it is linked to existing plots.";
    }
}

// Handle Add / Edit Form Submission
$editId = null;
$editName = '';

if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM document_types WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $typeToEdit = $stmt->fetch();
    if ($typeToEdit) {
        $editName = $typeToEdit['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeName = trim($_POST['name'] ?? '');
    $postEditId = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;
    
    if (!empty($typeName)) {
        try {
            if ($postEditId) {
                // Update
                $stmt = $pdo->prepare("UPDATE document_types SET name = :name WHERE id = :id");
                $stmt->execute(['name' => $typeName, 'id' => $postEditId]);
                $successMsg = "Document type updated successfully!";
                $editId = null; // Clear edit mode
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO document_types (name) VALUES (:name)");
                $stmt->execute(['name' => $typeName]);
                $successMsg = "New document type added successfully!";
            }
        } catch (PDOException $e) {
            $errorMsg = "This document type already exists.";
        }
    } else {
        $errorMsg = "Please enter a valid document type name.";
    }
}

// Fetch all document types
try {
    $stmt = $pdo->query("SELECT * FROM document_types ORDER BY name ASC");
    $docTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMsg = "Error loading document types: " . $e->getMessage();
    $docTypes = [];
}
?>

<div class="action-header">
    <div class="action-header-title">
        <h2>Dastavej (Document) Type Master</h2>
        <span>Manage the choices for Document Types dynamically selected in the Main Plot Form</span>
    </div>
</div>

<!-- Alerts -->
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

<div class="dual-panel-grid doc-types-grid">
    <!-- Add/Edit Form Card -->
    <div class="form-card">
        <h3><?php echo $editId ? '<i class="fa-solid fa-pen-to-square"></i> Edit Document Type' : '<i class="fa-solid fa-square-plus"></i> Add Document Type'; ?></h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Provide a descriptive name (e.g. Resell, Gift Deed, First Owner, etc.)
        </p>
        
        <form action="document_types.php" method="POST" autocomplete="off">
            <?php if ($editId): ?>
                <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
            <?php endif; ?>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="name">Document Type Name (દસ્તાવેજનો પ્રકાર)</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="input-control" 
                    value="<?php echo htmlspecialchars($editName); ?>" 
                    placeholder="e.g. Gift Deed" 
                    required
                >
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-accent">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $editId ? 'Update Master' : 'Save to Master'; ?>
                </button>
                <?php if ($editId): ?>
                    <a href="document_types.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Table Grid -->
    <div class="table-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background-color: #fafdfb;">
            <h3 style="font-size: 1.1rem;"><i class="fa-solid fa-list-ol"></i> Registered Document Types</h3>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Document Type Name (દસ્તાવેજનો પ્રકાર)</th>
                        <th style="width: 180px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($docTypes) > 0): ?>
                        <?php foreach ($docTypes as $type): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="document_types.php?edit=<?php echo $type['id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a 
                                            href="document_types.php?delete=<?php echo $type['id']; ?>" 
                                            class="btn btn-danger btn-sm" 
                                            onclick="triggerCustomConfirmDelete(this.href, 'Are you sure you want to delete this document type? This might affect existing plots.'); return false;" 
                                            title="Delete"
                                        >
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 2rem;">No document types registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
