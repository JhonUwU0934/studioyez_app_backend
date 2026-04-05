<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaEliminacion extends Model
{
    protected $table = 'auditoria_eliminaciones';

    protected $fillable = [
        'tipo',
        'registro_id',
        'datos_eliminados',
        'usuario_id',
    ];

    protected $casts = [
        'datos_eliminados' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
