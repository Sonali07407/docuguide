<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?require_register=1");
    exit;
}

$user_id = intval($_SESSION['user_id']);

if (isset($_GET['remove'])) {
    $rem_id = intval($_GET['remove']);
    $del = $conn->prepare("DELETE FROM liked_documents WHERE user_id = ? AND doc_id = ?");
    $del->bind_param("ii", $user_id, $rem_id);
    $del->execute();
    header("Location: liked.php");
    exit;
}

$stmt = $conn->prepare("SELECT d.*, c.category_name 
                        FROM liked_documents ld 
                        JOIN documents d ON ld.doc_id = d.doc_id 
                        LEFT JOIN categories c ON d.category_id = c.category_id 
                        WHERE ld.user_id = ? 
                        ORDER BY ld.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$docs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liked Documents - DocuGuide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f3f6; }
        .fk-navbar { background-color: #2874f0; padding: 12px 0; }
        .fk-brand { color: #fff; font-weight: 800; font-size: 1.4rem; text-decoration: none; font-style: italic; }
        .fk-brand span { color: #ffe500; font-size: 0.75rem; display: block; font-weight: 500; margin-top: -6px; }
    </style>
</head>
<body>

<nav class="fk-navbar">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="index.php" class="fk-brand">DocuGuide <span>Official Portal</span></a>
        <a href="index.php" class="btn btn-light text-primary fw-bold btn-sm">← Back to All Guides</a>
    </div>
</nav>

<div class="container my-4">
    <div class="bg-white p-4 rounded-1 shadow-sm">
        <h4 class="fw-bold mb-3">❤️ <?php echo htmlspecialchars($_SESSION['user_name']); ?>'s Liked Documents (<?php echo $docs->num_rows; ?>)</h4>

        <?php if ($docs->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($row = $docs->fetch_assoc()): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                        <div>
                            <span class="badge bg-primary-subtle text-primary mb-1"><?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></span>
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['doc_name']); ?></h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($row['overview']); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="guide.php?id=<?php echo $row['doc_id']; ?>" class="btn btn-warning btn-sm fw-bold">View Checklist</a>
                            <a href="liked.php?remove=<?php echo $row['doc_id']; ?>" class="btn btn-outline-danger btn-sm">Remove</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">You haven't liked any documents yet. Click the heart icon on any document card to save it here!</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>