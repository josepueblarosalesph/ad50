<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';
    protected $guarded = [];
    protected $casts = [
        'features'  => 'array',
        'destacado' => 'bool',
    ];
}
