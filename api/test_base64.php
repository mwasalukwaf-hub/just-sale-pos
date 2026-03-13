<?php
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$logo_path = 'C:\xampp\htdocs\justsale\uploads\logo_1773158838.png';
$mime_type = mime_content_type($logo_path);
$base64_img = base64_encode(file_get_contents($logo_path));
$logo_src = 'data:' . $mime_type . ';base64,' . $base64_img;

$html = '<html><body><h1>Test base64</h1><img src="' . $logo_src . '" /></body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();

file_put_contents('test_b64.pdf', $dompdf->output());
