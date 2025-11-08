<?php
function mergeVideoWithSubtitle() {
    $videoFile = 'upload/0512.mp4';
    $subtitleFile = 'subtitles/output.ass';
    $outputFile = 'output/merged_video.mp4';
    $logFile = 'process.log';
    $tempFile = 'output/merged_video.mp4.part'; // File temporary FFmpeg

    // Bersihkan file lama
    @unlink($outputFile);
    @unlink($tempFile);

    // Validasi file
    if (!file_exists($videoFile) || !file_exists($subtitleFile)) {
        file_put_contents($logFile, "❌ File video/subtitle tidak ditemukan\n", FILE_APPEND);
        return false;
    }

    // Command FFmpeg untuk merge dengan progress
    $command = "ffmpeg -y -i " . escapeshellarg($videoFile) .
        " -vf \"ass=" . escapeshellarg($subtitleFile) . "\" " .
        " -progress " . escapeshellarg($tempFile) . " " .
        escapeshellarg($outputFile) . " 2>&1";

    // Mulai proses
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];

    $process = proc_open($command, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        file_put_contents($logFile, "❌ Gagal memulai FFmpeg\n", FILE_APPEND);
        return false;
    }

    // Baca output real-time
    while (true) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        if ($stderr !== false && $stderr !== '') {
            file_put_contents($logFile, $stderr, FILE_APPEND);
        }

        // Cek status proses
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }

        usleep(100000); // Delay 100ms
    }

    // Tutup proses
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    // Hapus file temporary
    @unlink($tempFile);

    return file_exists($outputFile);
}

// Eksekusi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'merge') {
    session_start();
    if (mergeVideoWithSubtitle()) {
        echo "merged";
    } else {
        echo "error";
    }
}
?>