<?php

use App\Http\Controllers\AutoController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/d', function () {
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard2');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/', function () {
        return view('dashboard.index');
    })->name('index');
    Route::get('/panel', function () {
        return view('dashboard.index');
    })->name('dashboard');
    Route::get('/userlist', [UserController::class, 'index']);
    Route::get('import-excel', [IngresoController::class, 'import_excel'])->name('import-excel');
    Route::post('import-excel', [IngresoController::class, 'import_excel_post'])->name('import-excelp');
    Route::get('/autolist', [AutoController::class, 'index'])->name('listaautos');
    Route::get('/rutalist', [RutaController::class, 'index'])->name('listarutas');
    Route::get('/ingresolist', [IngresoController::class, 'index'])->name('listaingresos');
    // web.php
    Route::get('/grafico', [IngresoController::class, 'index2'])->name('grafico.index2');
    Route::post('/grafico/filtros', [IngresoController::class, 'filtrarIngresos'])->name('grafico.filtrar');
    Route::get('/{id}/edit', [IngresoController::class, 'edit'])->name('ingresos.edit');
    Route::put('/{id}', [IngresoController::class, 'update'])->name('ingresos.update');
    Route::delete('/{id}/eliminar', [IngresoController::class, 'delete'])->name('ingresos.delete');
    Route::get('/vcreate', [IngresoController::class, 'vcreate'])->name('ingresos.vcreate');
    Route::post('/create', [IngresoController::class, 'create'])->name('ingresos.create');
    Route::get('/grafauto', [IngresoController::class, 'indexAuto'])->name('graficoauto');
    Route::post('/filtroauto', [IngresoController::class, 'filtrarAuto'])->name('filtrarauto');
    Route::get('/grafruta', [IngresoController::class, 'indexRuta'])->name('graficoruta');
    Route::post('/filtroruta', [IngresoController::class, 'filtrarRuta'])->name('filtrarruta');
    Route::get('/turnos/nopie', [IngresoController::class, 'indexturno'])->name('indexturno');

    // Ruta para obtener turnos según la ruta seleccionada
    Route::get('/turnos/{rutaId}', [IngresoController::class, 'obtenerTurnosPorRuta']);

    // Ruta para obtener ingresos filtrados
    Route::post('/ingresos-filtrados', [IngresoController::class, 'obtenerIngresosFiltrados'])->name('obtenerIngresosFiltrados');
});
//pie
Route::get('/autoruta', [IngresoController::class, 'indexautoruta'])->name('indexautoruta');
Route::post('/ingresos-por-rutas-por-auto', [IngresoController::class, 'obtenerIngresosPorRutasPorAuto'])->name('ingresosPorRutasPorAuto');

Route::get('/grafautopie', [IngresoController::class, 'indexautopie'])->name('indexautopie');
Route::post('/filtroautopie', [IngresoController::class, 'ingresosPorAutos'])->name('ingresosPorAutos');
Route::get('/grafrutapie', [IngresoController::class, 'indexrutapie'])->name('indexrutapie');
Route::post('/filtrorutapie', [IngresoController::class, 'ingresosPorRutas'])->name('ingresosPorRutas');


require __DIR__ . '/auth.php';
