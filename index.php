<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/includes/auth.php');
if (isLoggedIn()) {
    header('Location: /pages/events.php');
} else {
    header('Location: /pages/login.php');
}
exit;
