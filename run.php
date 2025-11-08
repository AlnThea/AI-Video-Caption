<?php
include_once 'process.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'start') {
        // Hapus semua file terkait proses + reset session
//        @unlink('process.log');
//        @unlink('process_done');
//        @unlink('0125.wav'); // Hapus file output audio jika ada

        $_SESSION['processing'] = true;
        if (extractAudio()) {
            echo 'started';
        } else {
            unset($_SESSION['processing']);
            echo 'error';
        }
    } elseif ($_POST['action'] === 'stop') {

        if (stopProcess()) {
            // Bersihkan semua file status
            @unlink('process.log');
            @unlink('process_done');
            session_unset();
            session_destroy();
            echo 'stopped';
        } else {
            echo 'error';
        }
    }
}