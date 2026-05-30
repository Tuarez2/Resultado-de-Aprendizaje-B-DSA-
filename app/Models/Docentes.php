<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docentes extends Model
{
    protected $table = 'docentes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'especialidad',
        'correo',
        'telefono',
    ];
}
