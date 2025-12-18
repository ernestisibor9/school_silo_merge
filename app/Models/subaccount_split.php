<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subaccount_split extends Model
{
    use HasFactory;

    protected $table = 'subaccount_splits';   // ensure this matches your DB table
    protected $fillable = ['schid', 'clsid', 'split_code', 'subaccounts'];
}
