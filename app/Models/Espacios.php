<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Espacios extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'espacios';
    protected $primaryKey = 'id';

    public $timestamps = true;
    protected $fillable = [
        'nombre',
        'capacidad',
        'precio-hora',
        'disponibilidad'
    ];
}
