<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TextBlockAssignment extends Model
{
    protected $fillable = ['profile_id', 'section', 'variant'];
}
