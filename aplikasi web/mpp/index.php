<?php
// File: index.php
require_once 'config/config.php';
// Redirect langsung ke dashboard (auth check akan menendang ke login jika belum auth)
header("Location: " . $base_url . "modules/dashboard/index.php");
exit;
?>