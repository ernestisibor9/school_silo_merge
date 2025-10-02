<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payment_refs extends Model
{
    protected $table = 'payment_refs';
    protected $primaryKey = 'ref';
    public $incrementing = false;
    protected $fillable = [
        'ref', 'amt', 'time', 'metadata'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
