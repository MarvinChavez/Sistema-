<?php

namespace App\Http\Controllers;

use App\Models\Auto;
use Illuminate\Http\Request;

class AutoController extends Controller
{
    public function index()
    {
         // Obtener todos los usuarios
         $autos = Auto::all(); // Asegúrate de que tengas la relación 'role' en tu modelo User
         return view('dashboard.app.autolist', compact('autos')); // Cambia 'users.index' por tu vista Blade
    }
}
