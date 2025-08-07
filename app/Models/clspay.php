<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class clspay extends Model
{
    protected $table = 'clspay';
    protected $fillable = [
        'schid','clsid', 'amt','phid','sesid','trmid'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
