<?php
session_start();
include('db_connect.php');

switch ($payment_method) {
    case 'BCA':
        header("Location: BCA_badminton.php");
        exit;
    case 'OVO':
        header("Location: OVO.html");
        exit;
    case 'QRIS':
        header("Location: QRIS.html");
        exit;
    default:
        header("Location: BCA_badminton.php");
        exit;
}
?>