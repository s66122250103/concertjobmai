<?php
session_start();
require_once 'C:/xampp/htdocs/Concert/config/database.php';
require_once 'C:/xampp/htdocs/Concert/includes/auth.php';
if (isLoggedIn()) {
    header('Location: http://localhost/Concert/pages/events.php');
} else {
    header('Location: http://localhost/Concert/pages/login.php');
}
exit;
