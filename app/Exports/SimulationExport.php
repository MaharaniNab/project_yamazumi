<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SimulationExport implements WithMultipleSheets
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
            new MetricsSheet($this->data),
            new YamazumiSheet($this->data),
            new MpBalancingSheet($this->data),
            new StationDetailSheet($this->data),
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
        $rows[] = ['Metric', 'Value', 'Unit'];

        foreach ($this->data['kpis'] as $kpi) {
            $rows[] = [
                $kpi['label'],
                $kpi['value'],
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
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ]
            ],

            'B:C' => [
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ]
        ];
    }
}

class MetricsSheet implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows[] = ['Metric', 'Before', 'After'];

        foreach ($this->data['metrics'] as $m) {
            $rows[] = [
                $m['label'],
                $m['before'],
                $m['after']
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [

            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ],

            'B:C' => [
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ]
        ];
    }
}

class YamazumiSheet implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows[] = ['Station', 'Before CT (s)', 'After CT (s)'];

        foreach ($this->data['chartData']['stations'] as $i => $st) {

            $rows[] = [
                $st,
                $this->data['chartData']['beforeData'][$i],
                $this->data['chartData']['afterData'][$i]
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [

            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ],

            'B:C' => [
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ]
        ];
    }
}

class MpBalancingSheet implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [

            ['Metric', 'Value'],

            ['MP Aktual', $this->data['mpAktual']],
            ['MP Assigned', $this->data['mpAssigned']],
            ['Overall Balance %', $this->data['mpBalance']],

        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [

            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ],

            'B' => [
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ]
        ];
    }
}

class StationDetailSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data['elementsData']);
    }

    public function headings(): array
    {
        return [
            'Station',
            'CT Before (s)',
            'CT After (s)',
            'MP Assigned',
            'CT Efektif',
            'Vs Takt',
            'Status'
        ];
    }

    public function collection()
    {
        return $this->data->map(function ($e) {

            return [
                $e['station_name'],
                $e['ct_before'],
                $e['ct_after'],
                $e['mp_assigned'],
                $e['ct_efektif'],
                $e['vs_takt'],
                $e['status']
            ];
        });
    }

    public function styles(Worksheet $sheet)
    {
        return [

            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ]
            ],

            'B:G' => [
                'alignment' => [
                    'horizontal' => 'center'
                ]
            ]
        ];
    }
}