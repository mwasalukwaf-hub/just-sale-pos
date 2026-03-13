<?php
// api/report_functions.php

use Dompdf\Dompdf;
use Dompdf\Options;

function get_report_settings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function format_amount($amount) {
    return number_format($amount, 0, '.', ',');
}

function get_report_styles() {
    return "
        @page { margin: 40px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #444; margin: 0; padding: 0; line-height: 1.5; }
        .report-container { width: 100%; }
        
        .header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eef2f7; }
        .header table { width: 100%; border-collapse: collapse; }
        .logo { max-height: 60px; max-width: 180px; filter: grayscale(10%); }
        .company-info { text-align: right; }
        .company-name { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.5px; }
        .company-details { font-size: 10px; color: #64748b; line-height: 1.4; }
        
        .report-info { margin-bottom: 30px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9; }
        .report-title { font-size: 18px; font-weight: 800; color: #4361ee; margin: 0 0 10px 0; letter-spacing: -0.5px; }
        .report-meta { width: 100%; border-collapse: collapse; }
        .report-meta td { padding: 4px 0; font-size: 10px; color: #475569; }
        .label { font-weight: bold; color: #1e293b; text-transform: uppercase; font-size: 9px; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 12px 8px; text-align: left; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; }
        .table td { border-bottom: 1px solid #f1f5f9; padding: 10px 8px; font-size: 10px; color: #334155; vertical-align: middle; }
        .table tr:nth-child(even) { background-color: #fcfdfe; }
        .table tfoot th { background: #f8fafc; border-top: 2px solid #4361ee; padding: 12px 8px; font-size: 11px; color: #0f172a; }
        
        .text-end, .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-primary { color: #4361ee; }
        .text-success { color: #10b981; }
        .text-danger { color: #ef4444; }
        
        .badge { padding: 4px 8px; border-radius: 9999px; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-success { background: #d1fae5; color: #065f46; }
        .bg-danger { background: #fee2e2; color: #991b1b; }
        .bg-warning { background: #fef3c7; color: #92400e; }
        .bg-info { background: #e0f2fe; color: #075985; }
        .bg-secondary { background: #f1f5f9; color: #475569; }
        
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px; }
    ";
}

function render_report_header($title, $settings, $format, $meta = []) {
    $logo_html = '';
    if ($format === 'pdf' && !empty($settings['company_logo'])) {
        $logo_path = realpath(__DIR__ . '/../' . $settings['company_logo']);
        if ($logo_path && file_exists($logo_path)) {
            $mime_type = mime_content_type($logo_path);
            $base64_img = base64_encode(file_get_contents($logo_path));
            $logo_html = '<img src="data:' . $mime_type . ';base64,' . $base64_img . '" class="logo">';
        }
    }

    $html = '<div class="header"><table><tr>';
    $html .= '<td width="50%">' . $logo_html . '</td>';
    $html .= '<td width="50%" class="company-info">';
    $html .= '<div class="company-name">' . ($settings['company_name'] ?? 'JUSTSALE POS') . '</div>';
    $html .= '<div class="company-details">';
    $html .= ($settings['company_address'] ?? '') . '<br>';
    if (!empty($settings['company_city'])) $html .= $settings['company_city'] . ', ';
    if (!empty($settings['company_country'])) $html .= $settings['company_country'];
    $html .= '<br>';
    if (!empty($settings['company_phone'])) $html .= 'Tel: ' . $settings['company_phone'] . ' | ';
    if (!empty($settings['company_email'])) $html .= 'Email: ' . $settings['company_email'];
    if (!empty($settings['company_website'])) $html .= '<br>Web: ' . $settings['company_website'];
    if (!empty($settings['company_tin'])) $html .= ' | TIN: ' . $settings['company_tin'];
    if (!empty($settings['company_vrn'])) $html .= ' | VRN: ' . $settings['company_vrn'];
    $html .= '</div></td></tr></table></div>';

    $html .= '<div class="report-info">';
    $html .= '<h1 class="report-title">' . $title . '</h1>';
    $html .= '<table class="report-meta">';
    
    foreach (array_chunk($meta, 2, true) as $chunk) {
        $html .= '<tr>';
        foreach ($chunk as $label => $value) {
            $html .= '<td width="20%"><strong>' . $label . ':</strong></td>';
            $html .= '<td width="30%">' . $value . '</td>';
        }
        if (count($chunk) < 2) {
            $html .= '<td width="20%"></td><td width="30%"></td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table></div>';
    
    return $html;
}

function render_report_footer($settings) {
    return '<div class="footer">' . ($settings['company_name'] ?? 'JUSTSALE') . ' POS System | Powered by <a href="http://franklin.co.tz/justsalePOS" style="color: #4361ee; text-decoration: none; font-weight: bold;">Franklin</a> | ' . date('Y-m-d H:i:s') . '</div>';
}

function export_to_pdf($html, $filename, $orientation = 'portrait', $stream = true) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    
    // Wrap in HTML structure
    $full_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . get_report_styles() . '</style></head><body>';
    $full_html .= '<div class="report-container">' . $html . '</div></body></html>';
    
    $dompdf->loadHtml($full_html);
    $dompdf->setPaper('A4', $orientation);
    $dompdf->render();
    
    if ($stream) {
        $dompdf->stream($filename, ["Attachment" => 1]);
        exit;
    } else {
        return $dompdf->output();
    }
}

function export_to_excel($html, $filename, $title, $stream = true) {
    if ($stream) {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
    
    $content = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . get_report_styles() . '</style></head><body>';
    $content .= '<div class="report-container">' . $html . '</div></body></html>';
    
    if ($stream) {
        echo $content;
        exit;
    } else {
        return $content;
    }
}
