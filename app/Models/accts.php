<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class accts extends Model
{
    protected $table = 'accts';
    protected $fillable = [
        'schid','clsid','anum','bnk','aname'
    ];
    /*protected $hidden = [
        'password',
    ];*/
    
        public function subAccounts()
    {
        return $this->hasMany(sub_account::class, 'acct_id', 'id');
    }
}
