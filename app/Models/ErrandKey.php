<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrandKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'errand_key',
        'expires_in',
    ];


}
