<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    protected $fillable = ['id','name','email','phone','department_id','posation_id','test33_id'];

    public function Department()
    {
        return $this->belongsTo(Department::class);
    }

    public function Posation()
    {
        return $this->belongsTo(Posation::class);
    }

    public function Test33()
    {
        return $this->belongsTo(Test33::class);
    }

}
