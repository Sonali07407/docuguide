<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

require_admin('login.php');

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username']);
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc_id'])) {
    verify_csrf();
    $del_id = intval($_POST['delete_doc_id']);
    if ($del_id > 0) {
        $stmt = $conn->prepare("DELETE FROM documents WHERE doc_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
    }
    header("Location: dashboard.php?msg=deleted");
    exit;
}

$docs = $conn->query("SELECT d.*, c.category_name FROM documents d LEFT JOIN categories c ON d.category_id = c.category_id ORDER BY d.doc_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DocuGuide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f3f6; }
        .fk-navbar { background-color: #2874f0; padding: 12px 0; }
        .fk-brand { color: #fff; font-weight: 800; font-size: 1.4rem; text-decoration: none; font-style: italic; }
        .btn-add { background-color: #fb641b; color: #fff; font-weight: 700; border: none; }
        .btn-add:hover { background-color: #e0530f; color: #fff; }
    </style>
</head>
<body>

<nav class="fk-navbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="../index.php" class="fk-brand">DocuGuide <small class="fs-6 fw-normal text-warning">Admin Panel</small></a>
        <div class="d-flex gap-2">
            <a href="../index.php" class="btn btn-outline-light btn-sm">View Citizen Site</a>
            <a href="dashboard.php?logout=1" class="btn btn-light text-primary fw-bold btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="bg-white p-4 rounded-1 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="fw-bold mb-0">Manage Document Guides</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="add_faq.php" class="btn btn-outline-primary fw-bold">+ Add FAQ</a>
                <a href="feedback.php" class="btn btn-outline-secondary fw-bold">Feedback</a>
                <a href="add_document.php" class="btn btn-add px-4">+ Add New Document Guide</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success py-2 small">
                <?php echo $_GET['msg'] === 'added' ? 'New document guide published!' : 'Document guide deleted.'; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Document Name</th>
                        <th>Category</th>
                        <th>Processing Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($docs && $docs->num_rows > 0): ?>
                        <?php while ($row = $docs->fetch_assoc()): ?>
                            <?php $did = intval($row['doc_id']); ?>
                            <tr>
                                <td>#<?php echo $did; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['doc_name']); ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></span></td>
                                <td><?php echo htmlspecialchars($row['processing_time']); ?></td>
                                <td class="text-end">
                                    <a href="../guide.php?id=<?php echo $did; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Preview</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this document guide?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete_doc_id" value="<?php echo $did; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No document guides yet. Click "+ Add New Document Guide" to add one!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>