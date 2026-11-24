<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

require_admin('login.php');

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['delete_faq_id'])) {
        $del_id = intval($_POST['delete_faq_id']);
        if ($del_id > 0) {
            $del_stmt = $conn->prepare("DELETE FROM faqs WHERE faq_id = ?");
            $del_stmt->bind_param("i", $del_id);
            $del_stmt->execute();
        }
        header("Location: add_faq.php");
        exit;
    }

    $doc_id = intval($_POST['doc_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');

    if ($doc_id > 0 && $question !== '' && $answer !== '') {
        $stmt = $conn->prepare("INSERT INTO faqs (doc_id, question, answer) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $doc_id, $question, $answer);
            if ($stmt->execute()) {
                $msg = "FAQ added successfully!";
            } else {
                $error = "Could not add FAQ: " . $stmt->error;
            }
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Please select a document and enter both question and answer.";
    }
}

$docs = $conn->query("SELECT doc_id, doc_name FROM documents ORDER BY doc_name ASC");
$faq_list = $conn->query("SELECT f.*, d.doc_name FROM faqs f LEFT JOIN documents d ON f.doc_id = d.doc_id ORDER BY f.faq_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQs - DocuGuide Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">DocuGuide Admin</a>
        <div class="d-flex gap-2">
            <a href="feedback.php" class="btn btn-outline-light btn-sm">View Feedback</a>
            <a href="dashboard.php" class="btn btn-light btn-sm">← Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width: 820px;">
    <div class="bg-white p-4 rounded shadow-sm mb-4">
        <h4 class="mb-3">Add Document FAQ</h4>

        <?php if ($msg): ?><div class="alert alert-success py-2 small"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>

            <div class="mb-3">
                <label class="form-label fw-bold">Select Document</label>
                <select name="doc_id" class="form-select" required>
                    <?php if ($docs && $docs->num_rows > 0): ?>
                        <?php while ($d = $docs->fetch_assoc()): ?>
                            <option value="<?php echo intval($d['doc_id']); ?>"><?php echo htmlspecialchars($d['doc_name']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Question</label>
                <input type="text" name="question" class="form-control" placeholder="e.g. Is an affidavit required for this certificate?" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Answer</label>
                <textarea name="answer" class="form-control" rows="2" placeholder="Write a clear, helpful answer..." required></textarea>
            </div>
            <button type="submit" class="btn btn-warning fw-bold px-4">Save FAQ</button>
        </form>
    </div>

    <div class="bg-white p-4 rounded shadow-sm">
        <h5 class="mb-3">Existing FAQs</h5>
        <?php if ($faq_list && $faq_list->num_rows > 0): ?>
            <?php while ($item = $faq_list->fetch_assoc()): ?>
                <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary mb-1"><?php echo htmlspecialchars($item['doc_name'] ?? 'Document'); ?></span>
                        <div class="fw-bold"><?php echo htmlspecialchars($item['question']); ?></div>
                        <div class="text-muted small mt-1"><?php echo htmlspecialchars($item['answer']); ?></div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Delete this FAQ?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="delete_faq_id" value="<?php echo intval($item['faq_id']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted small mb-0">No FAQs added yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>