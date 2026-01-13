<?php
session_start();
require_once '../../core/connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $query = "SELECT userId, firstName, lastName, fullName, email, phoneNumber, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['userId'] = $user['userId'];
                $_SESSION['firstName'] = $user['firstName'];
                $_SESSION['role'] = $user['role'];
                
                $_SESSION['user_id'] = $user['userId'];
                $_SESSION['user_name'] = $user['fullName'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_mobile'] = $user['phoneNumber'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin' || $user['role'] === 'Admin') {
                    header('Location: ../admin/adminPages/admin.php');
                } else {
                    header('Location: ../index.php');
                }
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
    
    if (!empty($error)) {
        $_SESSION['login_error'] = $error;
        header('Location: ../pages/login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>

