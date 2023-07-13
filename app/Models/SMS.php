<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMS extends Model
{
    use HasFactory;
    protected $table = "sms";
    protected $fillable = ['user', 'message', 'phone', 'operator', 'operator_id', 'processing', 'line', 'message_id', 'sent_status'];
}
