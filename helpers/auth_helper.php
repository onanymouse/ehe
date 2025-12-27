<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "views/auth/login.php");
        exit;
    }
}

// Cek Role (Bisa multiple role)
function checkRole($allowed_roles = []) {
    if (!isset($_SESSION['role'])) {
        header("Location: " . BASE_URL . "views/auth/login.php");
        exit;
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Jika akses ditolak, redirect ke dashboard atau tampilkan pesan
        echo "<script>alert('Akses Ditolak!'); window.history.back();</script>";
        exit;
    }
}
?>
