<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_medical_data extends Model
{
    protected $table = 'student_medical_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'hospital', 'blood', 'geno', 'hiv','malaria', 'typha', 'tb', 'heart', 'liver', 'vdrl', 'hbp'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
