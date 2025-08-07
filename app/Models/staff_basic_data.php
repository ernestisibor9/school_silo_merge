<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff_basic_data extends Model
{
    protected $table = 'staff_basic_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'dob', 'sex', 'town', 'country','state', 'lga', 'addr', 'phn', 'kin_name', 'kin_phn', 'kin_relation'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
