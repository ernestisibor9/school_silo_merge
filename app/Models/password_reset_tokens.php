<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class password_reset_tokens extends Model
{
    protected $table = 'password_reset_tokens'; 
    protected $primaryKey = 'email';
    public $incrementing = false;
    protected $fillable = [
        'email', 'token'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
