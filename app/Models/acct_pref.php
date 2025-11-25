<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class acct_pref extends Model
{
    protected $table = 'acct_pref';
    protected $primaryKey = 'sid';
    public $incrementing = false;
    protected $fillable = [
        'sid','pref', 'ssn', 'trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
