<?php
$files = [
    'article', 'category', 'chat', 'info', 'offer', 'option', 'settings', 'slider', 'user'
];

foreach ($files as $name) {
    $oldPath = __DIR__ . '/php/' . $name . '_class.php';
    if (!file_exists($oldPath)) continue;
    
    $content = file_get_contents($oldPath);
    
    // Add namespace and use statements
    $newClassName = ucfirst($name);
    
    // Find where to put namespace (after declare(strict_types=1); or <?php)
    if (strpos($content, 'declare(strict_types=1);') !== false) {
        $content = preg_replace('/(declare\(strict_types=1\);)/i', "$1\n\nnamespace App;\n\nuse PDO;\nuse Exception;\n", $content);
    } else {
        $content = preg_replace('/(<\?php\s*)/i', "$1\nnamespace App;\n\nuse PDO;\nuse Exception;\n", $content);
    }
    
    // Replace class name declaration (case insensitive)
    $content = preg_replace('/class\s+'.$name.'\s*{/i', 'class '.$newClassName.' {', $content);
    
    $newPath = __DIR__ . '/php/' . $newClassName . '.php';
    file_put_contents($newPath, $content);
    unlink($oldPath);
}
echo "Done php/\n";

$adminFiles = [
    'admin'
];

foreach ($adminFiles as $name) {
    $oldPath = __DIR__ . '/admin/php/' . $name . '.class.php';
    if (!file_exists($oldPath)) continue;
    
    $content = file_get_contents($oldPath);
    $newClassName = ucfirst($name);
    
    if (strpos($content, 'declare(strict_types=1);') !== false) {
        $content = preg_replace('/(declare\(strict_types=1\);)/i', "$1\n\nnamespace App\Admin;\n\nuse PDO;\nuse Exception;\n", $content);
    } else {
        $content = preg_replace('/(<\?php\s*)/i', "$1\nnamespace App\Admin;\n\nuse PDO;\nuse Exception;\n", $content);
    }
    
    $content = preg_replace('/class\s+'.$name.'\s*{/i', 'class '.$newClassName.' {', $content);
    
    $newPath = __DIR__ . '/admin/php/' . $newClassName . '.php';
    file_put_contents($newPath, $content);
    unlink($oldPath);
}
echo "Done admin/php/\n";
