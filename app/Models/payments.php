<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payments extends Model
{
    protected $table = 'payments';
    protected $fillable = [
        'schid','stid','ssnid','trmid', 'pay_head', 'clsid', 'name', 'exp', 'amt','lid','subaccount_code', 'main_ref'
    ];
    /*protected $hidden = [
        'password',
    ];*/

    public function subAccounts()
    {
        return $this->hasMany(sub_account::class, 'acct_id', 'id');
    }
}
