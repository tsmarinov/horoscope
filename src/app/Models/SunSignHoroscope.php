<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunSignHoroscope extends Model
{
    protected $fillable = ['sign', 'date', 'body'];

    protected $casts = ['date' => 'date'];
}
