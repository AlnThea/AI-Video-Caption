<?php
// background_processor.php
set_time_limit(0);
ini_set('max_execution_time', 0);

function runProcess() {
    $ffmpegPath = '"C:\\ProgramData\\chocolatey\\bin\\ffmpeg.exe"';
    $pythonPath = '"C:\\Program Files\\PhpWebStudy-Data\\app\\python-3.13.3\\python.exe"';

    $inputFile = 'upload/0512.mp4';
    $outputFile = 'audio/0512.wav';
    $logFile = 'process.log';

    // Buat directories
    if (!is_dir('audio')) mkdir('audio', 0777, true);
    if (!is_dir('output')) mkdir('output', 0777, true);
    if (!is_dir('subtitles')) mkdir('subtitles', 0777, true);

    try {
        // STEP 1: Ekstrak Audio
        file_put_contents($logFile, "\n🎧 STEP 1: Ekstrak audio...\n", FILE_APPEND);
        $ffmpegCmd = "$ffmpegPath -y -i $inputFile -vn -ar 16000 -ac 1 -f wav $outputFile 2>&1";
        file_put_contents($logFile, "Command: $ffmpegCmd\n", FILE_APPEND);
        $output = shell_exec($ffmpegCmd);
        file_put_contents($logFile, $output . "\n", FILE_APPEND);

        if (!file_exists($outputFile)) {
            throw new Exception("Gagal membuat file audio: $outputFile");
        }
        file_put_contents($logFile, "✅ Audio berhasil: " . filesize($outputFile) . " bytes\n\n", FILE_APPEND);

        // STEP 2: Transkripsi
        file_put_contents($logFile, "🎤 STEP 2: Transkripsi audio...\n", FILE_APPEND);
        $pythonCmd = "$pythonPath transcribe.py 2>&1";
        file_put_contents($logFile, "Command: $pythonCmd\n", FILE_APPEND);
        $pythonOutput = shell_exec($pythonCmd);
        file_put_contents($logFile, $pythonOutput . "\n", FILE_APPEND);

        // Cek jika subtitle berhasil dibuat
        if (!file_exists("subtitles/output.ass")) {
            throw new Exception("File subtitle tidak dibuat: subtitles/output.ass");
        }
        file_put_contents($logFile, "✅ Subtitle berhasil: subtitles/output.ass\n\n", FILE_APPEND);

        // STEP 3: Merge Video
        file_put_contents($logFile, "🎬 STEP 3: Merge video dengan subtitle...\n", FILE_APPEND);
        $mergeCmd = "$ffmpegPath -y -i $inputFile -vf \"ass=subtitles/output.ass\" output/merged.mp4 2>&1";
        file_put_contents($logFile, "Command: $mergeCmd\n", FILE_APPEND);
        $mergeOutput = shell_exec($mergeCmd);
        file_put_contents($logFile, $mergeOutput . "\n", FILE_APPEND);

        // Cek jika video berhasil dibuat
        if (!file_exists("output/merged.mp4")) {
            throw new Exception("File video output tidak dibuat: output/merged.mp4");
        }
        file_put_contents($logFile, "✅ Video berhasil: output/merged.mp4 - " . filesize("output/merged.mp4") . " bytes\n\n", FILE_APPEND);

        // Tandai proses selesai
        file_put_contents('process_done', 'completed');
        file_put_contents($logFile, "🎉 PROSES SELESAI!\n", FILE_APPEND);

    } catch (Exception $e) {
        file_put_contents($logFile, "❌ ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        file_put_contents('process_error', $e->getMessage());
    }
}

// Jalankan proses
runProcess();
?>