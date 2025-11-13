<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    // GUNAKAN stock_data sebagai tabel
    protected $table = 'stock_data';

    protected $fillable = [
        'material',
        'material_description',
        'batch',
        'stock_quantity',
        'base_unit',
        'sales_document',
        'item_number',
        'vendor_name',
        'plant',
        'storage_location',
        'last_updated',
        'hu_created',
        'hu_created_at',
        'hu_number'
    ];

    protected $casts = [
        'last_updated' => 'datetime',
        'hu_created_at' => 'datetime',
        'hu_created' => 'boolean',
        'stock_quantity' => 'decimal:2',
    ];
}
