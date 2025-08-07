<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sch_cls extends Model
{
    protected $table = 'sch_cls'; 
    protected $fillable = [
        'schid','cls_id','name'
    ];
}
