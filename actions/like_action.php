<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (!is_user_logged_in()) {
    header("Location: index.php?require_register=1");
    exit;
}

$page_title = 'Liked Documents - DocuGuide';
$show_search = false;

require_once __DIR__ . '/includes/header.php';

$user_id = current_user_id();
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

<div class="container my-4">
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-primary btn-sm fw-bold">← Back to All Guides</a>
    </div>

    <div class="bg-white p-4 rounded-1 shadow-sm">
        <h4 class="fw-bold mb-3">❤️ <?php echo htmlspecialchars(current_user_name()); ?>'s Liked Documents (<?php echo $docs->num_rows; ?>)</h4>

        <?php if ($docs->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($row = $docs->fetch_assoc()): ?>
                    <?php $doc_id = intval($row['doc_id']); ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center p-3 gap-3 flex-wrap">
                        <div>
                            <span class="badge bg-primary-subtle text-primary mb-1"><?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></span>
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['doc_name']); ?></h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($row['overview']); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="guide.php?id=<?php echo $doc_id; ?>" class="btn btn-warning btn-sm fw-bold">View Checklist</a>
                            <a href="actions/like_action.php?doc_id=<?php echo $doc_id; ?>&from=liked" class="btn btn-outline-danger btn-sm">Remove</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">You haven't liked any documents yet. Click the heart icon on any document card to save it here!</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>