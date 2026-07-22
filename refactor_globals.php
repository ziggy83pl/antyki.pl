<?php
$files = glob(__DIR__ . '/php/*.php');
$files = array_merge($files, glob(__DIR__ . '/admin/php/*.php'));

foreach ($files as $file) {
    if (basename($file) === 'global.php') continue;
    
    $content = file_get_contents($file);
    $original = $content;

    // Find all 'global $var1, $var2, ...;'
    $content = preg_replace_callback('/global\s+([^;]+);/i', function($m) {
        $vars = explode(',', str_replace(' ', '', $m[1]));
        $replacement = '';
        $kept = [];
        foreach ($vars as $var) {
            if ($var === '$db') {
                $replacement .= '$db = \App\Core\App::db();'."\n        ";
            } elseif ($var === '$settings') {
                $replacement .= '$settings = \App\Core\App::settings();'."\n        ";
            } elseif ($var === '$purifier') {
                $replacement .= '$purifier = \App\Core\App::purifier();'."\n        ";
            } else {
                $kept[] = $var;
            }
        }
        
        if (!empty($kept)) {
            $replacement = 'global ' . implode(', ', $kept) . ';' . "\n        " . $replacement;
        }
        
        return rtrim($replacement);
    }, $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated globals in: $file\n";
    }
}
