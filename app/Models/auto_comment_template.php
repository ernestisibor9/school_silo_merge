<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class auto_comment_template extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'schid', 'ssn', 'trm', 'clsm', 'clsa', 'role', 'grade', 'comment',
    ];
}
