<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LineAnalysisExport implements WithMultipleSheets
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new KpiSheet($this->data),
            new StationSheet($this->data),
            new WorkElementSheet($this->data),
        ];
    }
}

class KpiSheet implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [
            ['Metric', 'Value', 'Target', 'Unit']
        ];

        foreach ($this->data['kpis'] as $kpi) {
            $rows[] = [
                $kpi['label'],
                $kpi['value'],
                $kpi['target'],
                $kpi['unit']
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center']
            ]
        ];
    }
}

class StationSheet implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [
            ['Station', 'Mean CT (s)', 'Robust CT (s)', 'CV (%)']
        ];

        foreach ($this->data['stations'] as $i => $station) {

            $rows[] = [
                $station,
                $this->data['meanCT'][$i],
                $this->data['robustCT'][$i],
                $this->data['cvData'][$i]
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center']
            ]
        ];
    }
}

class WorkElementSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $elements;

    public function __construct($data)
    {
        $this->elements = $data['elements'];
    }

    public function headings(): array
    {
        return [
            'Station',
            'Element',
            'Kategori',
            'Durasi (s)',
            'Std Dev',
            'CV (%)',
            'Frekuensi'
        ];
    }

    public function collection()
    {
        return $this->elements->map(function ($e) {
            return [
                $e->station,
                $e->elemen_kerja,
                $e->kategori_va,
                $e->durasi_detik,
                $e->std_dev,
                $e->cv_persen,
                $e->frekuensi
            ];
        });
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center']
            ]
        ];
    }
}