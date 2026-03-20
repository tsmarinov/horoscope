<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeekdayText extends Model
{
    public $timestamps = false;

    protected $fillable = ['iso_day', 'language', 'name', 'colors', 'gem', 'theme', 'description'];
}
