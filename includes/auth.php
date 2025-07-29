<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Redirect if logged in
function requireGuest() {
    if (isLoggedIn()) {
        header('Location: /dashboard.php');
        exit();
    }
}

// Get current user data
function currentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    static $user = null;
    if ($user === null) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

// Login function
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        updateUserStatus($user['id'], 'online');
        return true;
    }
    return false;
}

// Logout function
function logout() {
    if (isLoggedIn()) {
        updateUserStatus($_SESSION['user_id'], 'offline');
    }
    session_destroy();
    session_start();
}

// Update user status
function updateUserStatus($userId, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET status = ?, last_online = NOW() WHERE id = ?");
    $stmt->execute([$status, $userId]);
}
?>
