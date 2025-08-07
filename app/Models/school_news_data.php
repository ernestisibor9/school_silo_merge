<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_news_data extends Model
{
    protected $table = 'school_news_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'title', 'body'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
