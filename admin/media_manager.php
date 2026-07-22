<?php
session_start();
// Basic authentication check
if (!isset($_SESSION['admin']['id'])) {
    die('Odmowa dostępu. Zaloguj się jako administrator.');
}

$upload_dir = '../upload/images/';

// Ensure directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Upload
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed_extensions)) {
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($file['name']));
        
        // Handle name collision
        $target_path = $upload_dir . $filename;
        $counter = 1;
        while(file_exists($target_path)) {
            $filename_without_ext = pathinfo($filename, PATHINFO_FILENAME);
            $new_filename = $filename_without_ext . '_' . $counter . '.' . $ext;
            $target_path = $upload_dir . $new_filename;
            $counter++;
        }

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            echo json_encode(['success' => true, 'file' => basename($target_path)]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Niedozwolony format pliku.']);
    exit;
}

// Handle Delete
if (isset($_POST['delete'])) {
    $file_to_delete = basename($_POST['delete']);
    $path = $upload_dir . $file_to_delete;
    if (file_exists($path) && is_file($path)) {
        unlink($path);
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Get all files
$files = array_diff(scandir($upload_dir), array('.', '..'));
$images = [];
foreach ($files as $file) {
    if (is_file($upload_dir . $file)) {
        $images[] = $file;
    }
}
// Sort by newest first (modification time)
usort($images, function($a, $b) use ($upload_dir) {
    return filemtime($upload_dir . $b) - filemtime($upload_dir . $a);
});
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menedżer Plików</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 15px; }
        .image-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .image-card:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .image-card img {
            height: 120px;
            object-fit: contain;
            width: 100%;
            background-color: #f1f3f5;
            padding: 5px;
        }
        .image-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(13, 110, 253, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            border: 2px solid transparent;
            border-radius: 8px;
        }
        .image-card:hover .image-overlay {
            opacity: 1;
            border-color: #0d6efd;
            background: rgba(13, 110, 253, 0.15);
        }
        .delete-btn {
            position: absolute;
            top: 5px; right: 5px;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.2s;
            padding: 0.15rem 0.4rem;
        }
        .image-card:hover .delete-btn {
            opacity: 1;
        }
        #upload-zone {
            border: 2px dashed #adb5bd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }
        #upload-zone:hover, #upload-zone.dragover {
            background: #e9ecef;
            border-color: #0d6efd;
            color: #0d6efd;
        }
        .filename-text {
            font-size: 0.75rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Upload Area -->
        <div class="mb-3">
            <div id="upload-zone" onclick="document.getElementById('file-input').click()">
                <i class="bi bi-cloud-arrow-up display-6"></i>
                <p class="mt-2 mb-0 fw-bold">Kliknij lub przeciągnij pliki, aby je wgrać</p>
                <input type="file" id="file-input" style="display: none;" accept="image/*" multiple>
            </div>
            <div class="progress mt-2 d-none" id="upload-progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Images Grid -->
        <div class="row g-2" id="images-container">
            <?php foreach ($images as $img): ?>
                <div class="col-4 col-sm-3 col-md-2 image-item">
                    <div class="image-card h-100" onclick="selectImage('<?php echo htmlspecialchars($img); ?>')">
                        <button class="btn btn-sm btn-danger delete-btn" onclick="deleteImage('<?php echo htmlspecialchars($img); ?>', event)" title="Usuń plik">
                            <i class="bi bi-trash"></i>
                        </button>
                        <img src="../upload/images/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($img); ?>">
                        <div class="image-overlay">
                        </div>
                        <div class="p-1 text-center text-truncate filename-text">
                            <?php echo htmlspecialchars($img); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($images)): ?>
                <div class="col-12"><div class="alert alert-light text-center border text-muted">Brak obrazków. Wgraj jakieś pliki!</div></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Select image and pass to parent
        function selectImage(filename) {
            // Zwracamy adres url tak jak stary Roxy Fileman: /upload/images/...
            // Omija to walidację w panelu, która blokuje użycie '../' (Invalid path)
            const url = '/upload/images/' + filename; 
            
            const parentDoc = window.parent.document;
            const target = parentDoc.querySelector('.roxy_target');
            if (target) {
                target.setAttribute('src', url);
                if (typeof window.parent.closeRoxySelectFile === 'function') {
                    window.parent.closeRoxySelectFile();
                }
            }
        }

        // Delete image
        function deleteImage(filename, event) {
            event.stopPropagation(); // Prevent triggering selectImage
            if (confirm('Czy na pewno chcesz bezpowrotnie usunąć ten plik?')) {
                const fd = new FormData();
                fd.append('delete', filename);
                fetch('media_manager.php', {
                    method: 'POST',
                    body: fd
                }).then(res => res.json()).then(data => {
                    if(data.success) location.reload();
                    else alert('Wystąpił błąd podczas usuwania.');
                });
            }
        }

        // Handle File Uploads
        const uploadZone = document.getElementById('upload-zone');
        const fileInput = document.getElementById('file-input');
        const progressBar = document.getElementById('upload-progress');
        const progressBarInner = progressBar.querySelector('.progress-bar');

        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });

        function handleFiles(files) {
            if (files.length === 0) return;
            
            progressBar.classList.remove('d-none');
            uploadZone.classList.add('d-none'); // ukryj pole wgrywania na czas uploadu
            
            let uploadsCompleted = 0;
            
            for(let i=0; i<files.length; i++) {
                const fd = new FormData();
                fd.append('file', files[i]);
                
                fetch('media_manager.php', {
                    method: 'POST',
                    body: fd
                }).then(res => res.json()).then(data => {
                    uploadsCompleted++;
                    progressBarInner.style.width = ((uploadsCompleted / files.length) * 100) + '%';
                    
                    if (uploadsCompleted === files.length) {
                        setTimeout(() => location.reload(), 300);
                    }
                }).catch(err => {
                    uploadsCompleted++;
                    if (uploadsCompleted === files.length) {
                        setTimeout(() => location.reload(), 300);
                    }
                });
            }
        }
    </script>
</body>
</html>
