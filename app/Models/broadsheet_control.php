<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class broadsheet_control extends Model
{
    use HasFactory;

    protected $fillable = ['sid', 'schid', 'ssn', 'trm', 'clsm', 'clsa', 'stat'];
}
