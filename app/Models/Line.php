<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Line extends Model
{
    use HasFactory;
    public $table = "lines";
    protected $fillable = [ 'status', 'jobs', 'busy', 'operator_id'];
    protected $casts = [
        'status' => 'integer',
        'jobs' => 'integer',
        'operator_id' => 'integer'
    ];

    function operator() {
        return $this->belongsTo(Operator::class);
    }
}
