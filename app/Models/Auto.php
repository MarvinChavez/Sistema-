<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auto extends Model
{
    use HasFactory;
    protected $table = 'auto';
    protected $fillable = ['placa', 'proveedor'];
    public $timestamps=false;
    public function ingreso() {
        return $this->hasMany(Ingreso::class);
    }
}
