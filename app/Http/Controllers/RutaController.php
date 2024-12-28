<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;

class RutaController extends Controller
{
    public function index()
    {
         // Obtener todos los usuarios
         $rutas = Ruta::all(); // Asegúrate de que tengas la relación 'role' en tu modelo User
         return view('dashboard.app.rutalist', compact('rutas')); // Cambia 'users.index' por tu vista Blade
    }
}
