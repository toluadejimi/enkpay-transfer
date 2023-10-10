<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VfdBank extends Model
{
    use HasFactory;


    protected $fillable = [
        'bank_name',
        'code',
    ];
}
