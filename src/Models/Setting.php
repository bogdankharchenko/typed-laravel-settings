<?php

namespace BogdanKharchenko\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{

    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'class',
        'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];
}
