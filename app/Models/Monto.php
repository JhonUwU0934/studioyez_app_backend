<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monto extends Model
{
    use HasFactory;

    protected $fillable = [
        'creador_id',
        'monto',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creador_id');
    }
}
