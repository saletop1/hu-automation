<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stock_data';

    protected $fillable = [
        'material',
        'material_description',
        'batch',
        'plant',
        'storage_location',
        'stock_quantity',
        'base_unit',
        'sales_document',
        'item_number',
        'vendor_name',
        'last_updated',
        'hu_created',
        'hu_created_at',
        'hu_number'
    ];

    protected $casts = [
        'stock_quantity' => 'decimal:2',
        'hu_created' => 'boolean',
        'last_updated' => 'datetime',
        'hu_created_at' => 'datetime'
    ];

    public function hu_histories()
    {
        return $this->hasMany(HuHistory::class, 'stock_id');
    }
}
