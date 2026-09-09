<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $fillable = [
        'operation',
        'file_name',
        'total_records',
        'successful_records',
        'duplicate_records',
        'invalid_records',
        'status',
        'details',
    ];
}