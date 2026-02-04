<?php
// File: modules/auth/logout.php
require_once '../../config/config.php';
session_start();
session_destroy();
header("Location: " . $base_url . "modules/auth/login.php");
exit;
?>