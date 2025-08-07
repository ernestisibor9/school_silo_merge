<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class partner_financial_data extends Model
{
    protected $table = 'partner_financial_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
         'user_id','bnk','anum', 'aname'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
