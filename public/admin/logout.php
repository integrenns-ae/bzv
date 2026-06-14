<?php
require_once __DIR__ . '/../../library/_init.php';
Auth::logout();
header('Location: /admin/login.php');
exit;
