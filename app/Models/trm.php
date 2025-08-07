<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class trm extends Model
{
    protected $table = 'trm'; 
    protected $primaryKey = 'no';
    public $incrementing = false;
    protected $fillable = [
        'no','name'
    ];
}
