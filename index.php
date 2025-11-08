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

        $('#start-btn').click(function() {
            if (!isProcessing) {
                isProcessing = true;
                updateButtonStates(true);
                $('#output').html('🔄 Memulai proses...');

                $.ajax({
                    url: 'run.php',
                    method: 'POST',
                    data: { action: 'start' },
                    dataType: 'json', // Tambahkan ini
                    success: function(response) {
                        console.log('Start response:', response);
                        if (response.status === 'started') {
                            $('#output').html('✅ ' + response.message);
                            interval = setInterval(checkProgress, 2000); // Check setiap 2 detik
                        } else {
                            $('#output').html('❌ ' + response.message);
                            isProcessing = false;
                            updateButtonStates(false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Start error:', error);
                        $('#output').html('❌ AJAX Error: ' + error);
                        isProcessing = false;
                        updateButtonStates(false);
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
                    dataType: 'json',
                    success: function(response) {
                        console.log('Stop response:', response);
                        clearInterval(interval);
                        isProcessing = false;
                        updateButtonStates(false);
                        $('#output').append('\n⏹ ' + response.message);
                    },
                    error: function(xhr, status, error) {
                        console.error('Stop error:', error);
                        $('#output').html('❌ AJAX Error: ' + error);
                    }
                });
            }
        });

        // Fungsi checkProgress yang baru
        function checkProgress() {
            $.ajax({
                url: 'run.php',
                method: 'POST',
                data: { action: 'status' },
                dataType: 'json',
                success: function(response) {
                    console.log('Status response:', response);

                    // Update progress bar berdasarkan status
                    if (response.status === 'processing') {
                        $('#progress-bar').css('width', '70%').text('70%');
                        $('#output').html(response.log || '🔄 Sedang memproses...');
                    } else if (response.status === 'completed') {
                        $('#progress-bar').css('width', '100%').text('100%');
                        $('#output').html('✅ ' + response.message);
                        clearInterval(interval);
                        isProcessing = false;
                        updateButtonStates(false);

                        // Tampilkan video hasil jika ada
                        if (fileExists('output/merged.mp4')) {
                            $('#output').append('\n\n🎥 Video hasil:');
                            $('#output').append(`
                                <br><video controls width="100%">
                                    <source src="output/merged.mp4" type="video/mp4">
                                    Browser Anda tidak mendukung tag video.
                                </video>
                            `);
                        }
                    } else if (response.status === 'error') {
                        $('#output').html('❌ ' + response.message);
                        clearInterval(interval);
                        isProcessing = false;
                        updateButtonStates(false);
                    } else {
                        $('#progress-bar').css('width', '30%').text('30%');
                        $('#output').html(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Status error:', error);
                    $('#output').html('❌ Error checking status: ' + error);
                }
            });
        }

        // Fungsi untuk cek file exists (simulasi)
        function fileExists(url) {
            // Ini hanya simulasi, di real case butuh AJAX check
            return true; // Asumsikan file ada
        }

        // Inisialisasi
        updateButtonStates(false);
    });
</script>
</body>
</html>
