<?php
// run.php - PASTIKAN INI VERSI TERBARU
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

        if ($action === 'start') {
            $_SESSION['processing'] = true;

            // Reset files
            @unlink('process.log');
            @unlink('process_done');
            @unlink('process_error');

            if (extractAudio()) {
                echo json_encode(['status' => 'started', 'message' => 'Proses dimulai']);
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