<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class class_subj extends Model
{
    protected $table = 'class_subj'; 
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','schid','subj_id','name','comp', 'clsid'
    ];
}
