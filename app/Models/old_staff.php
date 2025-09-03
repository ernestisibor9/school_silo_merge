<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class old_staff extends Model
{
    protected $table = 'old_staff';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','sid', 'schid', 'fname','mname','lname','suid','ssn','trm','clsm','role','role2', 'more'
    ];
    /*protected $hidden = [
        'password',
    ];*/

    public function roleName()
    {
        return $this->hasOne(\App\Models\sch_staff_role::class, 'role', 'role')
            ->where('schid', $this->schid);
    }

    public function role2Name()
    {
        return $this->hasOne(\App\Models\sch_staff_role::class, 'role', 'role2')
            ->where('schid', $this->schid);
    }

}
