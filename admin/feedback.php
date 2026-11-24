<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

require_admin('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_fb_id'])) {
    verify_csrf();
    $del_id = intval($_POST['delete_fb_id']);
    if ($del_id > 0) {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
    }
    header("Location: feedback.php");
    exit;
}

$rows = $conn->query("SELECT f.*, d.doc_name FROM feedback f LEFT JOIN documents d ON f.doc_id = d.doc_id ORDER BY f.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Feedback - DocuGuide Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">DocuGuide Admin</a>
        <div class="d-flex gap-2">
            <a href="add_faq.php" class="btn btn-outline-light btn-sm">Manage FAQs</a>
            <a href="dashboard.php" class="btn btn-light btn-sm">← Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width: 820px;">
    <div class="bg-white p-4 rounded shadow-sm">
        <h4 class="mb-3">Citizen Feedback</h4>

        <?php if ($rows && $rows->num_rows > 0): ?>
            <?php while ($fb = $rows->fetch_assoc()): ?>
                <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-bold">
                            <?php echo htmlspecialchars($fb['user_name']); ?>
                            <?php if (!empty($fb['doc_name'])): ?>
                                <span class="badge bg-primary-subtle text-primary ms-1"><?php echo htmlspecialchars($fb['doc_name']); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1 mt-1"><?php echo htmlspecialchars($fb['message']); ?></p>
                        <small class="text-muted"><?php echo htmlspecialchars($fb['created_at']); ?></small>
                    </div>
                    <form method="POST" onsubmit="return confirm('Delete this feedback?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="delete_fb_id" value="<?php echo intval($fb['feedback_id']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted small mb-0">No feedback submitted yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>