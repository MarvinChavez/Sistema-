<?php

namespace App\Http\Controllers;

use App\Imports\UserImport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index()
    {
         // Obtener todos los usuarios
         $users = User::all(); // Asegúrate de que tengas la relación 'role' en tu modelo User
         return view('dashboard.app.userlist', compact('users')); // Cambia 'users.index' por tu vista Blade
    }
}
