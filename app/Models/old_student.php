<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class old_student extends Model
{
    protected $table = 'old_student';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','sid', 'schid', 'fname','mname','lname','suid','ssn','clsm', 'clsa', 'more'
    ];
    /*protected $hidden = [
        'password',
    ];*/
    
    public function student()
    {
        return $this->belongsTo(student::class, 'sid', 'sid');
    }

}
