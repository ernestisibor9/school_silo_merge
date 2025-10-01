<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subaccount_split extends Model
{
    use HasFactory;

    protected $fillable = [ 'schid', 'clsid', 'split_code', ];
}
