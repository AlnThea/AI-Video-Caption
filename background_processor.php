<?php
// background_processor.php - FIXED VERSION
set_time_limit(0);
ini_set('max_execution_time', 0);

// Mulai session untuk akses session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEBUG: Log semua available data
file_put_contents('debug_background.log',
    "=== BACKGROUND PROCESSOR STARTED ===\n" .
    "Time: " . date('Y-m-d H:i:s') . "\n" .
    "argv: " . print_r($argv, true) . "\n" .
    "SESSION: " . print_r($_SESSION, true) . "\n",
FILE_APPEND);

// Method 1: Baca dari file (PRIORITY)
$filename = '';
$filenameFile = 'current_filename.txt';

if (file_exists($filenameFile)) {
    $filename = trim(file_get_contents($filenameFile));
    file_put_contents('debug_background.log', "Read from file: $filename\n", FILE_APPEND);

    // Hapus file setelah dibaca
    unlink($filenameFile);
}

// Method 2: Dari session
if (empty($filename) && isset($_SESSION['current_video']) && !empty($_SESSION['current_video'])) {
    $filename = $_SESSION['current_video'];
    file_put_contents('debug_background.log', "Read from session: $filename\n", FILE_APPEND);
}

// Method 3: Dari command line arguments
if (empty($filename) && isset($argv[1]) && !empty($argv[1])) {
    $filename = $argv[1];
    file_put_contents('debug_background.log', "Read from argv: $filename\n", FILE_APPEND);
}

// ERROR jika tidak ada filename
if (empty($filename)) {
    $errorMsg = "❌ ERROR: Tidak ada filename yang provided\n";
    file_put_contents('process.log', $errorMsg, FILE_APPEND);
    file_put_contents('process_error', $errorMsg);
    file_put_contents('debug_background.log', $errorMsg, FILE_APPEND);
    exit(1);
}

file_put_contents('debug_background.log', "Final filename: $filename\n", FILE_APPEND);

function runProcess($filename) {
    $ffmpegPath = '"C:\\ProgramData\\chocolatey\\bin\\ffmpeg.exe"';
    $pythonPath = '"C:\\Program Files\\PhpWebStudy-Data\\app\\python-3.13.3\\python.exe"';

    $baseName = pathinfo($filename, PATHINFO_FILENAME);

    $inputFile = 'upload/' . $filename;
    $outputFile = 'audio/' . $baseName . '.wav';
    $subtitleFile = 'subtitles/' . $baseName . '.ass';
    $outputVideo = 'output/' . $baseName . '_with_subtitle.mp4';
    $logFile = 'process.log';

    file_put_contents($logFile, "\n📁 FILE PATHS:\n", FILE_APPEND);
    file_put_contents($logFile, "Input: $inputFile\n", FILE_APPEND);
    file_put_contents($logFile, "Audio: $outputFile\n", FILE_APPEND);
    file_put_contents($logFile, "Subtitle: $subtitleFile\n", FILE_APPEND);
    file_put_contents($logFile, "Output: $outputVideo\n\n", FILE_APPEND);

    // Buat directories
    if (!is_dir('audio')) mkdir('audio', 0777, true);
    if (!is_dir('output')) mkdir('output', 0777, true);
    if (!is_dir('subtitles')) mkdir('subtitles', 0777, true);

    try {
        // STEP 1: Ekstrak Audio dari file uploaded
        file_put_contents($logFile, "🎧 STEP 1: Ekstrak audio dari $filename...\n", FILE_APPEND);

        if (!file_exists($inputFile)) {
            throw new Exception("File input tidak ditemukan: $inputFile");
        }

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
        $pythonCmd = "$pythonPath transcribe.py \"$filename\" 2>&1";
        file_put_contents($logFile, "Command: $pythonCmd\n", FILE_APPEND);
        $pythonOutput = shell_exec($pythonCmd);
        file_put_contents($logFile, $pythonOutput . "\n", FILE_APPEND);

        if (!file_exists($subtitleFile)) {
            throw new Exception("File subtitle tidak dibuat: $subtitleFile");
        }
        file_put_contents($logFile, "✅ Subtitle berhasil: $subtitleFile\n\n", FILE_APPEND);

        // STEP 3: Merge Video
        file_put_contents($logFile, "🎬 STEP 3: Merge video dengan subtitle...\n", FILE_APPEND);
        $mergeCmd = "$ffmpegPath -y -i $inputFile -vf \"ass=$subtitleFile\" $outputVideo 2>&1";
        file_put_contents($logFile, "Command: $mergeCmd\n", FILE_APPEND);
        $mergeOutput = shell_exec($mergeCmd);
        file_put_contents($logFile, $mergeOutput . "\n", FILE_APPEND);

        if (!file_exists($outputVideo)) {
            throw new Exception("File video output tidak dibuat: $outputVideo");
        }
        file_put_contents($logFile, "✅ Video berhasil: $outputVideo - " . filesize($outputVideo) . " bytes\n\n", FILE_APPEND);

        file_put_contents('process_done', 'completed');
        file_put_contents($logFile, "🎉 PROSES SELESAI!\n", FILE_APPEND);

        return true;

    } catch (Exception $e) {
        file_put_contents($logFile, "❌ ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        file_put_contents('process_error', $e->getMessage());
        return false;
    }
}

// Jalankan proses
runProcess($filename);
?>