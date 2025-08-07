<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class partner_general_data extends Model
{
    protected $table = 'partner_general_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
         'user_id','state','lga', 'addr', 'sex'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
