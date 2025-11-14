<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HuHistory extends Model
{
    use HasFactory;

    protected $table = 'hu_histories'; // Sesuaikan dengan nama tabel di database

    protected $fillable = [
        'hu_number',
        'material',
        'material_description',
        'batch',
        'quantity',
        'unit',
        'sales_document',
        'plant',
        'storage_location',
        'scenario_type',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
