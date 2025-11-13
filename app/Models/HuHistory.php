<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HuHistory extends Model
{
    use HasFactory;

    protected $table = 'hu_history';

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
        // HAPUS 'created_at' dari fillable karena sudah otomatis dari timestamps
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // PASTIKAN TIMESTAMPS DIAKTIFKAN
    public $timestamps = true;

    // Jika Anda ingin menggunakan nama kolom yang berbeda, tambahkan:
    // const CREATED_AT = 'created_at';
    // const UPDATED_AT = 'updated_at';
}
