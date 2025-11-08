<?php
// run.php - PASTIKAN JSON RESPONSE VALID
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'process.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function untuk send JSON response
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload') {
            // Handle file upload
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'upload/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // PERTAHANKAN NAMA FILE ASLI (dengan sanitize)
                $originalName = $_FILES['video']['name'];
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName); // Sanitize nama file
                $filepath = $uploadDir . $sanitizedName;

                // Cek jika file sudah ada, tambah timestamp
                if (file_exists($filepath)) {
                    $fileInfo = pathinfo($sanitizedName);
                    $filename = $fileInfo['filename'] . '_' . time() . '.' . $fileInfo['extension'];
                    $filepath = $uploadDir . $filename;
                } else {
                    $filename = $sanitizedName;
                }

                // Move uploaded file
                if (move_uploaded_file($_FILES['video']['tmp_name'], $filepath)) {
                    // Store filename in session untuk diproses
                    $_SESSION['current_video'] = $filename;
                    $_SESSION['original_filename'] = $filename;

                    sendJsonResponse([
                        'status' => 'uploaded',
                        'message' => 'File berhasil diupload: ' . $originalName,
                        'filename' => $filename
                    ]);
                } else {
                    throw new Exception('Gagal menyimpan file');
                }
            } else {
                $errorMsg = 'Tidak ada file yang diupload atau error upload';
                if (isset($_FILES['video'])) {
                    $errorMsg .= ' (Error code: ' . $_FILES['video']['error'] . ')';
                }
                throw new Exception($errorMsg);
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
                sendJsonResponse([
                    'status' => 'started',
                    'message' => 'Proses dimulai untuk file: ' . $filename
                ]);
            } else {
                unset($_SESSION['processing']);
                sendJsonResponse(['status' => 'error', 'message' => 'Gagal memulai proses']);
            }

        } elseif ($action === 'stop') {
            if (stopProcess()) {
                session_unset();
                session_destroy();
                sendJsonResponse(['status' => 'stopped', 'message' => 'Proses dihentikan']);
            } else {
                sendJsonResponse(['status' => 'error', 'message' => 'Gagal menghentikan proses']);
            }

        } elseif ($action === 'status') {
            $status = getProcessStatus();
            sendJsonResponse($status);

        } elseif ($action === 'get_video') {
            // Return URL video hasil berdasarkan nama file asli
            $currentVideo = $_SESSION['current_video'] ?? '';
            if (!empty($currentVideo)) {
                $baseName = pathinfo($currentVideo, PATHINFO_FILENAME);
                $outputVideo = 'output/' . $baseName . '_with_subtitle.mp4';
                if (file_exists($outputVideo)) {
                    sendJsonResponse(['video_url' => $outputVideo . '?t=' . time()]);
                } else {
                    sendJsonResponse(['video_url' => null]);
                }
            } else {
                sendJsonResponse(['video_url' => null]);
            }

        } else {
            sendJsonResponse(['status' => 'error', 'message' => 'Action tidak dikenali: ' . $action]);
        }
    } catch (Exception $e) {
        sendJsonResponse(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
} else {
    sendJsonResponse(['status' => 'error', 'message' => 'Method tidak diizinkan']);
}
?>