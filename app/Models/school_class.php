<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_class extends Model
{
    protected $table = 'school_class'; 
    protected $fillable = [
        'schid','clsid','name'
    ];
}
