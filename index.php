<?php
$page_title = 'DocuGuide - Citizen Document Store';$show_search = true;
$search_value = isset($_GET['search']) ? trim($_GET['search']) : '';$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

require_once __DIR__ . '/includes/header.php';

$liked_docs = [];
if (is_user_logged_in()) {
    $uid = current_user_id();
    $l_stmt =$conn->prepare("SELECT doc_id FROM liked_documents WHERE user_id = ?");
    $l_stmt->bind_param("i", $uid);$l_stmt->execute();
    $l_res =$l_stmt->get_result();
    while ($r =$l_res->fetch_assoc()) {
        $liked_docs[] = intval($r['doc_id']);
    }
}

$cat_result =$conn->query("SELECT * FROM categories ORDER BY category_name ASC");

$sql = "SELECT d.*, c.category_name 
        FROM documents d 
        LEFT JOIN categories c ON d.category_id = c.category_id 
        WHERE 1=1";
$params = [];$types = "";

if ($search_value !== '') {$sql .= " AND (d.doc_name LIKE ? OR d.overview LIKE ?)";
    $like = "\%" . $search_value . "%";
    $params[] =$like;
    $params[] =$like;
    $types .= "ss";
}

if ($category_id > 0) {$sql .= " AND d.category_id = ?";
    $params[] =$category_id;
    $types .= "i";
}

$sql .= " ORDER BY d.doc_name ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {$stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result =$stmt->get_result();
?>

<div class="cat-bar mb-3 no-print">
    <div class="container d-flex gap-2 flex-wrap">
        <a href="index.php" class="cat-pill <?php echo $category_id === 0 ? 'active' : ''; ?>">All Documents</a>
        <?php while ($cat =$cat_result->fetch_assoc()): ?>
            <a href="index.php?category=<?php echo intval($cat['category_id']); ?>" 
               class="cat-pill <?php echo $category_id === intval($cat['category_id']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </a>
        <?php endwhile; ?>
    </div>
</div>

<div class="container pb-5">
    <div class="bg-white p-3 rounded-1 shadow-sm mb-3">
        <h5 class="mb-0 fw-bold">Available Document Guides</h5>
    </div>

    <div class="row g-3">
        <?php if ($result &&$result->num_rows > 0): ?>
            <?php while ($row =$result->fetch_assoc()): ?>
                <?php $doc_id = intval($row['doc_id']); ?>
                <?php $is_liked = in_array($doc_id,$liked_docs, true); ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card doc-card p-3 shadow-sm d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-subtle text-primary">
                                    <?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?>
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="time-badge"><?php echo htmlspecialchars($row['processing_time']); ?></span>
                                    <?php if (is_user_logged_in()): ?>
                                        <a href="actions/like_action.php?doc_id=<?php echo $doc_id; ?>&from=index" class="like-btn" title="Save to Liked">
                                            <?php echo $is_liked ? '❤️' : '🤍'; ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" onclick="openAuthPopup('register', true); return false;" class="like-btn" title="Register to save favourites">
                                            🤍
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($row['doc_name']); ?></h6>
                            <p class="text-muted small"><?php echo htmlspecialchars($row['overview']); ?></p>
                        </div>
                        <a href="guide.php?id=<?php echo $doc_id; ?>" class="btn btn-warning fw-bold w-100 mt-2">
                            View Checklist
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="bg-white p-4 text-center rounded-1">No matching documents found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>