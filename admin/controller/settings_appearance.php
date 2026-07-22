<?php
/**
 * Appearance Settings Controller
 * Improved version with security, performance, and UX enhancements
 */

if (!isset(\App\Core\App::settings()['base_url'])) {
    die('Access denied!');
}

if (!$admin->is_logged()) {
    header('Location: ?controller=admin&action=login');
    exit;
}

// ===== CONFIGURATION =====
$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];
$maxUploadSize = 5 * 1024 * 1024; // 5MB
$cacheDir = '../tmp/twig/';
$uploadDir = '../uploads/images/';

// ===== HELPER FUNCTIONS =====

/**
 * Sanitize HTML content - allows safe tags only
 */
function sanitizeHtml($content) {
    $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><span><div><table><tr><td><th><tbody><thead><tfoot><blockquote><code><pre><hr><sub><sup>';
    $allowedAttributes = [
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'table' => ['class'],
        'div' => ['class', 'style'],
        'span' => ['class', 'style'],
        'p' => ['class', 'style'],
    ];

    $content = strip_tags($content, $allowedTags);

    // Remove event handlers and javascript: URLs
    $content = preg_replace('/on\w+=["\'][^"\']*["\']/i', '', $content);
    $content = preg_replace('/javascript:/i', '', $content);

    return $content;
}

/**
 * Validate image path/URL
 */
function validateImagePath($path, $allowedExtensions) {
    if (empty($path)) {
        return ['valid' => true, 'error' => null];
    }

    // Check if it's a URL
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Invalid image extension: ' . $ext];
        }
        return ['valid' => true, 'error' => null];
    }

    // Check local file
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Invalid image extension: ' . $ext];
    }

    // Check for path traversal
    if (strpos($path, '..') !== false || strpos($path, './') === 0) {
        return ['valid' => false, 'error' => 'Invalid path'];
    }

    return ['valid' => true, 'error' => null];
}

/**
 * Clean cache directory safely
 */
function cleanCacheDirectory($dir) {
    if (!is_dir($dir)) {
        return true;
    }

    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($objects as $object) {
        if ($object->isDir()) {
            rmdir($object->getPathname());
        } else {
            unlink($object->getPathname());
        }
    }

    return true;
}

/**
 * Save settings history to the database
 * 
 * @param PDO $db Database connection
 * @param int $adminId ID of the admin making changes
 * @param array $changes Array of changed settings
 */
function saveSettingsHistory($db, $adminId, $changes) {
    $sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'settings_history` 
        (admin_id, changes, created_at) VALUES (:admin_id, :changes, NOW())');
    $sth->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
    $sth->bindValue(':changes', json_encode($changes), PDO::PARAM_STR);
    $sth->execute();
}

// ===== HANDLE FILE UPLOADS (AJAX) =====
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'upload_image') {
    header('Content-Type: application/json');

    if (!checkToken('admin_save_settings_appearance')) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
        exit;
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedImageExtensions)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    if ($file['size'] > $maxUploadSize) {
        echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
        exit;
    }

    // Generate safe filename
    $filename = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Return relative path
        $relativePath = 'uploads/images/' . $filename;
        echo json_encode(['success' => true, 'path' => $relativePath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    }
    exit;
}

// ===== SAVE SETTINGS =====
if (isset($_POST['action']) && $_POST['action'] == 'save_settings_appearance' && checkToken('admin_save_settings_appearance')) {

    $errors = [];
    $changes = [];

    // Validate template
    $path = '../views/';
    $templates = [];
    if (is_dir($path)) {
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' || $result === '..' || !is_dir($path . '/' . $result)) continue;
            $templates[] = $result;
        }
    }

    if (empty($_POST['template']) || !in_array($_POST['template'], $templates)) {
        $errors[] = lang('Invalid template selected');
    }

    // Validate image paths
    $imageFields = ['logo', 'logo_facebook', 'watermark', 'favicon', 'og_image'];
    foreach ($imageFields as $field) {
        if (!empty($_POST[$field])) {
            $validation = validateImagePath($_POST[$field], $allowedImageExtensions);
            if (!$validation['valid']) {
                $errors[] = lang(ucfirst(str_replace('_', ' ', $field))) . ': ' . $validation['error'];
            }
        }
    }

    // Validate color hex codes
    $colorFields = ['primary_color', 'secondary_color', 'dark_mode_primary'];
    foreach ($colorFields as $field) {
        if (!empty($_POST[$field]) && !preg_match('/^#[a-fA-F0-9]{6}$/', $_POST[$field])) {
            $errors[] = lang('Invalid color format for') . ' ' . str_replace('_', ' ', $field);
        }
    }

    // Validate numbers
    if (!empty($_POST['items_per_page']) && (!is_numeric($_POST['items_per_page']) || $_POST['items_per_page'] < 1 || $_POST['items_per_page'] > 100)) {
        $errors[] = lang('Items per page must be between 1 and 100');
    }

    if (!empty($_POST['grid_columns']) && (!in_array($_POST['grid_columns'], ['2', '3', '4']))) {
        $errors[] = lang('Grid columns must be 2, 3, or 4');
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $sth = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'settings` SET value=:value WHERE name=:name LIMIT 1');

            // Checkbox fields (boolean)
            $checkboxFields = [
                'search_box_category', 'search_box_address', 'search_box_distance',
                'search_box_keywords', 'search_box_price', 'search_box_state',
                'search_box_type', 'search_box_options',
                'show_contact_form_offer', 'show_contact_form_profile',
                'show_breadcrumbs', 'index_box_subcategories',
                'show_number_offers_in_categories', 'rodo_alert',
                'enable_dark_mode', 'enable_pwa', 'lazy_load_images',
                'show_map_on_list', 'show_filters_sidebar'
            ];

                $sthCheck = $db->prepare('SELECT COUNT(1) FROM `' . _DB_PREFIX_ . 'settings` WHERE name=:name');
                $sthUpdate = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'settings` SET value=:value WHERE name=:name LIMIT 1');
                $sthInsert = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'settings` (name, value) VALUES (:name, :value)');

                foreach ($checkboxFields as $field) {
                    $oldValue = \App\Core\App::settings()[$field] ?? 0;
                    $newValue = isset($_POST[$field]) ? 1 : 0;
                    if ($oldValue != $newValue) {
                        $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
                    }
                    
                    $sthCheck->execute([':name' => $field]);
                    if ($sthCheck->fetchColumn() > 0) {
                        $sthUpdate->execute([':value' => $newValue, ':name' => $field]);
                    } else {
                        $sthInsert->execute([':name' => $field, ':value' => $newValue]);
                    }
                }

            // String fields
            $stringFields = [
                'template', 'logo', 'logo_facebook', 'watermark', 'favicon',
                'og_image', 'meta_title', 'meta_description', 'meta_keywords',
                'footer_top', 'footer_bottom', 'code_style', 'code_head', 'code_body',
                'primary_color', 'secondary_color', 'dark_mode_primary',
                'font_family', 'items_per_page', 'grid_columns',
                'apple_touch_icon', 'manifest_name', 'manifest_short_name'
            ];

            foreach ($stringFields as $field) {
                $oldValue = \App\Core\App::settings()[$field] ?? '';
                $newValue = isset($_POST[$field]) ? trim($_POST[$field]) : '';

                // Sanitize HTML for rich text fields
                if (in_array($field, ['footer_top', 'footer_bottom', 'rodo_alert_text'])) {
                    $newValue = sanitizeHtml($newValue);
                }

                if ($oldValue != $newValue) {
                    $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
                }

                $sthCheck->execute([':name' => $field]);
                if ($sthCheck->fetchColumn() > 0) {
                    $sthUpdate->execute([':value' => $newValue, ':name' => $field]);
                } else {
                    $sthInsert->execute([':name' => $field, ':value' => $newValue]);
                }
            }

            // RODO alert text (special handling)
            $oldRodo = \App\Core\App::settings()['rodo_alert_text'] ?? '';
            $newRodo = isset($_POST['rodo_alert_text']) ? sanitizeHtml($_POST['rodo_alert_text']) : '';
            if ($oldRodo != $newRodo) {
                $changes['rodo_alert_text'] = ['old' => $oldRodo, 'new' => $newRodo];
            }
            $sthCheck->execute([':name' => 'rodo_alert_text']);
            if ($sthCheck->fetchColumn() > 0) {
                $sthUpdate->execute([':value' => $newRodo, ':name' => 'rodo_alert_text']);
            } else {
                $sthInsert->execute([':name' => 'rodo_alert_text', ':value' => $newRodo]);
            }

            $db->commit();

            // Save history if changes were made
            if (!empty($changes)) {
                try {
                    saveSettingsHistory($db, $admin->id, $changes);
                } catch (Exception $e) {
                    // History table might not exist, log but don't fail
                    error_log('Failed to save settings history: ' . $e->getMessage());
                }
            }

            // Clean cache safely
            cleanCacheDirectory($cacheDir);

            // Regenerate manifest.json if PWA enabled
            if (!empty($_POST['enable_pwa'])) {
                $manifest = [
                    'name' => $_POST['manifest_name'] ?? 'My App',
                    'short_name' => $_POST['manifest_short_name'] ?? 'App',
                    'start_url' => '/',
                    'display' => 'standalone',
                    'background_color' => $_POST['primary_color'] ?? '#ffffff',
                    'theme_color' => $_POST['primary_color'] ?? '#ffffff',
                    'icons' => []
                ];

                if (!empty($_POST['logo_facebook'])) {
                    $manifest['icons'][] = [
                        'src' => $_POST['logo_facebook'],
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    ];
                }

                file_put_contents('../manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            }

            // Regenerate custom CSS file
            $customCss = '';
            if (!empty($_POST['primary_color'])) {
                $customCss .= ':root { --primary-color: ' . $_POST['primary_color'] . '; }' . PHP_EOL;
            }
            if (!empty($_POST['secondary_color'])) {
                $customCss .= ':root { --secondary-color: ' . $_POST['secondary_color'] . '; }' . PHP_EOL;
            }
            if (!empty($_POST['font_family'])) {
                $customCss .= 'body { font-family: ' . $_POST['font_family'] . ', sans-serif; }' . PHP_EOL;
            }
            if (!empty($_POST['code_style'])) {
                $customCss .= '/* Custom CSS */' . PHP_EOL . $_POST['code_style'] . PHP_EOL;
            }

            if (!empty($customCss)) {
                if (!is_dir('../assets/css/')) {
                    mkdir('../assets/css/', 0755, true);
                }
                file_put_contents('../assets/css/custom-theme.css', $customCss);
            }

            getSettings();

            // Regenerate CSRF token after successful save
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $render_variables['alert_success'][] = lang('Changes have been saved');

        } catch (Exception $e) {
            $db->rollBack();
            $render_variables['alert_danger'][] = lang('Error saving settings: ') . $e->getMessage();
            error_log('Settings save error: ' . $e->getMessage());
        }
    } else {
        $render_variables['alert_danger'] = array_merge(
            $render_variables['alert_danger'] ?? [],
            $errors
        );
    }
}

// ===== GET LIST OF TEMPLATES (with caching) =====
$cacheKey = 'templates_list';
$cacheFile = '../tmp/cache/' . $cacheKey . '.json';
$cacheTime = 3600; // 1 hour

$templates = [];
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    $templates = json_decode(file_get_contents($cacheFile), true);
} else {
    $path = '../views/';
    if (is_dir($path)) {
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' || $result === '..' || !is_dir($path . '/' . $result)) continue;
            $templates[] = $result;
        }
    }

    // Ensure cache directory exists
    if (!is_dir('../tmp/cache/')) {
        mkdir('../tmp/cache/', 0755, true);
    }
    file_put_contents($cacheFile, json_encode($templates));
}

$render_variables['templates'] = $templates;

// ===== GET SETTINGS HISTORY (last 10 entries) =====
try {
    $sth = $db->prepare('SELECT sh.*, a.username as admin_name 
        FROM `' . _DB_PREFIX_ . 'settings_history` sh 
        LEFT JOIN `' . _DB_PREFIX_ . 'admins` a ON sh.admin_id = a.id 
        ORDER BY sh.created_at DESC LIMIT 10');
    $sth->execute();
    $history = $sth->fetchAll(PDO::FETCH_ASSOC);
    foreach ($history as &$entry) {
        $entry['changes'] = json_decode($entry['changes'], true);
    }
    $render_variables['settings_history'] = $history;
} catch (Exception $e) {
    $render_variables['settings_history'] = [];
}

// ===== AVAILABLE OPTIONS =====
$render_variables['font_families'] = [
    'system-ui' => 'Czcionka systemowa (System UI)',
    'Arial' => 'Arial',
    'Helvetica' => 'Helvetica',
    'Georgia' => 'Georgia',
    'Times New Roman' => 'Times New Roman',
    'Verdana' => 'Verdana',
    'Roboto' => 'Roboto (Google Font)',
    'Open Sans' => 'Open Sans (Google Font)',
    'Lato' => 'Lato (Google Font)',
    'Montserrat' => 'Montserrat (Google Font)',
    'Poppins' => 'Poppins (Google Font)'
];

$render_variables['grid_columns_options'] = [
    '2' => '2 kolumny',
    '3' => '3 kolumny',
    '4' => '4 kolumny'
];

$render_variables['items_per_page_options'] = [8, 12, 16, 20, 24, 32, 48];

$title = lang('Appearance settings') . ' - ' . $title_default;