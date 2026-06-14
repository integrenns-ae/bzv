<?php
require_once __DIR__ . '/../../library/_init.php';
Auth::logout();
header('Location: /mitglieder/login.php');
exit;
