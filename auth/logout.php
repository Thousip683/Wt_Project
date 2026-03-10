<?php
require_once '../config/config.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>
