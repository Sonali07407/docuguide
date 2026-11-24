<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (is_admin_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

// Seed default admin only if admins table is completely empty
$count_res = $conn->query("SELECT COUNT(*) AS cnt FROM admins");
if ($count_res) {
    $cnt_row = $count_res->fetch_assoc();
    if (intval($cnt_row['cnt'] ?? 0) === 0) {
        $default_user = 'admin';
        $default_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $seed = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        if ($seed) {
            $seed->bind_param("ss", $default_user, $default_hash);
            $seed->execute();
        }
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT admin_id, username, password_hash FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = intval($admin['admin_id']);
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DocuGuide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f3f6; }
        .login-card { max-width: 750px; margin: 70px auto; border-radius: 6px; overflow: hidden; }
        .left-panel { background-color: #2874f0; color: #fff; }
        .btn-login { background-color: #fb641b; color: #fff; font-weight: 700; border: none; }
        .btn-login:hover { background-color: #e0530f; color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card shadow-sm border-0">
        <div class="row g-0">
            <div class="col-md-5 left-panel p-4 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="fw-bold">Admin Login</h3>
                    <p class="small opacity-75 mt-2">Manage document guides, eligibility rules, FAQs, and citizen feedback for DocuGuide.</p>
                </div>
                <a href="../index.php" class="text-white text-decoration-none small">← Back to Citizen Portal</a>
            </div>
            <div class="col-md-7 bg-white p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter admin" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <button type="submit" class="btn btn-login w-100 py-2">LOGIN TO DASHBOARD</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>