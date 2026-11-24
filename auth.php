<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name !== '' && $email !== '' && $password !== '') {
            $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $_SESSION['auth_error'] = "Email already registered. Please login.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hash);
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['user_name'] = $name;
                } else {
                    $_SESSION['auth_error'] = "Registration failed. Try again.";
                }
            }
        }
    } elseif ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT user_id, name, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
        } else {
            $_SESSION['auth_error'] = "Invalid email or password.";
        }
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['user_id'], $_SESSION['user_name']);
}

header("Location: index.php");
exit;