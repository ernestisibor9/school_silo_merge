<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sesn extends Model
{
    protected $table = 'sesn'; 
    protected $primaryKey = 'year';
    public $incrementing = false;
    protected $fillable = [
        'year','name'
    ];
}
