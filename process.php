<?php
ini_set('max_execution_time', 300);

function isWindows() {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function extractAudio() {
    // Konfigurasi path untuk Windows Anda
    if (isWindows()) {
        // Windows paths - sesuai dengan lokasi di sistem Anda
        $ffmpegPath = '"C:\\ProgramData\\chocolatey\\bin\\ffmpeg.exe"';
        $pythonPath = '"C:\\Program Files\\PhpWebStudy-Data\\app\\python-3.13.3\\python.exe"';
    } else {
        // Linux paths (fallback)
        $ffmpegPath = '/usr/bin/ffmpeg';
        $pythonPath = '/home/ernest/.local/share/voskenv/bin/python';
    }

    // Path file
    $inputFile = 'upload/0512.mp4';
    $outputFile = 'audio/0512.wav';
    $logFile = 'process.log';
    $doneFile = 'process_done';

    // Pastikan directory exists
    if (!is_dir('audio')) mkdir('audio', 0777, true);
    if (!is_dir('output')) mkdir('output', 0777, true);
    if (!is_dir('subtitles')) mkdir('subtitles', 0777, true);

    file_put_contents($logFile, "🚀 Memulai proses ekstraksi audio...\n", FILE_APPEND);
    file_put_contents($logFile, "FFmpeg: $ffmpegPath\n", FILE_APPEND);
    file_put_contents($logFile, "Python: $pythonPath\n", FILE_APPEND);

    // 1. Ekstrak Audio
    $ffmpegCmd = "$ffmpegPath -y -i $inputFile -vn -ar 16000 -ac 1 -f wav $outputFile 2>&1";
    file_put_contents($logFile, "\n🎧 Ekstrak audio...\n", FILE_APPEND);
    file_put_contents($logFile, "Command: $ffmpegCmd\n", FILE_APPEND);
    $output = shell_exec($ffmpegCmd);
    file_put_contents($logFile, $output, FILE_APPEND);

    // Cek jika audio berhasil dibuat
    if (file_exists($outputFile)) {
        file_put_contents($logFile, "✅ Audio berhasil dibuat: " . filesize($outputFile) . " bytes\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "❌ GAGAL: File audio tidak dibuat\n", FILE_APPEND);
        return false;
    }

    // 2. Transkripsi ke Subtitle
    file_put_contents($logFile, "\n🎤 Transkripsi audio...\n", FILE_APPEND);
    $pythonCmd = "$pythonPath transcribe.py 2>&1";
    file_put_contents($logFile, "Command: $pythonCmd\n", FILE_APPEND);
    $pythonOutput = shell_exec($pythonCmd);
    file_put_contents($logFile, $pythonOutput, FILE_APPEND);

    // 3. Merge Video + Subtitle
    if (file_exists("subtitles/output.ass")) {
        file_put_contents($logFile, "\n🎬 Merge video dengan subtitle...\n", FILE_APPEND);
        $mergeCmd = "$ffmpegPath -y -i $inputFile -vf \"ass=subtitles/output.ass\" output/merged.mp4 2>&1";
        file_put_contents($logFile, "Command: $mergeCmd\n", FILE_APPEND);
        $mergeOutput = shell_exec($mergeCmd);
        file_put_contents($logFile, $mergeOutput, FILE_APPEND);
    } else {
        file_put_contents($logFile, "\n❌ ERROR: File subtitle tidak ditemukan\n", FILE_APPEND);
    }

    $success = file_exists("output/merged.mp4");
    if ($success) {
        file_put_contents($logFile, "\n✅ PROSES SELESAI: Video berhasil diproses\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "\n❌ PROSES GAGAL: Video output tidak dibuat\n", FILE_APPEND);
    }

    return $success;
}

function stopProcess() {
    if (isWindows()) {
        // Untuk Windows
        $output = shell_exec('tasklist /FI "IMAGENAME eq ffmpeg.exe" 2>&1');
        if (strpos($output, 'ffmpeg.exe') !== false) {
            shell_exec('taskkill /F /IM ffmpeg.exe 2>&1');
            return true;
        }

        // Juga cek python processes
        $output = shell_exec('tasklist /FI "IMAGENAME eq python.exe" 2>&1');
        if (strpos($output, 'python.exe') !== false) {
            shell_exec('taskkill /F /IM python.exe 2>&1');
            return true;
        }
    } else {
        // Untuk Unix/Linux
        $pids = shell_exec('pgrep -f ffmpeg 2>&1');
        if (!empty($pids)) {
            $pids = explode("\n", trim($pids));
            foreach ($pids as $pid) {
                shell_exec("kill -9 $pid 2>&1");
            }
            return true;
        }
    }
    return false;
}

// Fungsi untuk cek dependencies dan environment
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
    echo "Working Dir: " . getcwd() . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n\n";

    echo "=== FFMPEG CHECK ===\n";
    echo "Command: $ffmpegPath -version\n";
    echo "Result: " . shell_exec("$ffmpegPath -version 2>&1");

    echo "=== PYTHON CHECK ===\n";
    echo "Command: $pythonPath --version\n";
    echo "Result: " . shell_exec("$pythonPath --version 2>&1");

    echo "=== DIRECTORY STRUCTURE ===\n";
    echo "upload/ exists: " . (is_dir('upload') ? 'Yes' : 'No') . "\n";
    echo "audio/ exists: " . (is_dir('audio') ? 'Yes' : 'No') . "\n";
    echo "output/ exists: " . (is_dir('output') ? 'Yes' : 'No') . "\n";
    echo "subtitles/ exists: " . (is_dir('subtitles') ? 'Yes' : 'No') . "\n";

    echo "=== FILE CHECK ===\n";
    echo "transcribe.py exists: " . (file_exists('transcribe.py') ? 'Yes' : 'No') . "\n";
    echo "upload/0512.mp4 exists: " . (file_exists('upload/0512.mp4') ? 'Yes' : 'No') . "\n";

    echo "=== PERMISSION CHECK ===\n";
    echo "Can write to current dir: " . (is_writable('.') ? 'Yes' : 'No') . "\n";
    echo "</pre>";
}

// Uncomment untuk testing
// checkDependencies();
?>