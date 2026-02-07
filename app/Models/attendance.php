<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class attendance extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    // Attendance status constants
    const STATUS_DRAFT = 0;
    const STATUS_PRESENT = 1;
    const STATUS_ABSENT = 2;

    const PERIODS = ['morning', 'evening'];

    // Day constants (optional)
    const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
}
