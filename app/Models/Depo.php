<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depo extends Model
{
    protected $table ='depo';
    use HasFactory;
    protected $fillable = [
        'nama',
        'alamat',
        'user_id',
       ];
}
