<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HuHistory extends Model
{
    use HasFactory;

    protected $table = 'hu_histories';

    protected $fillable = [
        'hu_number',
        'material',
        'material_description',
        'batch',
        'quantity',
        'unit',
        'plant',
        'storage_location',
        'sales_document',
        'scenario_type',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
