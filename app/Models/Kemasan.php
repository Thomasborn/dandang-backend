<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kemasan extends Model
{
    protected $table = 'kemasan';
    use HasFactory;
    protected $fillable = [
        'ukuran',
        'uom',
       ];
}
