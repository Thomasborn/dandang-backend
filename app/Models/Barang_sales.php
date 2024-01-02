<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang_sales extends Model
{
    use HasFactory;protected $fillable = ['sales_id', 'barang_id', 'jumlah_barang'];

    public function sales()
    {
        return $this->belongsTo(sales::class);
    }

    public function barangKemasan()
    {
        return $this->belongsTo(barang_kemasan::class, 'barang_id');
    }
}
