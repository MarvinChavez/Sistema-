<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingreso extends Model
{
    use HasFactory;
    protected $table = 'ingreso';
    protected $fillable = ['auto_id', 'ruta_id','turno_id', 'monto','fecha','serial','servicio'];
    public $timestamps = false;
    public function auto() {
        return $this->belongsTo(Auto::class);
    }
    public function ruta() {
        return $this->belongsTo(Ruta::class);
    }
    public function turno() {
        return $this->belongsTo(Turno::class);
    }
}
