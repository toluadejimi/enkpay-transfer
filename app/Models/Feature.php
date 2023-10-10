<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;



    protected $casts = [

    "id" => 'integer',
    "pos" => 'integer',
    "soft_pos" => 'integer',
    "bank_transfer"=> 'integer',
    "bills"=> 'integer',
    "data"=> 'integer',
    "airtime"=> 'integer',
    "insurance"=> 'integer',
    "education"=> 'integer',
    "power"=> 'integer',
    "exchange"=> 'integer',
    "ticket"=> 'integer',
    "v_card"=> 'integer',
    "pos_transfer"=> 'integer',



    ];
}
