<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'sid';
    public $incrementing = false;
    protected $fillable = [
        'sid', 'schid', 'fname','mname','lname', 'count','sch3','stat','cuid','status','exit_status',"role","role2",'s_basic','s_prof'
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
