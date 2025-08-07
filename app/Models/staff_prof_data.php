<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff_prof_data extends Model
{
    protected $table = 'staff_prof_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'grad_date', 'univ', 'area', 'qual','trcn', 'hqual', 'place_first_appt', 'last_employment'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
