<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Concert Booking' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Kanit', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }

        /* ===== NAVBAR ===== */
        .navbar {
            background: rgba(15,15,26,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(108,99,255,0.3);
            padding: 0 2rem;
            position: sticky; top: 0; z-index: 999;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
        }
        .navbar-brand {
            font-size: 1.5rem; font-weight: 700;
            background: linear-gradient(135deg, #6C63FF, #ff6584);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
            white-space: nowrap;
        }
        .navbar-nav { display: flex; gap: 1.5rem; align-items: center; list-style: none; }
        .navbar-nav a { color: #ccc; text-decoration: none; font-size: 0.95rem; transition: color .2s; white-space: nowrap; }
        .navbar-nav a:hover { color: #6C63FF; }
        .btn-nav {
            background: linear-gradient(135deg, #6C63FF, #9c94ff);
            color: #fff !important; padding: .45rem 1.2rem;
            border-radius: 50px; font-size: .9rem;
            transition: transform .2s, box-shadow .2s !important;
        }
        .btn-nav:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(108,99,255,.5); }

        /* ===== HAMBURGER TOGGLE (ซ่อนไว้บนจอกว้าง) ===== */
        .navbar-toggle {
            display: none;
            background: none; border: none; color: #fff;
            font-size: 1.6rem; cursor: pointer; padding: .3rem .5rem;
        }

        /* ===== RESPONSIVE: มือถือ/จอแคบ ===== */
        @media (max-width: 768px) {
            .navbar { padding: 0 1rem; flex-wrap: wrap; height: auto; min-height: 64px; }
            .navbar-toggle { display: block; }
            .navbar-nav {
                display: none;
                flex-direction: column;
                width: 100%;
                gap: 0;
                align-items: stretch;
                background: rgba(15,15,26,0.98);
                border-top: 1px solid rgba(108,99,255,0.3);
            }
            .navbar-nav.active { display: flex; }
            .navbar-nav li { width: 100%; }
            .navbar-nav a {
                display: block;
                padding: .9rem 1rem;
                border-bottom: 1px solid rgba(255,255,255,.06);
            }
            .navbar-nav .btn-nav {
                border-radius: 0;
                text-align: center;
                margin: 0;
            }
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block; padding: .6rem 1.5rem;
            border-radius: 50px; border: none; cursor: pointer;
            font-family: 'Kanit', sans-serif; font-size: 1rem;
            text-decoration: none; transition: all .25s;
        }
        .btn-primary { background: linear-gradient(135deg,#6C63FF,#9c94ff); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(108,99,255,.5); }
        .btn-outline { background: transparent; border: 2px solid #6C63FF; color: #6C63FF; }
        .btn-outline:hover { background: #6C63FF; color: #fff; }
        .btn-danger { background: linear-gradient(135deg,#ff4d6d,#ff6584); color: #fff; }
        .btn-success { background: linear-gradient(135deg,#4CAF50,#81c784); color: #fff; }

        /* ===== FORMS ===== */
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: .4rem; color: #aaa; font-size: .9rem; }
        .form-control {
            width: 100%; padding: .7rem 1rem;
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.15);
            border-radius: 10px; color: #fff;
            font-family: 'Kanit', sans-serif; font-size: 1rem;
            transition: border-color .2s;
        }
        .form-control:focus { outline: none; border-color: #6C63FF; }
        .form-control::placeholder { color: #666; }

        /* ===== ALERTS ===== */
        .alert { padding: .8rem 1.2rem; border-radius: 10px; margin-bottom: 1rem; font-size: .95rem; }
        .alert-danger  { background: rgba(255,77,109,.15); border: 1px solid rgba(255,77,109,.4); color: #ff8fa3; }
        .alert-success { background: rgba(76,175,80,.15);  border: 1px solid rgba(76,175,80,.4);  color: #81c784; }

        /* ===== CONTAINER ===== */
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }

        /* ===== CARD ===== */
        .card {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 16px; overflow: hidden;
        }
        .card-body { padding: 1.5rem; }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="<?= BASE_URL ?>">🎵 ConcertBook</a>
    <button class="navbar-toggle" onclick="document.getElementById('navMenu').classList.toggle('active')">
        <i class="fa-solid fa-bars"></i>
    </button>
    <ul class="navbar-nav" id="navMenu">
        <li><a href="<?= BASE_URL ?>/pages/events.php">อีเวนต์</a></li>
        <?php if (isLoggedIn()): ?>
           <li><a href="<?= BASE_URL ?>/pages/Myticket.php">บัตรของฉัน</a></li>
            <li><a href="<?= BASE_URL ?>/pages/profile.php"><?= htmlspecialchars($_SESSION['full_name']) ?></a></li>
            <li><a href="<?= BASE_URL ?>/pages/logout.php" class="btn-nav">ออกจากระบบ</a></li>
        <?php else: ?>
            <li><a href="<?= BASE_URL ?>/pages/login.php">เข้าสู่ระบบ</a></li>
            <li><a href="<?= BASE_URL ?>/pages/register.php" class="btn-nav">สมัครสมาชิก</a></li>
        <?php endif; ?>
    </ul>
</nav>
