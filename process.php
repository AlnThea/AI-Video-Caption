<?php
ini_set('max_execution_time', 0); // No time limit
ignore_user_abort(true);

function isWindows() {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

// Juga update startBackgroundProcess() untuk terima parameter
function startBackgroundProcess($filename = null) {
    $logFile = 'process.log';

    // Reset log file
    if ($filename) {
        file_put_contents($logFile, "🚀 Memulai proses untuk file: $filename - " . date('Y-m-d H:i:s') . "\n");
    } else {
        file_put_contents($logFile, "🚀 Memulai proses background... " . date('Y-m-d H:i:s') . "\n");
    }

    if (isWindows()) {
        // Windows background process - pass filename sebagai argument
        $filenameParam = $filename ? " \"$filename\"" : "";
        $cmd = 'start /B php-cgi -f background_processor.php' . $filenameParam . ' > nul 2>&1';
        pclose(popen($cmd, 'r'));
    } else {
        // Linux background process
        $filenameParam = $filename ? " \"$filename\"" : "";
        $cmd = 'php background_processor.php' . $filenameParam . ' > /dev/null 2>&1 &';
        shell_exec($cmd);
    }

    file_put_contents($logFile, "✅ Proses background dimulai\n", FILE_APPEND);
    return true;
}

function extractAudio($filename = null) {
    // Jika tidak ada filename, gunakan default atau dari session
    if ($filename === null) {
        $filename = $_SESSION['current_video'] ?? '0512.mp4';
    }

    // Hanya start background process dan langsung return
    return startBackgroundProcess();
}

function getProcessStatus() {
    $logFile = 'process.log';
    $doneFile = 'process_done';
    $errorFile = 'process_error';

    if (file_exists($errorFile)) {
        $error = file_get_contents($errorFile);
        unlink($errorFile);
        return ['status' => 'error', 'message' => $error];
    }

    if (file_exists($doneFile)) {
        unlink($doneFile);
        return ['status' => 'completed', 'message' => 'Proses selesai'];
    }

    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        if (strpos($logContent, '✅ PROSES SELESAI') !== false) {
            return ['status' => 'completed', 'message' => 'Proses selesai'];
        }
        return ['status' => 'processing', 'message' => 'Sedang diproses', 'log' => $logContent];
    }

    return ['status' => 'ready', 'message' => 'Siap memulai'];
}

function stopProcess() {
    if (isWindows()) {
        shell_exec('taskkill /F /IM ffmpeg.exe 2>&1');
        shell_exec('taskkill /F /IM python.exe 2>&1');
        shell_exec('taskkill /F /IM php-cgi.exe 2>&1');
    } else {
        shell_exec('pkill -f ffmpeg');
        shell_exec('pkill -f python');
        shell_exec('pkill -f php');
    }

    // Cleanup files
    @unlink('process.log');
    @unlink('process_done');
    @unlink('process_error');

    return true;
}

// Fungsi untuk cek dependencies
function checkDependencies() {
    if (isWindows()) {
        $ffmpegPath = '"C:\\ProgramData\\chocolatey\\bin\\ffmpeg.exe"';
        $pythonPath = '"C:\\Program Files\\PhpWebStudy-Data\\app\\python-3.13.3\\python.exe"';
    } else {
        $ffmpegPath = '/usr/bin/ffmpeg';
        $pythonPath = '/usr/bin/python3';
    }

    echo "<pre>";
    echo "=== SYSTEM INFORMATION ===\n";
    echo "OS: " . PHP_OS . "\n";
    echo "Working Dir: " . getcwd() . "\n\n";

    echo "=== FFMPEG CHECK ===\n";
    echo shell_exec("$ffmpegPath -version 2>&1");

    echo "=== PYTHON CHECK ===\n";
    echo shell_exec("$pythonPath --version 2>&1");

    echo "=== VOSK MODEL CHECK ===\n";
    $modelDir = "vosk-model/vosk-model-en-us-0.22";
    echo "Model exists: " . (is_dir($modelDir) ? 'Yes' : 'No') . "\n";

    echo "=== FILE CHECK ===\n";
    echo "transcribe.py: " . (file_exists('transcribe.py') ? 'Yes' : 'No') . "\n";
    echo "upload/0512.mp4: " . (file_exists('upload/0512.mp4') ? 'Yes' : 'No') . "\n";
    echo "</pre>";
}
?>