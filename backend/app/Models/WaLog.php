<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaLog extends Model
{
    use HasFactory;

    protected $table = 'wa_logs';

    protected $fillable = [
        'recipient_phone',
        'recipient_name',
        'user_id',
        'type',
        'message',
        'status',
        'response_payload',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
