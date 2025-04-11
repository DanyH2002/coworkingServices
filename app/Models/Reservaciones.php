<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservaciones extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'reservaciones';
    protected $primaryKey = 'id';

    public $timestamps = true;
    protected $fillable = [
        'id_user',
        'id_espacio',
        'fecha_reseva',
        'hora_inicio',
        'hora_fin',
        'estado',
        'total_precio'
    ];

    protected $casts = [
        'estado' => 'string',
        'total_precio' => 'decimal:2',
    ];

    protected $hidden = [
        'updated_at',
    ];
}
