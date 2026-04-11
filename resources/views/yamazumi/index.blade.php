<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yamazumi - Upload Video Line Balancing</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #loadingArea { display: none; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Analisis Line Balancing (Yamazumi)</h4>
                </div>
                <div class="card-body">
                    
                    <form id="yamazumiForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Line</label>
                                <input type="text" class="form-control" name="nama_line" required placeholder="Contoh: Line 1 Sewing">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Bagian / Part</label>
                                <input type="text" class="form-control" name="nama_bagian" required placeholder="Contoh: Kerah Kemeja">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Target Output Harian (Pcs)</label>
                                <input type="number" class="form-control" name="output_harian" required value="1000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Manpower Aktual (Operator)</label>
                                <input type="number" class="form-control" name="mp_aktual" required value="5">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload Video Proses Kerja (Bisa pilih lebih dari satu)</label>
                            <input class="form-control" type="file" name="file_list[]" id="file_list" multiple accept="video/mp4,video/avi,video/quicktime" required>
                            <small class="text-muted">Gunakan nama file yang mendeskripsikan proses (contoh: jahit_kerah.mp4, gosok_depan.mp4)</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="btnSubmit">
                            Mulai Analisis Video
                        </button>
                    </form>

                    <div id="loadingArea" class="text-center mt-4 p-4 border rounded bg-white">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <h5 id="statusText">Mengunggah video...</h5>
                        <p class="text-muted small" id="statusDetail">Mohon jangan tutup halaman ini.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('yamazumiForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Mencegah browser reload halaman
        
        const form = this;
        const submitBtn = document.getElementById('btnSubmit');
        const loadingArea = document.getElementById('loadingArea');
        const statusText = document.getElementById('statusText');
        const statusDetail = document.getElementById('statusDetail');
        
        // Sembunyikan tombol, tampilkan area loading
        submitBtn.style.display = 'none';
        loadingArea.style.display = 'block';
        statusText.innerText = "Mengirim data ke server...";

        // Siapkan data dari form
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 1. Tembak endpoint Analyze di Laravel
        fetch('{{ route('yamazumi.analyze') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.job_id) {
                // Berhasil masuk antrean, mulai proses polling (cek status berkala)
                statusText.innerText = "Sistem AI sedang menganalisis video...";
                statusDetail.innerText = "Job ID: " + data.job_id;
                
                mulaiPollingStatus(data.job_id);
            } else {
                // Gagal upload
                alert("Terjadi kesalahan: " + (data.message || 'Unknown error'));
                resetForm(submitBtn, loadingArea);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Terjadi kesalahan koneksi ke server.");
            resetForm(submitBtn, loadingArea);
        });
    });

    // 2. Fungsi untuk mengecek status secara berkala (Polling)
    function mulaiPollingStatus(jobId) {
        // Cek status setiap 3000 milidetik (3 detik)
        const pollingInterval = setInterval(() => {
            fetch(`/yamazumi/status/${jobId}`)
                .then(response => response.json())
                .then(data => {
                    
                    if (data.status === 'completed') {
                        // Jika selesai, hentikan polling dan pindah halaman
                        clearInterval(pollingInterval);
                        document.getElementById('statusText').innerText = "Selesai! Membuka hasil analisis...";
                        window.location.href = `/yamazumi/result/${jobId}`;
                    } 
                    else if (data.status === 'failed') {
                        // Jika Python gagal memproses (misal video rusak)
                        clearInterval(pollingInterval);
                        alert("Analisis gagal: " + (data.error_message || 'Terjadi kesalahan di mesin AI'));
                        resetForm(document.getElementById('btnSubmit'), document.getElementById('loadingArea'));
                    }
                    // Jika masih 'processing', biarkan interval terus berjalan
                    
                })
                .catch(error => {
                    console.error('Polling error:', error);
                    // Kita tidak menghentikan polling jika hanya error jaringan sesaat
                });
        }, 3000); 
    }

    // Fungsi utilitas untuk mengembalikan tampilan form jika terjadi error
    function resetForm(btn, loading) {
        btn.style.display = 'block';
        loading.style.display = 'none';
        document.getElementById('yamazumiForm').reset();
    }
</script>

</body>
</html>