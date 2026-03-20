<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedAccount extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event',
        'email',
        'registered_at',
        'deleted_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'deleted_at'    => 'datetime',
            'meta'          => 'array',
        ];
    }
}
