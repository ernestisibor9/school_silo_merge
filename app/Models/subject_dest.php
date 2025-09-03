<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subject_dest extends Model
{
    use HasFactory;

        protected $fillable = [
        'uid','stid', 'sbj', 'schid', 'sesn','trm'
    ];
}
