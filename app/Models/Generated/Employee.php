<?php

namespace App\Models\Generated;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';
    protected $fillable = ['name', 'email', 'phone', 'department_id', 'departments_id'];

    public function departments()
    {
        return $this->belongsTo(Department::class, 'departments_id');
    }

    public function departments()
    {
        return $this->belongsTo(Department::class, 'departments_id');
    }

}
