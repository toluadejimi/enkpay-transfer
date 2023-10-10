<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webtransfer extends Model
{
    use HasFactory;


    protected $fillable = [
        'trans_id',
        'status',
        'amount',
    ];
}
