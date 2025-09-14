<?php
session_start();
unset($_SESSION['member']);

header('Location: login.php'); exit();
?>
