<?php
// auth_check.php
session_start();
if (!isset($_SESSION['user_admin']) || empty($_SESSION['user_admin'])) {
    header("Location: login.php?err=denied");
    exit();
}
?>