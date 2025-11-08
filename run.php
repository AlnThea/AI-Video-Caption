<?php
// run.php - UPDATE UNTUK SUPPORT FILE UPLOAD
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'process.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload') {
            // Handle file upload
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'upload/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate unique filename
                $originalName = $_FILES['video']['name'];
                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $fileExtension;
                $filepath = $uploadDir . $filename;

                // Move uploaded file
                if (move_uploaded_file($_FILES['video']['tmp_name'], $filepath)) {
                    // Store filename in session untuk diproses
                    $_SESSION['current_video'] = $filename;
                    echo json_encode([
                        'status' => 'uploaded',
                        'message' => 'File berhasil diupload: ' . $originalName,
                        'filename' => $filename
                    ]);
                } else {
                    throw new Exception('Gagal menyimpan file');
                }
            } else {
                throw new Exception('Tidak ada file yang diupload atau error upload');
            }

        } elseif ($action === 'start') {
            // Start processing dengan file yang diupload
            $filename = $_POST['filename'] ?? ($_SESSION['current_video'] ?? '');

            if (empty($filename)) {
                throw new Exception('Tidak ada file video yang dipilih');
            }

            $_SESSION['processing'] = true;
            $_SESSION['current_video'] = $filename;

            // Reset files
            @unlink('process.log');
            @unlink('process_done');
            @unlink('process_error');

            if (extractAudio($filename)) {
                echo json_encode([
                    'status' => 'started',
                    'message' => 'Proses dimulai untuk file: ' . $filename
                ]);
            } else {
                unset($_SESSION['processing']);
                echo json_encode(['status' => 'error', 'message' => 'Gagal memulai proses']);
            }

        } elseif ($action === 'stop') {
            if (stopProcess()) {
                session_unset();
                session_destroy();
                echo json_encode(['status' => 'stopped', 'message' => 'Proses dihentikan']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghentikan proses']);
            }

        } elseif ($action === 'status') {
            $status = getProcessStatus();
            echo json_encode($status);

        } elseif ($action === 'get_video') {
            // Return URL video hasil
            if (file_exists('output/merged.mp4')) {
                echo json_encode(['video_url' => 'output/merged.mp4?t=' . time()]);
            } else {
                echo json_encode(['video_url' => null]);
            }

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali: ' . $action]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
}
?>