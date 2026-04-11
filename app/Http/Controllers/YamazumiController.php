<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class YamazumiController extends Controller
{
    // URL Backend Python (Sesuaikan jika nanti di-deploy ke server terpisah)
    private $pythonApiUrl = 'http://127.0.0.1:5000/api';

    /**
     * Menampilkan halaman form upload video
     */
    public function index()
    {
        return view('yamazumi.index'); // Pastikan Anda punya file resources/views/yamazumi/index.blade.php
    }

    /**
     * Menerima request video dari user, dan meneruskannya (forward) ke API Python
     */
    public function analyze(Request $request)
    {
        // 1. Validasi input dari user
        $request->validate([
            'file_list.*' => 'required|mimes:mp4,avi,mov|max:102400', // Max 100MB per file
            'output_harian' => 'required|numeric',
            'mp_aktual' => 'required|integer',
            'nama_line' => 'required|string',
            'nama_bagian' => 'required|string',
        ]);

        // 2. Siapkan HTTP Client Laravel dengan format Multipart (untuk kirim file)
        $client = Http::asMultipart();

        // 3. Masukkan semua file video ke dalam request
        if ($request->hasFile('file_list')) {
            foreach ($request->file('file_list') as $file) {
                $client->attach(
                    'file_list', file_get_contents($file->path()), $file->getClientOriginalName()
                );
            }
        }

        // 4. Tembak API Python
        try {
            $response = $client->post("{$this->pythonApiUrl}/upload", [
                'output_harian' => $request->output_harian,
                'mp_aktual' => $request->mp_aktual,
                'nama_line' => $request->nama_line,
                'nama_bagian' => $request->nama_bagian,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Kembalikan job_id ke frontend agar bisa mulai melakukan polling
                return response()->json([
                    'success' => true,
                    'job_id' => $data['job_id'],
                    'message' => 'Video sedang diproses...'
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Gagal memproses di engine Python.'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat terhubung ke server Python: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengecek status antrean di Python
     */
    public function checkStatus($job_id)
    {
        try {
            $response = Http::get("{$this->pythonApiUrl}/results/{$job_id}");
            
            // Akan mengembalikan JSON berisi status 'processing', 'completed', atau 'failed'
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Koneksi terputus'], 500);
        }
    }

    /**
     * Menampilkan halaman hasil (Grafik Yamazumi & Tabel Kaizen)
     */
    public function showResult($job_id)
    {
        // Ambil data final dari Python
        $response = Http::get("{$this->pythonApiUrl}/results/{$job_id}");

        if ($response->successful() && $response->json('status') === 'completed') {
            $hasilAlgoritma = $response->json();
            
            // Parsing chart_base64 untuk dikirim ke view
            $chartBase64 = $hasilAlgoritma['chart_base64'] ?? null;

            return view('yamazumi.result', compact('hasilAlgoritma', 'chartBase64', 'job_id'));
        }

        return redirect()->route('yamazumi.index')->with('error', 'Data tidak ditemukan atau proses gagal.');
    }
}