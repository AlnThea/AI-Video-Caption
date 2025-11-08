<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVC Video Processing</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        /* [CSS yang sama seperti sebelumnya] */
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
        #start-btn:disabled, #stop-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        #stop-btn:not(:disabled) {
            background-color: #ff4444;
            color: white;
        }
        .upload-area {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .upload-area.dragover {
            border-color: #4CAF50;
            background-color: #f9f9f9;
        }
        #file-info {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<h2>AVC Video Processing</h2>

<!-- Area Upload -->
<div class="upload-area" id="upload-area">
    <p>📁 Drag & drop video file di sini atau klik untuk memilih</p>
    <input type="file" id="video-file" accept="video/*" style="display: none;">
    <button onclick="document.getElementById('video-file').click()">Pilih File Video</button>
    <div id="file-info"></div>
</div>

<!-- Processing Controls -->
<button id="start-btn" disabled>🚀 Jalankan Proses</button>
<button id="stop-btn" disabled>⛔ STOP</button>

<div id="progress-container">
    <div id="progress-bar">0%</div>
</div>

<h3>🔍 Output:</h3>
<div id="output">Pilih file video terlebih dahulu...</div>

<script>
    $(document).ready(function() {
        let interval;
        let isProcessing = false;
        let currentFileName = '';

        // Handle file selection
        $('#video-file').change(function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.type.startsWith('video/')) {
                    currentFileName = file.name;
                    $('#file-info').html(`✅ File terpilih: <strong>${file.name}</strong> (${formatFileSize(file.size)})`);
                    $('#start-btn').prop('disabled', false);
                    $('#output').html(`File siap diproses: ${file.name}`);
                } else {
                    alert('Silakan pilih file video!');
                    $('#video-file').val('');
                }
            }
        });

        // Drag and drop functionality
        const uploadArea = $('#upload-area')[0];
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('video/')) {
                    currentFileName = file.name;
                    $('#file-info').html(`✅ File terpilih: <strong>${file.name}</strong> (${formatFileSize(file.size)})`);
                    $('#start-btn').prop('disabled', false);
                    $('#output').html(`File siap diproses: ${file.name}`);
                    // Simulate file input change
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    $('#video-file')[0].files = dataTransfer.files;
                } else {
                    alert('Silakan drop file video!');
                }
            }
        });

        // Start Process
        $('#start-btn').click(function() {
            if (!isProcessing && currentFileName) {
                const fileInput = $('#video-file')[0];
                if (fileInput.files.length === 0) return;

                isProcessing = true;
                updateButtonStates(true);
                $('#output').html('📤 Mengupload file...');

                // Upload file dulu
                uploadFile(fileInput.files[0]);
            }
        });

        // Upload file function
        // Upload file function
function uploadFile(file) {
    const formData = new FormData();
    formData.append('video', file);
    formData.append('action', 'upload');

    $.ajax({
        url: 'run.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json', // ← INI YANG PENTING! jQuery akan auto parse JSON
        success: function(data) { // ← 'data' sudah berupa Object, bukan string
            console.log('Upload response:', data);

            // LANGSUNG GUNAKAN data (tidak perlu JSON.parse)
            if (data.status === 'uploaded') {
                $('#output').html('✅ ' + data.message);
                // Start processing setelah upload berhasil
                startProcessing(data.filename);
            } else {
                $('#output').html('❌ ' + data.message);
                isProcessing = false;
                updateButtonStates(false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Upload error:', error);
            console.log('XHR response:', xhr.responseText);
            $('#output').html('❌ Upload error: ' + error + '<br>Response: ' + xhr.responseText);
            isProcessing = false;
            updateButtonStates(false);
        }
    });
}

        // Start processing setelah upload
        function startProcessing(filename) {
            $('#output').html('🔄 Memulai proses video...');

            $.ajax({
                url: 'run.php',
                method: 'POST',
                data: {
                    action: 'start',
                    filename: filename
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Start response:', response);
                    if (response.status === 'started') {
                        $('#output').html('✅ ' + response.message);
                        interval = setInterval(checkProgress, 2000);
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

        // [Fungsi checkProgress, updateButtonStates, dan lainnya tetap sama...]
        function updateButtonStates(processing) {
            if (processing) {
                $('#start-btn').text('⏳ Proses Berjalan').prop('disabled', true);
                $('#stop-btn').prop('disabled', false);
                $('#video-file').prop('disabled', true);
            } else {
                $('#start-btn').text('🚀 Jalankan Proses').prop('disabled', false);
                $('#stop-btn').prop('disabled', true);
                $('#video-file').prop('disabled', false);
            }
        }

        function checkProgress() {
            $.ajax({
                url: 'run.php',
                method: 'POST',
                data: { action: 'status' },
                dataType: 'json',
                success: function(response) {
                    console.log('Status response:', response);

                    if (response.status === 'processing') {
                        $('#progress-bar').css('width', '70%').text('70%');
                        $('#output').html(response.log || '🔄 Sedang memproses...');
                    } else if (response.status === 'completed') {
                        $('#progress-bar').css('width', '100%').text('100%');
                        $('#output').html('✅ ' + response.message);
                        clearInterval(interval);
                        isProcessing = false;
                        updateButtonStates(false);

                        // Tampilkan video hasil
                        showVideoResult();
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

        function showVideoResult() {
            $.ajax({
                url: 'run.php',
                method: 'POST',
                data: { action: 'get_video' },
                dataType: 'json',
                success: function(response) {
                    if (response.video_url) {
                        $('#output').append('\n\n🎥 Video hasil:');
                        $('#output').append(`
                            <br><video controls width="100%" style="max-width: 600px;">
                                <source src="${response.video_url}" type="video/mp4">
                                Browser Anda tidak mendukung tag video.
                            </video>
                        `);
                    }
                }
            });
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Stop button handler
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

        // Inisialisasi
        updateButtonStates(false);
    });
</script>
</body>
</html>