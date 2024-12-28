<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;
    protected $table = 'turno';
    protected $fillable = ['hora'];
    public $timestamps = false; // Asegúrate de que esta línea esté presente
    public function ingreso() {
        return $this->hasMany(Ingreso::class);
    }
}
