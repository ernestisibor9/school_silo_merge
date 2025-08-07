<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cummulative_comment extends Model
{
    use HasFactory;
    
    protected $table = 'cummulative_comments';

    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
       'uid', 'schid','sid','clsm', 'clsa','ssn','comm'
    ];
}
