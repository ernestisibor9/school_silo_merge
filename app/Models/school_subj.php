<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_subj extends Model
{
    protected $table = 'school_subj'; 
    protected $fillable = [
        'schid','subj_id','name','comp'
    ];
}
