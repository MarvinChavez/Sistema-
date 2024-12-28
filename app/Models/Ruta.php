<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory;
    protected $table = 'ruta';
    protected $fillable = ['ciudad_inicial', 'ciudad_final'];
    public $timestamps = false; // Asegúrate de que esta línea esté presente
    public function ingreso() {
        return $this->hasMany(Ingreso::class);
    }
}
