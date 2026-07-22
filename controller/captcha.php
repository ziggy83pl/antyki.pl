<?php

if(!isset($settings['base_url'])){
	die('Access denied!');
}

function generateCaptcha(){
    $chars = '0123456789abc';       
    $width = 120;         
    $height = 38;          
    $number_of_characters = 6;       
    $str = '';            
    
    for ($i = 0; $i < $number_of_characters; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    
    $_SESSION['captcha'] = $str;

    // Build SVG vector image
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
    
    // Background color: dark slate-800
    $svg .= '<rect width="100%" height="100%" fill="#1e293b" rx="4"/>';
    
    // Noise lines
    for ($i = 0; $i < 6; $i++) {
        $x1 = mt_rand(0, $width);
        $y1 = mt_rand(0, $height);
        $x2 = mt_rand(0, $width);
        $y2 = mt_rand(0, $height);
        $strokeColor = 'rgb(' . mt_rand(100, 200) . ',' . mt_rand(100, 200) . ',' . mt_rand(100, 200) . ')';
        $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $strokeColor . '" stroke-width="1.5" opacity="0.4"/>';
    }
    
    // Noise circles
    for ($i = 0; $i < 10; $i++) {
        $cx = mt_rand(0, $width);
        $cy = mt_rand(0, $height);
        $r = mt_rand(2, 5);
        $fillColor = 'rgb(' . mt_rand(100, 200) . ',' . mt_rand(100, 200) . ',' . mt_rand(100, 200) . ')';
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $fillColor . '" opacity="0.3"/>';
    }
    
    // Draw characters with rotation and random colors
    $xStep = ($width - 15) / $number_of_characters;
    $xStart = 8;
    
    for ($i = 0; $i < $number_of_characters; $i++) {
        $char = $str[$i];
        $x = $xStart + ($i * $xStep) + mt_rand(-2, 2);
        $y = 25 + mt_rand(-3, 3);
        $angle = mt_rand(-15, 15);
        
        $color = 'rgb(' . mt_rand(200, 255) . ',' . mt_rand(180, 255) . ',' . mt_rand(180, 255) . ')';
        $fontSize = mt_rand(16, 20);
        
        $svg .= '<text x="' . $x . '" y="' . $y . '" fill="' . $color . '" font-size="' . $fontSize . 'px" font-family="Monospace, Courier, sans-serif" font-weight="bold" transform="rotate(' . $angle . ' ' . ($x + 4) . ' ' . ($y - 6) . ')">' . $char . '</text>';
    }
    
    $svg .= '</svg>';
    
    // Clear any previous output or whitespace BOMs to ensure clean SVG output
    if (ob_get_level()) {
        ob_clean();
    }
    
    header("Content-type: image/svg+xml");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    echo $svg;
    die();
}
generateCaptcha();

