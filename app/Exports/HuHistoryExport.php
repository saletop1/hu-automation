<?php

namespace App\Exports;

use App\Models\HuHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HuHistoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $selectedData;

    public function __construct($selectedData)
    {
        $this->selectedData = $selectedData;
    }

    public function collection()
    {
        return HuHistory::whereIn('id', $this->selectedData)->get();
    }

    public function headings(): array
    {
        return [
            'HU Number',
            'Material',
            'Deskripsi Material',
            'Batch',
            'Quantity',
            'Unit',
            'Plant',
            'Storage Location',
            'Sales Document',
            'Scenario Type',
            'Created By',
            'Created At'
        ];
    }

    public function map($history): array
    {
        return [
            $history->hu_number,
            $history->material,
            $history->material_description,
            $history->batch,
            $history->quantity,
            $history->unit,
            $history->plant,
            $history->storage_location,
            $history->sales_document,
            $history->scenario_type,
            $history->created_by,
            $history->created_at ? $history->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:L' => ['alignment' => ['vertical' => 'center']],
        ];
    }
}
