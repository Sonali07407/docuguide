<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$page_title = $page_title ?? 'DocuGuide - Citizen Document Store';
$show_search = $show_search ?? false;
$search_value = $search_value ?? '';

$liked_count = 0;
if (is_user_logged_in()) {
    $uid = current_user_id();
    $c_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM liked_documents WHERE user_id = ?");
    $c_stmt->bind_param("i", $uid);
    $c_stmt->execute();
    $cnt_row = $c_stmt->get_result()->fetch_assoc();
    $liked_count = intval($cnt_row['cnt'] ?? 0);
}

$auth_error = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);
$require_register = isset($_GET['require_register']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f3f6; }
        .fk-navbar { background-color: #2874f0; padding: 12px 0; }
        .fk-brand { color: #fff; font-weight: 800; font-size: 1.4rem; text-decoration: none; font-style: italic; }
        .fk-brand span { color: #ffe500; font-size: 0.75rem; display: block; font-weight: 500; margin-top: -6px; }
        .cat-bar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 10px 0; }
        .cat-pill { text-decoration: none; color: #212121; font-weight: 600; font-size: 0.9rem; padding: 6px 14px; border-radius: 20px; }
        .cat-pill.active, .cat-pill:hover { background: #e8f0fe; color: #2874f0; }
        .doc-card { background: #fff; border: none; border-radius: 6px; transition: transform 0.15s, box-shadow 0.15s; height: 100%; }
        .doc-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
        .time-badge { background: #388e3c; color: #fff; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; }
        .like-btn { text-decoration: none; font-size: 1.2rem; line-height: 1; cursor: pointer; }
        .fk-modal-left { background-color: #2874f0; color: #fff; padding: 32px 24px; }
        .btn-fk-orange { background-color: #fb641b; color: #fff; font-weight: 700; border: none; }
        .btn-fk-orange:hover { background-color: #e0530f; color: #fff; }
        .fk-input { border: none; border-bottom: 1px solid #ced4da; border-radius: 0; padding-left: 0; }
        .fk-input:focus { box-shadow: none; border-bottom: 2px solid #2874f0; }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body>

<nav class="fk-navbar no-print">
    <div class="container d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <a href="index.php" class="fk-brand">DocuGuide <span>Official Portal</span></a>

        <?php if ($show_search): ?>
            <form method="GET" action="index.php" class="d-flex flex-grow-1" style="max-width: 480px;">
                <input type="text" name="search" class="form-control rounded-0 rounded-start border-0"
                       placeholder="Search for Domicile, PAN, Income Certificate..."
                       value="<?php echo htmlspecialchars($search_value); ?>">
                <button type="submit" class="btn bg-white text-primary fw-bold rounded-0 rounded-end px-4">Search</button>
            </form>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-2">
            <?php if (is_user_logged_in()): ?>
                <span class="text-white fw-semibold small me-1">Hi, <?php echo htmlspecialchars(current_user_name()); ?></span>
                <a href="actions/auth_action.php?logout=1" class="btn btn-outline-light btn-sm fw-bold">Logout</a>
                <a href="liked.php" class="btn btn-outline-light fw-bold px-3 rounded-1">
                    ❤️ Liked (<?php echo $liked_count; ?>)
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-light text-primary fw-bold px-4 rounded-1" onclick="openAuthPopup('login')">
                    Login
                </button>
                <button type="button" class="btn btn-outline-light fw-bold px-3 rounded-1" onclick="openAuthPopup('register', true)">
                    ❤️ Liked (0)
                </button>
            <?php endif; ?>

            <a href="admin/login.php" class="btn btn-warning fw-bold px-3 rounded-1">Admin</a>
        </div>
    </div>
</nav>