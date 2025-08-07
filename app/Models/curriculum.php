<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class curriculum extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'schid', 'clsm', 'week', 'topic', 'description', 'teaching_aids',
        'ssn', 'trm', 'sbj', 'group', 'url_link'
    ];

    protected $casts = [
        'description' => 'array',
        'teaching_aids' => 'array',
    ];
}
