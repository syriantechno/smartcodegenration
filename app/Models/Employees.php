<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    protected $fillable = ['id','name','email','phone','department_id'];

    public function Department()
    {
        return $this->belongsTo(Department::class);
    }

}
