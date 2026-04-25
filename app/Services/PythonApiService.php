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
     * Upload video files ke Flask API.
     * $videoMap = ['station_name' => 'path/di/storage/public/...']
     */
    public function uploadVideos(array $videoMap, array $metadata): array
    {
        $multipart = Http::timeout(120)->asMultipart();

        foreach ($videoMap as $stationName => $storagePath) {
            $fullPath = Storage::disk('public')->path($storagePath);
            $ext      = pathinfo($storagePath, PATHINFO_EXTENSION);

            $multipart = $multipart->attach(
                'file_list',
                file_get_contents($fullPath),
                $stationName . '.' . $ext
            );
        }

        foreach ($metadata as $key => $value) {
            $multipart = $multipart->attach($key, (string) $value);
        }

        $response = $multipart->post("{$this->baseUrl}/api/upload");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Flask upload gagal [{$response->status()}]: " . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Cek status & ambil hasil analisis.
     * Status: 'processing' | 'completed' | 'failed'
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