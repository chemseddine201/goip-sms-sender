<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'integer'
    ];

    public function lines()
    {
        return $this->hasMany(Line::class);
    }
}
