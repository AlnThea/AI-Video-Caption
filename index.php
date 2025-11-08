<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVC Video Processing</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        #progress-container {
            width: 100%;
            background-color: #ddd;
            height: 30px;
            border-radius: 5px;
            margin-top: 20px;
        }
        #progress-bar {
            width: 0%;
            height: 100%;
            background-color: #4CAF50;
            text-align: center;
            line-height: 30px;
            color: white;
            font-weight: bold;
            border-radius: 5px;
        }
        #output {
            white-space: pre-wrap;
            font-family: monospace;
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 10px;
            height: 200px;
            overflow-y: auto;
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Efek visual untuk tombol disabled */
        #start-btn:disabled, #stop-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Warna tombol Stop saat aktif */
        #stop-btn:not(:disabled) {
            background-color: #ff4444;
            color: white;
        }
    </style>
</head>
<body>
<h2>AVC Video Processing</h2>
<button id="start-btn">🚀 Jalankan Proses</button>
<button id="stop-btn">⛔ STOP</button>
<div id="progress-container">
    <div id="progress-bar">0%</div>
</div>
<h3>🔍 Output:</h3>
<div id="output">Klik "Jalankan Proses" untuk memulai...</div>

<script>
    $(document).ready(function() {
        let interval;
        let isProcessing = false;

        $('#start-btn').click(function() {
            if (!isProcessing) {
                isProcessing = true;
                $(this).prop('disabled', true);
                $('#output').html('🔄 Memulai proses...');

                $.ajax({
                    url: 'run.php',
                    method: 'POST',
                    data: { action: 'start' },
                    success: function(response) {
                        if (response === 'started') {
                            interval = setInterval(checkProgress, 1000);
                        } else {
                            $('#output').html('❌ Error: ' + response);
                            isProcessing = false;
                            $('#start-btn').prop('disabled', false);
                        }
                    }
                });
            }
        });

        $('#stop-btn').click(function() {
            if (isProcessing) {
                $.ajax({
                    url: 'run.php',
                    method: 'POST',
                    data: { action: 'stop' },
                    success: function() {
                        isProcessing = false;
                        updateButtonStates(false); // Non-aktifkan Stop, aktifkan Start
                        $('#output').append('\n⏹ Proses dihentikan!');
                    }
                });
            }
        });

        // Fungsi untuk update tombol
        function updateButtonStates(processing) {
            if (processing) {
                $('#start-btn').text('⏳ Proses Berjalan').prop('disabled', true);
                $('#stop-btn').prop('disabled', false);
            } else {
                $('#start-btn').text('🚀 Jalankan Proses').prop('disabled', false);
                $('#stop-btn').prop('disabled', true);
            }
        }

    // Fungsi checkProgress (modifikasi bagian completed)
        function checkProgress() {
            $.ajax({
                url: 'progress.php',
                success: function(data) {
                    try {
                        let progressData = JSON.parse(data);
                        $('#progress-bar').css('width', progressData.progress + '%')
                            .text(progressData.progress + '%');
                        $('#output').html(progressData.log);

                        if (progressData.completed) {
                            clearInterval(interval);
                            isProcessing = false;
                            updateButtonStates(false);
                            $('#output').append('\n✅ Semua proses selesai!');

                            // Tampilkan video hasil
                            $('#video-preview').html(`
                        <video controls width="100%">
                            <source src="output/merged.mp4" type="video/mp4">
                        </video>
                    `);
                        }
                    } catch (e) {
                        console.error("Error parsing JSON:", e, "Response:", data);
                        $('#output').html('Error: ' + data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    $('#output').html('AJAX Error: ' + status);
                }
            });
        }

        updateButtonStates(false); // Pastikan Stop disabled saat pertama load




    });
</script>
</body>
</html>
