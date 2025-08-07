<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff_subj extends Model
{
    protected $table = 'staff_subj';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid', 'sbj', 'schid'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
