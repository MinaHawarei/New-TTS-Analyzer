<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'tkt_id',
        'type',
        'model',
        'description',
    ];
}
