<?php
require_once 'config.php';

// Oturumu temizle
session_destroy();
session_unset();

// Giriş sayfasına yönlendir
redirect('login.php');
?>