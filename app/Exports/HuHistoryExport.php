<?php

namespace App\Exports;

use App\Models\HuHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HuHistoryExport implements FromCollection, WithHeadings
{
    protected $selectedData;

    public function __construct($selectedData)
    {
        $this->selectedData = $selectedData;
    }

    public function collection()
    {
        return HuHistory::whereIn('id', $this->selectedData)
            ->get()
            ->map(function ($item) {
                return [
                    'HU Number' => $item->hu_number,
                    'Material' => $item->material,
                    'Material Description' => $item->material_description,
                    'Batch' => $item->batch,
                    'Quantity' => (int) $item->quantity, // Ensure integer in export
                    'Unit' => $item->unit,
                    'Plant' => $item->plant,
                    'Storage Location' => $item->storage_location,
                    'Sales Document' => $item->sales_document,
                    'Scenario Type' => $item->scenario_type,
                    'Created By' => $item->created_by,
                    'Created At' => $item->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'HU Number',
            'Material',
            'Material Description',
            'Batch',
            'Quantity',
            'Unit',
            'Plant',
            'Storage Location',
            'Sales Document',
            'Scenario Type',
            'Created By',
            'Created At (WIB)'
        ];
    }
}
