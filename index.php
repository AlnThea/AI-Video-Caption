<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVC Video Processing</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        .upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
            cursor: pointer;
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }

        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .upload-area.dragover .upload-icon {
            color: #0d6efd;
        }

        /* PROGRESS BAR BOOTSTRAP YANG BENAR */
        .progress {
            border-radius: 10px;
            overflow: hidden;
            height: 25px;
        }
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }

        .output-container {
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .video-preview-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .status-badge {
            font-size: 0.8rem;
        }

        .btn-processing {
            position: relative;
            overflow: hidden;
        }

        .btn-processing::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: processing 1.5s infinite;
        }

        @keyframes processing {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .file-info-card {
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-camera-video-fill text-primary fs-1 me-3"></i>
                    <div>
                        <h1 class="h2 mb-1">AVC Video Processing</h1>
                        <p class="text-muted mb-0">Upload video untuk ekstrak audio, transkripsi, dan tambah subtitle otomatis</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Controls -->
            <div class="col-lg-6">
                <!-- Upload Area -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-cloud-upload me-2"></i>Upload Video
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="upload-area" id="upload-area">
                            <div class="upload-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <h5>Drag & Drop Video File</h5>
                            <p class="text-muted mb-3">atau klik untuk memilih file dari komputer</p>
                            <button class="btn btn-primary btn-lg" id="select-file-btn">
                                <i class="bi bi-folder2-open me-2"></i>Pilih File Video
                            </button>
                            <input type="file" id="video-file" accept="video/*" class="d-none">
                        </div>

                        <!-- File Info -->
                        <div id="file-info" class="mt-3"></div>
                    </div>
                </div>

                <!-- Processing Controls -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-gear me-2"></i>Processing Controls
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2 d-md-flex">
                            <button id="start-btn" class="btn btn-success btn-lg flex-fill" disabled>
                                <i class="bi bi-play-circle me-2"></i>🚀 Jalankan Proses
                            </button>
                            <button id="stop-btn" class="btn btn-outline-danger btn-lg" disabled>
                                <i class="bi bi-stop-circle me-2"></i>⛔ STOP
                            </button>
                        </div>

                        <!-- ✅ PROGRESS BAR BOOTSTRAP YANG BENAR -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Progress</span>
                                <span id="progress-percent" class="fw-bold">0%</span>
                            </div>
                            <div class="progress">
                                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar"
                                     aria-valuenow="0"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     style="width: 0%">
                                </div>
                            </div>
                            <div id="progress-text" class="text-center text-muted small mt-2">
                                Menunggu proses dimulai...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Process Output -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-terminal me-2"></i>Process Output
                        </h5>
                        <span id="status-badge" class="badge bg-secondary status-badge">Ready</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="output" class="output-container p-3">
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-info-circle me-2"></i>
                                Pilih file video terlebih dahulu untuk memulai proses...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Video Preview -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-play-btn me-2"></i>Video Preview
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Original Video Preview -->
                        <div id="original-video-container" class="mb-4" style="display: none;">
                            <h6 class="text-muted mb-2">
                                <i class="bi bi-film me-1"></i>Original Video
                            </h6>
                            <div id="original-video" class="ratio ratio-16x9 bg-dark rounded">
                                <!-- Original video will be inserted here -->
                            </div>
                        </div>

                        <!-- Processed Video Preview -->
                        <div id="processed-video-container">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-camera-video fs-1 d-block mb-3"></i>
                                <h6>Video Hasil Akan Muncul di Sini</h6>
                                <p class="small">Setelah proses selesai, video dengan subtitle akan ditampilkan di sini</p>
                            </div>
                            <div id="processed-video" class="ratio ratio-16x9">
                                <!-- Processed video will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            let interval;
            let isProcessing = false;
            let currentFileName = '';
            let currentProgress = 0;
            let currentVideoFile = null;

            // Handle file selection
            $('#video-file').change(function(e) {
                if (e.target.files && e.target.files[0]) {
                    handleFileSelection(e.target.files[0]);
                }
            });

            // Handle tombol pilih file - TANPA RECURSION
            $('#select-file-btn').click(function(e) {
                e.stopPropagation();
                $('#video-file').click();
            });

            // Upload area click (hanya untuk area kosong, bukan tombol)
            $('#upload-area').click(function(e) {
                if (!$(e.target).is('button') &&
                    !$(e.target).closest('button').length &&
                    !$(e.target).is('i') &&
                    !$(e.target).closest('i').length) {
                    $('#video-file').click();
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
                    handleFileSelection(files[0]);
                }
            });

            function handleFileSelection(file) {
                if (file) {
                    if (file.type.startsWith('video/')) {
                        currentFileName = file.name;
                        currentVideoFile = file;

                        // Show file info
                        $('#file-info').html(`
                            <div class="alert alert-success file-info-card">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">File Terpilih</h6>
                                        <p class="mb-0"><strong>${file.name}</strong> (${formatFileSize(file.size)})</p>
                                    </div>
                                </div>
                            </div>
                        `);

                        $('#start-btn').prop('disabled', false);
                        $('#output').html(`<div class="text-success"><i class="bi bi-check-circle me-2"></i>File siap diproses: <strong>${file.name}</strong></div>`);
                        updateStatus('ready', 'bg-success');

                        // Reset progress
                        updateProgress(0, 'Menunggu proses dimulai...');

                        // Show original video preview
                        showOriginalVideoPreview(file);

                    } else {
                        showAlert('Silakan pilih file video!', 'danger');
                        $('#video-file').val('');
                        currentVideoFile = null;
                    }
                }
            }

            // Start Process
            $('#start-btn').click(function() {
                if (!isProcessing && currentVideoFile) {
                    isProcessing = true;
                    currentProgress = 0;
                    updateButtonStates(true);
                    updateStatus('processing', 'bg-warning');
                    updateProgress(10, 'Mengupload file...');
                    $('#output').html('<div class="text-warning"><i class="bi bi-arrow-up me-2"></i>Mengupload file...</div>');

                    // Upload file
                    uploadFile(currentVideoFile);
                }
            });

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
                    dataType: 'json',
                    success: function(data) {
                        console.log('Upload response:', data);

                        if (data.status === 'uploaded') {
                            updateProgress(30, 'Mengekstrak audio dari video...');
                            $('#output').html(`<div class="text-success"><i class="bi bi-check-circle me-2"></i>${data.message}</div>`);
                            startProcessing(data.filename);
                        } else {
                            $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>${data.message}</div>`);
                            isProcessing = false;
                            updateButtonStates(false);
                            updateStatus('error', 'bg-danger');
                            updateProgress(0, 'Error - proses dihentikan');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Upload error:', error);
                        $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>Upload error: ${error}</div>`);
                        isProcessing = false;
                        updateButtonStates(false);
                        updateStatus('error', 'bg-danger');
                        updateProgress(0, 'Error - upload gagal');
                    }
                });
            }

            // Start processing setelah upload
            function startProcessing(filename) {
                $('#output').html('<div class="text-info"><i class="bi bi-gear me-2"></i>Memulai proses video...</div>');

                $.ajax({
                    url: 'run.php',
                    method: 'POST',
                    data: { action: 'start', filename: filename },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Start response:', response);
                        if (response.status === 'started') {
                            updateProgress(50, 'Melakukan transkripsi audio...');
                            $('#output').html(`<div class="text-success"><i class="bi bi-play-circle me-2"></i>${response.message}</div>`);
                            interval = setInterval(checkProgress, 2000);
                        } else {
                            $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>${response.message}</div>`);
                            isProcessing = false;
                            updateButtonStates(false);
                            updateStatus('error', 'bg-danger');
                            updateProgress(0, 'Error - proses gagal');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Start error:', error);
                        $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>AJAX Error: ${error}</div>`);
                        isProcessing = false;
                        updateButtonStates(false);
                        updateStatus('error', 'bg-danger');
                        updateProgress(0, 'Error - koneksi gagal');
                    }
                });
            }

            // Check progress
            function checkProgress() {
                $.ajax({
                    url: 'run.php',
                    method: 'POST',
                    data: { action: 'status' },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Status response:', response);

                        if (response.status === 'processing') {
                            // Tentukan progress berdasarkan kondisi
                            let progress = 70;
                            let progressText = 'Memproses video...';

                            if (response.log) {
                                if (response.log.includes('encoder')) {
                                    progress = 60;
                                    progressText = 'Mengekstrak audio...';
                                } else if (response.log.includes('Transkripsi')) {
                                    progress = 75;
                                    progressText = 'Transkripsi audio ke teks...';
                                } else if (response.log.includes('Audio berhasil')) {
                                    progress = 85;
                                    progressText = 'Audio berhasil diproses, menambahkan subtitle...';
                                }
                            }

                            updateProgress(progress, progressText);
                            updateStatus('processing', 'bg-warning');

                            if (response.log) {
                                $('#output').html(`<div class="text-info">${response.log.replace(/\n/g, '<br>')}</div>`);
                            } else {
                                $('#output').html(`<div class="text-info"><i class="bi bi-hourglass-split me-2"></i>${progressText}</div>`);
                            }

                            // Auto scroll output to bottom
                            const output = $('#output')[0];
                            output.scrollTop = output.scrollHeight;

                        } else if (response.status === 'completed') {
                            updateProgress(100, 'Proses selesai!');
                            $('#output').html(`<div class="text-success"><i class="bi bi-check-circle me-2"></i>${response.message}</div>`);
                            clearInterval(interval);
                            isProcessing = false;
                            updateButtonStates(false);
                            updateStatus('completed', 'bg-success');

                            // Show processed video
                            showVideoResult();

                        } else if (response.status === 'error') {
                            updateProgress(0, 'Error - proses dihentikan');
                            $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>${response.message}</div>`);
                            clearInterval(interval);
                            isProcessing = false;
                            updateButtonStates(false);
                            updateStatus('error', 'bg-danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Status error:', error);
                        $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>Error checking status: ${error}</div>`);
                    }
                });
            }

            // ✅ FUNGSI UPDATE PROGRESS YANG BENAR
            function updateProgress(percent, text) {
                currentProgress = percent;

                // Update width dan text
                $('#progress-bar').css('width', percent + '%');
                $('#progress-percent').text(percent + '%');
                $('#progress-text').text(text);

                // Update warna berdasarkan persentase
                updateProgressBarColor(percent);

                // Update aria-valuenow untuk accessibility
                $('#progress-bar').attr('aria-valuenow', percent);
            }

            // ✅ FUNGSI UPDATE WARNA PROGRESS BAR YANG BENAR
            function updateProgressBarColor(percent) {
                const progressBar = $('#progress-bar');

                // Hapus semua class warna
                progressBar.removeClass('bg-secondary bg-info bg-warning bg-primary bg-success bg-danger');

                // Tambahkan class warna berdasarkan persentase
                if (percent === 0) {
                    progressBar.addClass('bg-secondary');
                } else if (percent < 30) {
                    progressBar.addClass('bg-info');
                } else if (percent < 70) {
                    progressBar.addClass('bg-warning');
                } else if (percent < 100) {
                    progressBar.addClass('bg-primary');
                } else {
                    progressBar.addClass('bg-success');
                }
            }

            // Show video result
            function showVideoResult() {
                $.ajax({
                    url: 'run.php',
                    method: 'POST',
                    data: { action: 'get_video' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.video_url) {
                            $('#processed-video-container').html(`
                                <h6 class="text-success mb-3">
                                    <i class="bi bi-check-circle me-2"></i>Video Hasil dengan Subtitle
                                </h6>
                                <div class="ratio ratio-16x9">
                                    <video controls class="w-100 rounded shadow-sm">
                                        <source src="${response.video_url}?t=${new Date().getTime()}" type="video/mp4">
                                        Browser Anda tidak mendukung tag video.
                                    </video>
                                </div>
                                <div class="mt-3">
                                    <a href="${response.video_url}" download class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-download me-2"></i>Download Video
                                    </a>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error getting video result:', error);
                        $('#processed-video-container').html(`
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Video hasil tidak dapat dimuat. Silakan coba lagi.
                            </div>
                        `);
                    }
                });
            }

            // Show original video preview
            function showOriginalVideoPreview(file) {
                const videoUrl = URL.createObjectURL(file);
                $('#original-video-container').show();
                $('#original-video').html(`
                    <video controls class="w-100">
                        <source src="${videoUrl}" type="${file.type}">
                        Browser Anda tidak mendukung tag video.
                    </video>
                `);
            }

            // Update button states
            function updateButtonStates(processing) {
                if (processing) {
                    $('#start-btn')
                        .html('<i class="bi bi-hourglass-split me-2"></i>⏳ Proses Berjalan')
                        .prop('disabled', true)
                        .addClass('btn-processing');
                    $('#stop-btn').prop('disabled', false);
                    $('#video-file').prop('disabled', true);
                    $('#select-file-btn').prop('disabled', true);
                } else {
                    $('#start-btn')
                        .html('<i class="bi bi-play-circle me-2"></i>🚀 Jalankan Proses')
                        .prop('disabled', false)
                        .removeClass('btn-processing');
                    $('#stop-btn').prop('disabled', true);
                    $('#video-file').prop('disabled', false);
                    $('#select-file-btn').prop('disabled', false);
                }
            }

            // Update status badge
            function updateStatus(status, badgeClass) {
                const statusText = {
                    'ready': 'Ready',
                    'processing': 'Processing',
                    'completed': 'Completed',
                    'error': 'Error',
                    'stopped': 'Stopped'
                };

                $('#status-badge')
                    .removeClass('bg-secondary bg-success bg-warning bg-danger')
                    .addClass(badgeClass)
                    .text(statusText[status] || status);
            }

            // Show alert
            function showAlert(message, type) {
                $('#output').html(`<div class="alert alert-${type}">${message}</div>`);
            }

            // Format file size
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
                            updateStatus('stopped', 'bg-secondary');
                            updateProgress(0, 'Proses dihentikan');
                            $('#output').append(`<div class="text-warning mt-2"><i class="bi bi-stop-circle me-2"></i>${response.message}</div>`);
                        },
                        error: function(xhr, status, error) {
                            console.error('Stop error:', error);
                            $('#output').html(`<div class="text-danger"><i class="bi bi-x-circle me-2"></i>AJAX Error: ${error}</div>`);
                        }
                    });
                }
            });

            // Initialize
            function initializeApp() {
                isProcessing = false;
                currentFileName = '';
                currentProgress = 0;
                currentVideoFile = null;
                updateButtonStates(false);
                updateStatus('ready', 'bg-secondary');
                updateProgress(0, 'Menunggu proses dimulai...');
                $('#file-info').empty();
                $('#original-video-container').hide();
                $('#processed-video-container').html(`
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-camera-video fs-1 d-block mb-3"></i>
                        <h6>Video Hasil Akan Muncul di Sini</h6>
                        <p class="small">Setelah proses selesai, video dengan subtitle akan ditampilkan di sini</p>
                    </div>
                `);
            }

            initializeApp();
        });
    </script>
</body>
</html>