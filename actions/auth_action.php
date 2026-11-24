<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action =$_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password =$_POST['password'] ?? '';

        if ($name === '' \vert{}\vert{} !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {$_SESSION['auth_error'] = "Enter a valid name, email, and password (min 6 characters).";
        } else {
            $check =$conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check->bind_param("s", $email);$check->execute();

            if ($check->get_result()->num_rows > 0) {$_SESSION['auth_error'] = "Email already registered. Please log in.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt =$conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email,$hash);

                if ($stmt->execute()) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = intval($conn->insert_id);
                    $_SESSION['user_name'] =$name;
                } else {
                    $_SESSION['auth_error'] = "Could not create account. Please try again.";
                }
            }
        }
    } elseif ($action === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password =$_POST['password'] ?? '';

        $stmt =$conn->prepare("SELECT user_id, name, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);$stmt->execute();
        $user =$stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password,$user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = intval($user['user_id']);
            $_SESSION['user_name'] =$user['name'];
        } else {
            $_SESSION['auth_error'] = "Invalid email or password.";
        }
    }

    header("Location: ../index.php");
    exit;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['user_id'],$_SESSION['user_name']);
    session_regenerate_id(true);
    header("Location: ../index.php");
    exit;
}

header("Location: ../index.php");
exit;