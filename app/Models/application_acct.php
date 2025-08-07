<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class application_acct extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
     public function subAccounts()
    {
        return $this->hasMany(application_sub_acct::class, 'acct_id', 'id');
    }
}
