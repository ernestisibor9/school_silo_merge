<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lesson_note extends Model
{
    use HasFactory;
    
    protected $fillable = ['sch_id', 'session', 'term', 'clsm', 'week', 'subject'];

    public function topics()
    {
        return $this->hasMany(topic::class);
    }
}
