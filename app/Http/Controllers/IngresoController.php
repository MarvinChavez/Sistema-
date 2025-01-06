<?php

namespace App\Http\Controllers;

use App\Imports\IngresoImport;
use App\Models\Auto;
use App\Models\Ingreso;
use App\Models\Ruta;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class IngresoController extends Controller
{
    public function import_excel()
    {
        return view('import_excel');
    }
    public function filtrarIngresos(Request $request)
{
    // Inicializar la consulta base
    $query = DB::table('ingreso');

    // Obtener los valores de fecha de inicio y fecha fin
    $fechaInicio = $request->filled('fecha_inicio') ? $request->input('fecha_inicio') : null;
    $fechaFin = $request->filled('fecha_fin') ? $request->input('fecha_fin') : date('Y-m-d');

    // Filtrar por fecha si se proporciona `fecha_inicio`
    if ($fechaInicio) {
        $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    } else {
        // Si no se especifica `fecha_inicio`, se filtra hasta `fecha_fin`
        $query->where('fecha', '<=', $fechaFin);
    }

    // Filtrar por servicio si se proporciona `servicio`
    if ($request->filled('servicio')) {
        $servicio = trim($request->input('servicio'));  // Remover espacios en blanco
        $query->where('servicio', '=', $servicio); // Filtrar donde el servicio sea igual al valor ingresado
    }

    // Seleccionar y agrupar ingresos por fecha
    $ingresos = $query->select(DB::raw('DATE(fecha) as fecha'), DB::raw('SUM(monto) as total'))
        ->groupBy(DB::raw('DATE(fecha)'))
        ->orderBy('fecha', 'asc')
        ->get();

    // Calcular el monto total sumando los totales diarios
    $montoTotal = $ingresos->sum('total');

    return response()->json([
        'ingresos' => $ingresos,
        'montoTotal' => $montoTotal
    ]);
}

    

    
    
    public function import_excel_post(Request $request)
    {
        // Validamos que haya un archivo presente.
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            // Intentamos importar el archivo.
            Excel::import(new IngresoImport, $request->file('excel_file'));

            // Mensaje de éxito.
            return redirect()->back()->with('success', 'Importación exitosa!');
        } catch (\Exception $e) {
            // Si hay un error, lo mostramos al usuario.
            return redirect()->back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
        return redirect()->route('index');
    }
    public function index2()
    {
        // Obtener todos los usuarios
        $rutas = DB::table('ruta')->get();
        $autos = DB::table('auto')->get();
        $turnos = Turno::all(); // Para cargar rutas en un select
        return view('dashboard.panel', compact('rutas', 'autos','turnos'));
    }
    public function index()
    {
        $ingresos = Ingreso::all();
        return view('dashboard.app.ingresolist', compact('ingresos')); 
    }
    public function edit($id)
    {
        $ingreso = Ingreso::findOrFail($id);
        $autos = Auto::all(); // Para cargar autos en un select
        $rutas = Ruta::all(); // Para cargar rutas en un select
        $turnos = Turno::all(); // Para cargar rutas en un select
        return view('dashboard.app.ingresoedit', compact('ingreso', 'autos', 'rutas','turnos'));
    }
    public function vcreate()
    {
        $autos = Auto::all(); // Para cargar autos en un select
        $rutas = Ruta::all(); // Para cargar rutas en un select
        $turnos = Turno::all(); // Para cargar rutas en un select
        return view('dashboard.app.ingresocreate', compact('autos', 'rutas','turnos'));
    }
    public function create(Request $request)
    {
        $request->validate([
            'auto_id' => 'required|exists:auto,id',
            'turno_id' => 'required|exists:turno,id',
            'ruta_id' => 'required|exists:ruta,id',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
            'servicio' => 'required|string',
            'serial' => 'required|string|unique:ingreso,serial'
        ]);

        // Actualizar los datos
        \App\Models\Ingreso::create([ // Asegúrate de que el modelo Ingreso está correctamente importado
            'auto_id' => $request->input('auto_id'),
            'ruta_id' => $request->input('ruta_id'),
            'turno_id' => $request->input('turno_id'),
            'servicio' => $request->input('servicio'),
            'monto' => $request->input('monto'),
            'fecha' => $request->input('fecha'),
            'serial' => $request->input('serial'),
        ]);
    

        return redirect()->route('listaingresos')
                     ->with('success', 'Ingreso actualizado exitosamente');
    }
    public function update(Request $request, $id)
    {
        $ingreso = Ingreso::findOrFail($id);

        // Validar los datos
        $request->validate([
            'auto_id' => 'required|exists:auto,id',
            'ruta_id' => 'required|exists:ruta,id',
            'turno_id' => 'required|exists:turno,id',
            'servicio' => 'required|string',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
            'serial' => 'required|string|unique:ingreso,serial,' . $id
        ]);

        // Actualizar los datos
        $ingreso->update([
            'auto_id' => $request->input('auto_id'),
            'ruta_id' => $request->input('ruta_id'),
            'turno_id' => $request->input('turno_id'),
            'monto' => $request->input('monto'),
            'fecha' => $request->input('fecha'),
            'serial' => $request->input('serial'),
            'servicio' => $request->input('servicio'),
        ]);

        return redirect()->route('listaingresos')
                     ->with('success', 'Ingreso actualizado exitosamente');
    }
    public function delete($id)
    {
        // Encontrar el ingreso por ID
        $ingreso = Ingreso::findOrFail($id);
    
        // Eliminar el registro
        $ingreso->delete();
    
        // Redirigir a la lista de ingresos con un mensaje de éxito
        return redirect()->route('listaingresos')->with('success', 'Ingreso eliminado exitosamente.');
    }
    public function indexauto()
    {
        // Obtener todos los autos para el selector
        $autos = Auto::all();

        return view('dashboard.grafico-auto', compact('autos'));
    }
    public function filtrarAuto(Request $request)
    {
        try {
            // Validar los datos recibidos
            $request->validate([
                'autos' => 'required|array',            // Los autos seleccionados deben ser un array
                'servicio' => 'nullable|string',        // El servicio debe ser un string, puede ser nulo
                'fecha_inicio' => 'nullable|date',      // Fecha de inicio válida, puede ser nula
                'fecha_fin' => 'nullable|date',         // Fecha de fin válida, puede ser nula
            ]);
    
            // Obtener los parámetros de la solicitud
            $autoIds = $request->input('autos');
            $servicio = $request->input('servicio');
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
    
            // Consultar los ingresos de autos filtrados por los parámetros recibidos
            $query = DB::table('ingreso')
                ->select(
                    'ingreso.auto_id',
                    'auto.placa',
                    DB::raw('SUM(ingreso.monto) as total_monto'),
                    DB::raw('DATE(ingreso.fecha) as fecha')
                )
                ->join('auto', 'ingreso.auto_id', '=', 'auto.id')
                ->whereIn('ingreso.auto_id', $autoIds)  // Filtrar por los autos seleccionados
                ->when($servicio, function ($query) use ($servicio) {
                    return $query->where('ingreso.servicio', $servicio);  // Filtrar por servicio si se proporcionó
                })
                ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
                    return $query->whereBetween('ingreso.fecha', [$fechaInicio, $fechaFin]);  // Filtrar por rango de fechas
                })
                ->when($fechaInicio, function ($query) use ($fechaInicio) {
                    return $query->where('ingreso.fecha', '>=', $fechaInicio);  // Filtrar por fecha de inicio si se proporcionó
                })
                ->when($fechaFin, function ($query) use ($fechaFin) {
                    return $query->where('ingreso.fecha', '<=', $fechaFin);  // Filtrar por fecha de fin si se proporcionó
                })
                ->groupBy('ingreso.auto_id', 'auto.placa', 'fecha') // Agrupar por auto y fecha
                ->orderBy('fecha')  // Ordenar por fecha
                ->get();
    
            // Si no hay resultados, devolver una respuesta vacía
            if ($query->isEmpty()) {
                return response()->json([
                    'autos' => []
                ]);
            }
    
            // Preparar la lista de autos con sus montos por fecha
            $autos = [];
            foreach ($autoIds as $autoId) {
                $datosAuto = $query->filter(function ($resultado) use ($autoId) {
                    return $resultado->auto_id == $autoId;
                });
    
                $fechas = [];
                $montos = [];
                $nombreAuto = '';
                $totalMontos = 0;
                $ultimoRegistro = null;
    
                foreach ($datosAuto as $resultado) {
                    $fechas[] = $resultado->fecha;
                    $montos[] = $resultado->total_monto;
                    $totalMontos += $resultado->total_monto;
                    $nombreAuto = $resultado->placa;
                    $ultimoRegistro = $resultado;
                }
    
                // Calcular el monto promedio
                $montoPromedio = count($montos) > 0 ? $totalMontos / count($montos) : 0;
    
                // Calcular el número de turnos únicos para este auto
                $numeroTurnos = DB::table('ingreso')
                    ->where('auto_id', $autoId)
                    ->when($servicio, function ($query) use ($servicio) {
                        return $query->where('servicio', $servicio);  // Filtrar por servicio si se proporcionó
                    })
                    ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
                        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);  // Filtrar por rango de fechas
                    })
                    ->when($fechaInicio, function ($query) use ($fechaInicio) {
                        return $query->where('fecha', '>=', $fechaInicio);  // Filtrar por fecha de inicio
                    })
                    ->when($fechaFin, function ($query) use ($fechaFin) {
                        return $query->where('fecha', '<=', $fechaFin);  // Filtrar por fecha de fin
                    })
                    ->count('turno_id');
    
                // Agregar los datos al array de autos
                $autos[] = [
                    'nombre' => $nombreAuto ?: 'Auto ' . $autoId,
                    'fechas' => $fechas,
                    'montos' => $montos,
                    'monto_promedio' => round($montoPromedio, 2),
                    'numero_turnos' => $numeroTurnos, // Número de turnos recorridos
                    'ultimo_registro' => $ultimoRegistro
                        ? [
                            'fecha' => $ultimoRegistro->fecha,
                            'monto' => $ultimoRegistro->total_monto
                        ]
                        : null
                ];
            }
    
            // Devolver la respuesta en formato JSON
            return response()->json([
                'autos' => $autos
            ]);
    
        } catch (\Exception $e) {
            // Si ocurre algún error, devolver una respuesta JSON con el error
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    

    
    public function indexruta()
    {
        // Obtener todos los autos para el selector
        $rutas = Ruta::all();

        return view('dashboard.grafico-ruta', compact('rutas'));
    }

    public function filtrarRuta(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'rutas' => 'required|array',       // Las rutas seleccionadas deben ser un array
            'servicio' => 'nullable|string',  // El servicio debe ser un string "spi" o "spp"
            'fecha_inicio' => 'nullable|date', // Fecha de inicio válida, pero puede ser nula
            'fecha_fin' => 'nullable|date',    // Fecha de fin válida, pero puede ser nula
        ]);
    
        // Obtener los parámetros de la solicitud
        $rutaIds = $request->input('rutas');
        $servicio = $request->input('servicio');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
    
        // Consultar ingresos por ruta, servicio y fechas
        $query = DB::table('ingreso')
            ->select(
                'ingreso.ruta_id',
                'ingreso.fecha',
                DB::raw('SUM(ingreso.monto) as total_monto'),
                'ruta.ciudad_inicial',
                'ruta.ciudad_final'
            )
            ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
            ->when($rutaIds, function ($query) use ($rutaIds) {
                return $query->whereIn('ingreso.ruta_id', $rutaIds);
            })
            ->when($servicio, function ($query) use ($servicio) {
                return $query->where('ingreso.servicio', $servicio);
            });
    
        // Agregar condición para las fechas solo si están presentes
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ingreso.fecha', [$fechaInicio, $fechaFin]);
        } elseif ($fechaInicio) {
            $query->where('ingreso.fecha', '>=', $fechaInicio);
        } elseif ($fechaFin) {
            $query->where('ingreso.fecha', '<=', $fechaFin);
        }
    
        $resultados = $query
            ->groupBy('ingreso.ruta_id', 'ingreso.fecha', 'ruta.ciudad_inicial', 'ruta.ciudad_final')
            ->orderBy('ingreso.fecha')
            ->get();
    
        // Verificar si hay resultados; si no, devolver un gráfico vacío
        if ($resultados->isEmpty()) {
            return response()->json([
                'rutas' => []
            ]);
        }
    
        // Definir las abreviaciones
        $abreviaciones = [
            'TRUJILLO' => 'TRUJ',
            'CAJAMARCA' => 'CAXA',
            'JAEN' => 'JAEN',
            'CHICLAYO' => 'CHIC',
            'PIURA' => 'PIUR',
            'LA VICTORIA' => 'LIMA',
            'MORALES' => 'TARA',
        ];
    
        // Organizar los datos por ruta
        $rutas = [];
        foreach ($rutaIds as $rutaId) {
            $datosRuta = $resultados->filter(function ($resultado) use ($rutaId) {
                return $resultado->ruta_id == $rutaId;
            });
    
            // Construir fechas y montos por cada ruta
            $fechas = [];
            $montos = [];
            $nombreRuta = '';
            $totalMontos = 0;
            $ultimoRegistro = null;
    
            foreach ($datosRuta as $resultado) {
                $fechas[] = $resultado->fecha;
                $montos[] = $resultado->total_monto;
                $totalMontos += $resultado->total_monto;
    
                // Construir el nombre de la ruta si aún no se ha definido
                if (!$nombreRuta) {
                    $ciudad_inicial = strtoupper(trim($resultado->ciudad_inicial));
                    $ciudad_final = strtoupper(trim($resultado->ciudad_final));
                    $ciudadInicial = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
                    $ciudadFinal = $abreviaciones[$ciudad_final] ?? $ciudad_final;
    
                    $nombreRuta = $ciudadInicial . ' - ' . $ciudadFinal;
                }
    
                // Actualizar el último registro
                $ultimoRegistro = $resultado;
            }
    
            // Calcular el monto promedio
            $montoPromedio = count($montos) > 0 ? $totalMontos / count($montos) : 0;
    
            // Agregar datos al array final
            $rutas[] = [
                'nombre' => $nombreRuta ?: 'Ruta ' . $rutaId,
                'fechas' => $fechas,
                'montos' => $montos,
                'monto_promedio' => round($montoPromedio, 2),
                'ultimo_registro' => $ultimoRegistro
                    ? [
                        'fecha' => $ultimoRegistro->fecha,
                        'monto' => $ultimoRegistro->total_monto
                    ]
                    : null
            ];
        }
    
        // Devolver la respuesta en formato JSON
        return response()->json([
            'rutas' => $rutas
        ]);
    }

    public function indexautopie()
    {
        // Obtener todos los autos para el selector
        $autos = Auto::all();

        return view('dashboard.graficoautopie', compact('autos'));
    }
    public function ingresosPorAutos(Request $request)
{
    $autosIds = $request->input('autos');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    // Filtrar por auto y rango de fechas
    $resultados = Ingreso::whereIn('auto_id', $autosIds)
        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
        ->groupBy('auto_id')
        ->selectRaw('auto_id, SUM(monto) as total_monto')
        ->get();

    // Calcular el total general de los ingresos de los autos seleccionados
    $totalGeneral = $resultados->sum('total_monto');

    // Mapear los resultados a un formato adecuado y calcular el porcentaje
    $data = $resultados->map(function($resultado) use ($totalGeneral) {
        $porcentaje = $totalGeneral > 0 ? ($resultado->total_monto / $totalGeneral) * 100 : 0; // Evitar división por cero
        return [
            'placa' => $resultado->auto->placa,
            'total_monto' => $resultado->total_monto,
            'porcentaje' => number_format($porcentaje, 2) // Formato a dos decimales
        ];
    });

    return response()->json($data);
}
public function indexrutapie()
{
    // Obtener todos los autos para el selector
    $rutas = Ruta::all();

    return view('dashboard.graficorutapie', compact('rutas'));
}
public function ingresosPorRutas(Request $request)
{
    $rutasIds = $request->input('rutas');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    // Filtrar por ruta y rango de fechas
    $resultados = Ingreso::whereIn('ruta_id', $rutasIds)
        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
        ->groupBy('ruta_id')
        ->selectRaw('ruta_id, SUM(monto) as total_monto')
        ->get();

    // Calcular el total general de los ingresos de las rutas seleccionadas
    $totalGeneral = $resultados->sum('total_monto');

    // Mapear los resultados a un formato adecuado y calcular el porcentaje
    $data = $resultados->map(function($resultado) use ($totalGeneral) {
        $porcentaje = $totalGeneral > 0 ? ($resultado->total_monto / $totalGeneral) * 100 : 0; // Evitar división por cero
        return [
            'rutainicial' => strtoupper(trim($resultado->ruta->ciudad_inicial)),
            'rutafinal' => strtoupper(trim($resultado->ruta->ciudad_final)),
            'total_monto' => $resultado->total_monto,
            'porcentaje' => number_format($porcentaje, 2) // Formato a dos decimales
        ];
    });

    return response()->json($data);
}
    public function indexturno()
    {
    // Obtener todos los autos para el selector
    $rutas = Ruta::all();

    return view('dashboard.graficoturno', compact('rutas'));
    }
    public function obtenerTurnosPorRuta(Request $request, $rutaId)
{
    // Obtener el tipo de servicio desde los parámetros de la solicitud
    $tipoServicio = $request->input('servicio');

    // Verificar si el tipo de servicio está presente
    if (!$tipoServicio) {
        return response()->json(['message' => 'El tipo de servicio es requerido.'], 400);
    }

    // Filtrar los turnos según la ruta y el tipo de servicio
    $turnos = Ingreso::where('ruta_id', $rutaId)
        ->where('servicio', $tipoServicio) // Asegúrate de que la columna "tipo_servicio" exista en tu tabla "ingresos"
        ->distinct()
        ->pluck('turno_id');

    // Si no hay turnos, devolver un mensaje de error
    if ($turnos->isEmpty()) {
        return response()->json(['message' => 'No se encontraron turnos para el tipo de servicio especificado.'], 404);
    }

    // Obtener los detalles de los turnos y ordenarlos por hora de inicio
    $turnosDetalles = Turno::whereIn('id', $turnos)
        ->orderBy('hora') // Asegúrate de usar el nombre correcto de la columna
        ->get();

    return response()->json($turnosDetalles);
}


public function obtenerIngresosFiltrados(Request $request)
{
    try {
        // Validar los datos recibidos
        $request->validate([
            'turnos' => 'required|array',       // Los turnos seleccionados deben ser un array
            'ruta' => 'required|integer',      // La ruta debe ser un entero
            'servicio' => 'required|string',   // El servicio debe ser un string
            'fecha_inicio' => 'nullable|date', // Fecha de inicio válida, puede ser nula
            'fecha_fin' => 'nullable|date',    // Fecha de fin válida, puede ser nula
        ]);

        // Obtener los parámetros de la solicitud
        $turnoIds = $request->input('turnos');
        $ruta = $request->input('ruta');
        $servicio = $request->input('servicio');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Consultar los turnos filtrados por los parámetros recibidos
        $query = DB::table('ingreso')
            ->select(
                'ingreso.turno_id',
                'turno.hora',
                DB::raw('SUM(ingreso.monto) as total_monto'),
                DB::raw('DATE(ingreso.fecha) as fecha')
            )
            ->join('turno', 'ingreso.turno_id', '=', 'turno.id')
            ->whereIn('ingreso.turno_id', $turnoIds)
            ->where('ingreso.ruta_id', $ruta)
            ->where('ingreso.servicio', $servicio)
            ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
                return $query->whereBetween('ingreso.fecha', [$fechaInicio, $fechaFin]);
            })
            ->when($fechaInicio, function ($query) use ($fechaInicio) {
                return $query->where('ingreso.fecha', '>=', $fechaInicio);
            })
            ->when($fechaFin, function ($query) use ($fechaFin) {
                return $query->where('ingreso.fecha', '<=', $fechaFin);
            })
            ->groupBy('ingreso.turno_id', 'turno.hora', 'fecha')
            ->orderBy('fecha')
            ->get();

        // Si no hay resultados, devolver una respuesta vacía
        if ($query->isEmpty()) {
            return response()->json([
                'turnos' => []
            ]);
        }

        // Preparar la lista de turnos con sus datos
        $turnos = [];
        foreach ($turnoIds as $turnoId) {
            $datosTurno = $query->filter(function ($resultado) use ($turnoId) {
                return $resultado->turno_id == $turnoId;
            });

            $fechas = [];
            $montos = [];
            $nombreTurno = '';
            $totalMontos = 0;
            $ultimoRegistro = null;

            foreach ($datosTurno as $resultado) {
                $fechas[] = $resultado->fecha;
                $montos[] = $resultado->total_monto;
                $totalMontos += $resultado->total_monto;
                $nombreTurno = $resultado->hora;
                $ultimoRegistro = $resultado;
            }

            // Calcular el monto promedio
            $montoPromedio = count($montos) > 0 ? $totalMontos / count($montos) : 0;

            // Agregar los datos al array de turnos
            $turnos[] = [
                'nombre' => $nombreTurno ?: 'Turno ' . $turnoId,
                'fechas' => $fechas,
                'montos' => $montos,
                'monto_promedio' => round($montoPromedio, 2),
                'ultimo_registro' => $ultimoRegistro
                    ? [
                        'fecha' => $ultimoRegistro->fecha,
                        'monto' => $ultimoRegistro->total_monto
                    ]
                    : null
            ];
        }

        // Devolver la respuesta en formato JSON
        return response()->json([
            'turnos' => $turnos
        ]);
    } catch (\Exception $e) {
        // Si ocurre algún error, devolver una respuesta JSON con el error
        return response()->json([
            'error' => 'Ocurrió un error al procesar la solicitud.',
            'message' => $e->getMessage()
        ], 500);
    }
}


public function indexautoruta()
{
    // Obtener todos los autos para el selector
    $autos = Auto::all();

    return view('dashboard.graficoautoruta', compact('autos'));
}
public function obtenerIngresosPorRutasPorAuto(Request $request)
{
    $request->validate([
        'auto' => 'required|integer',
        'fecha_inicio' => 'nullable|date',
        'fecha_fin' => 'nullable|date',
    ]);

    $autoId = $request->input('auto');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    // Consulta para obtener los ingresos por rutas y contar turnos únicos
    $query = Ingreso::query()
        ->where('auto_id', $autoId);

    if ($fechaInicio) {
        $query->where('fecha', '>=', $fechaInicio);
    }

    if ($fechaFin) {
        $query->where('fecha', '<=', $fechaFin);
    }

    // Modificar la consulta para contar los turnos únicos y sumar los ingresos
    $ingresos = $query->with('ruta') // Asegúrate de definir la relación 'ruta' en tu modelo
        ->selectRaw('
            ruta.ciudad_inicial, 
            ruta.ciudad_final, 
            COUNT(ingreso.turno_id) as numero_turnos, 
            SUM(ingreso.monto) as monto
        ')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->groupBy('ruta.ciudad_inicial', 'ruta.ciudad_final')
        ->get();

    // Formatear la respuesta
    $labels = [];
    $data = [];
    $numeroTurnos = [];
    $totalIngresos = 0;

    foreach ($ingresos as $ingreso) {
        $abreviaciones = [
            'TRUJILLO' => 'TRUJ',
            'CAJAMARCA' => 'CAXA',
            'JAEN' => 'JAEN',
            'CHICLAYO' => 'CHIC',
            'PIURA' => 'PIUR',
            'LA VICTORIA' => 'LIMA',
            'MORALES' => 'TARA',
        ];
        $ciudad_inicial = strtoupper(trim($ingreso->ciudad_inicial));
        $ciudad_final = strtoupper(trim($ingreso->ciudad_final));
        $ciudadInicial = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
        $ciudadFinal = $abreviaciones[$ciudad_final] ?? $ciudad_final;

        // Construir etiquetas con ciudad inicial y ciudad final
        $labels[] = $ciudadInicial . ' - ' . $ciudadFinal;
        $data[] = $ingreso->monto;
        $numeroTurnos[] = $ingreso->numero_turnos; // Agregar el número de turnos
        $totalIngresos += $ingreso->monto;
    }

    return response()->json([
        'labels' => $labels,
        'data' => $data,
        'numeroTurnos' => $numeroTurnos, // Incluir el número de turnos
        'total' => $totalIngresos,
    ]);
}



}
