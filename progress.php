<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$logFile = 'process.log';
$doneFile = 'process_done';

$progress = 0;
$logContent = '';
$completed = false;

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);

    // Deteksi tahap proses
    if (strpos($logContent, "Generating ASS subtitle") !== false) {
        $currentStep = 2; // Tahap transkripsi
        $progress = 33;
    } elseif (strpos($logContent, "Merging video with subtitle") !== false) {
        $currentStep = 3; // Tahap merge
        $progress = 66;
    } else {
        $currentStep = 1; // Tahap ekstrak audio
        $progress = 0;
    }

    // Hitung progress per tahap
    if (preg_match('/time=(\d+):(\d+):(\d+)/', $logContent, $matches)) {
        $currentTime = $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
        $progress += min(33, ($currentTime / $totalDuration) * 33);
    }
}

if (file_exists($doneFile) || (file_exists('output/merged.mp4') && filesize('output/merged.mp4') > 0)) {
    $completed = true;
    $progress = 100;
}

echo json_encode([
    'progress' => $progress,
    'log' => $logContent,
    'completed' => $completed
]);