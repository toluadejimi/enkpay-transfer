<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedTransaction extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'attempt',
        'created_at',
        'updated_at',
        'ref_id',
        'id'
    
    ];
}
