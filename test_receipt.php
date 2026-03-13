<?php
$_SERVER['REQUEST_URI'] = '/api/print_receipt.php?id=1';
$_GET['id'] = 1;
session_start();
$_SESSION['user_id'] = 1;

ob_start();
require 'api/print_receipt.php';
$pdf = ob_get_clean();
file_put_contents('test_receipt.pdf', $pdf);
