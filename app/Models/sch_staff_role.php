<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sch_staff_role extends Model
{
    protected $table = 'sch_staff_role'; 
    protected $fillable = [
        'name','role','schid'
    ];
}
