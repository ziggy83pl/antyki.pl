<?php

$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$classes = [
    'article', 'category', 'chat', 'info', 'offer', 'option', 'settings', 'slider', 'user'
];

foreach ($files as $file) {
    $path = $file[0];
    
    // Skip vendor and the classes themselves (to avoid modifying their namespace declarations)
    if (strpos($path, '/vendor/') !== false) continue;
    if (strpos($path, '/php/Article.php') !== false) continue;
    if (strpos($path, '/php/Category.php') !== false) continue;
    if (strpos($path, '/php/Chat.php') !== false) continue;
    if (strpos($path, '/php/Info.php') !== false) continue;
    if (strpos($path, '/php/Offer.php') !== false) continue;
    if (strpos($path, '/php/Option.php') !== false) continue;
    if (strpos($path, '/php/Settings.php') !== false) continue;
    if (strpos($path, '/php/Slider.php') !== false) continue;
    if (strpos($path, '/php/User.php') !== false) continue;
    if (strpos($path, '/admin/php/Admin.php') !== false) continue;

    $content = file_get_contents($path);
    $original = $content;

    // Refactor App classes
    foreach ($classes as $name) {
        $ucName = ucfirst($name);
        
        // new class
        $content = preg_replace("/new\s+".$name."(\s*[\(;])/i", "new \\App\\$ucName$1", $content);
        
        // static calls class::
        $content = preg_replace("/(?<![\$a-zA-Z0-9_])".$name."::/", "\\App\\$ucName::", $content);
        
        // type hinting (e.g., function(user $user) or catch(user $e) - less likely but possible)
        // wait, let's just do new and static for now.
    }

    // Refactor admin class
    $content = preg_replace("/new\s+admin(\s*[\(;])/i", "new \\App\\Admin\\Admin$1", $content);
    $content = preg_replace("/(?<![\$a-zA-Z0-9_])\App\Admin\Admin::/", "\\App\\Admin\\Admin::", $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated: $path\n";
    }
}
