<?php
// Komentar: Skrip untuk menghancurkan sesi dan logout user
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
?>