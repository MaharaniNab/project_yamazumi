<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PythonApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.python_api.url'), '/');
    }

    /**
     * Upload video files ke Flask API menggunakan curl multipart stream.
     * Jauh lebih cepat dari file_get_contents karena tidak load ke memory.
     */
    public function uploadVideos(array $videoMap, array $metadata): array
    {
        $postFields = [];

        // Attach setiap file video sebagai CURLFile (stream, tidak load ke memory)
        foreach ($videoMap as $stationName => $storagePath) {
            $fullPath = Storage::disk('public')->path($storagePath);
            $ext      = pathinfo($storagePath, PATHINFO_EXTENSION);
            $filename = $stationName . '.' . $ext;

            $postFields['file_list'][] = new \CURLFile($fullPath, 'video/mp4', $filename);
        }

        // Kalau ada beberapa video, curl butuh array format khusus
        // Rebuild dengan key file_list[0], file_list[1], dst
        $curlFields = [];
        foreach (($postFields['file_list'] ?? []) as $i => $curlFile) {
            $curlFields["file_list[$i]"] = $curlFile;
        }

        // Attach metadata
        foreach ($metadata as $key => $value) {
            $curlFields[$key] = (string) $value;
        }

        // Kirim dengan curl langsung untuk support CURLFile streaming
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => "{$this->baseUrl}/api/upload",
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $curlFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 600,  // 10 menit untuk video besar
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("Curl error: {$curlError}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("Flask upload gagal [{$httpCode}]: {$response}");
        }

        $data = json_decode($response, true);
        if (!$data) {
            throw new \RuntimeException("Response Flask tidak valid: {$response}");
        }

        return $data;
    }

    /**
     * Cek status & ambil hasil analisis.
     */
    public function getResults(string $pythonJobId): array
    {
        $response = Http::timeout(30)
            ->get("{$this->baseUrl}/api/results/{$pythonJobId}");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Flask results gagal [{$response->status()}]: " . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Jalankan simulasi Kaizen + Robust Balancing.
     */
    public function runSimulation(string $pythonJobId): array
    {
        $response = Http::timeout(60)
            ->get("{$this->baseUrl}/api/simulate/{$pythonJobId}");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Flask simulate gagal [{$response->status()}]: " . $response->body()
            );
        }

        return $response->json();
    }

    public function ping(): bool
    {
        try {
            return Http::timeout(5)->get("{$this->baseUrl}/health")->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
