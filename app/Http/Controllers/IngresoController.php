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
    // Initialize the base query
    $query = DB::table('ingreso');

    // Get the start and end date values
    $fechaInicio = $request->filled('fecha_inicio') ? $request->input('fecha_inicio') : null;
    $fechaFin = $request->filled('fecha_fin') ? $request->input('fecha_fin') : date('Y-m-d');

    // Filter by date if `fecha_inicio` is provided
    if ($fechaInicio) {
        $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    } else {
        // If `fecha_inicio` is not specified, filter until `fecha_fin`
        $query->where('fecha', '<=', $fechaFin);
    }

    // Filter by service if `servicio` is provided
    if ($request->filled('servicio') && $request->input('servicio') !== '') {
        $servicio = trim($request->input('servicio'));  // Remove extra spaces
        $query->where('servicio', '=', $servicio); // Filter by exact match on service
    }

    // Select and group the income by date, including passengers count
    $ingresos = $query->select(
        DB::raw('DATE(fecha) as fecha'), 
        DB::raw('SUM(monto) as total'),
        DB::raw('SUM(pasajero) as total_pasajeros') // Sum of passengers
    )
    ->groupBy(DB::raw('DATE(fecha)'))
    ->orderBy('fecha', 'asc')
    ->get();

    // Calculate the total amount by summing the daily totals
    $montoTotal = (int) $ingresos->sum('total');
    $totalPasajeros = (int) $ingresos->sum('total_pasajeros');

    // Format the income data
    $ingresosFormateados = $ingresos->map(function($ingreso) {
        return [
            'fecha' => $ingreso->fecha,
            'monto' => round($ingreso->total),
            'pasajeros' => $ingreso->total_pasajeros // Covert passengers to integer
        ];
    });

    // Return the JSON response with formatted data
    return response()->json([
        'ingresos' => $ingresosFormateados,
        'montoTotal' =>number_format($montoTotal, 0, '.', ","),  // Total income as integer
        'totalPasajeros' => $totalPasajeros // Total passengers as integer
    ]);
}

    
public function ingresosPorRutaHoy(Request $request)
{
    // Obtener la fecha desde la petición
    $fechaInicio = $request->input('fecha_inicio');

    // Validación de entrada
    $request->validate([
        'servicio' => 'nullable|string',
        'fecha_inicio' => 'nullable|date',      
    ]);

    // Abreviaciones de las ciudades
    $abreviaciones = [
        'TRUJILLO' => 'TRUJ',
        'CAJAMARCA' => 'CAXA',
        'JAEN' => 'JAEN',
        'CHICLAYO' => 'CHIC',
        'PIURA' => 'PIUR',
        'LA VICTORIA' => 'LIMA',
        'MORALES' => 'TARA',
    ];

    // CASE para ciudad inicial
    $ciudadInicialCase = "CASE";
    foreach ($abreviaciones as $ciudad => $abreviacion) {
        $ciudadInicialCase .= " WHEN TRIM(ruta.ciudad_inicial) = '$ciudad' THEN '$abreviacion'";
    }
    $ciudadInicialCase .= " ELSE TRIM(ruta.ciudad_inicial) END";

    // CASE para ciudad final
    $ciudadFinalCase = "CASE";
    foreach ($abreviaciones as $ciudad => $abreviacion) {
        $ciudadFinalCase .= " WHEN TRIM(ruta.ciudad_final) = '$ciudad' THEN '$abreviacion'";
    }
    $ciudadFinalCase .= " ELSE TRIM(ruta.ciudad_final) END";

    // Construcción de la consulta
    $query = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->select(
            'ruta.id as ruta_id',
            DB::raw("CONCAT($ciudadInicialCase, ' - ', $ciudadFinalCase) as ruta"),
            DB::raw('SUM(ingreso.monto) as total'),
            DB::raw('SUM(ingreso.pasajero) as total_pasajeros') // Nueva columna para contar pasajeros
        )
        ->where('ingreso.fecha', '=', $fechaInicio)
        ->groupBy('ruta.id', 'ruta.ciudad_inicial', 'ruta.ciudad_final')
        ->orderBy('total', 'desc'); // Ordenar de mayor a menor por monto

    // Filtrar por servicio si se proporciona
    if ($request->filled('servicio')) {
        $servicio = $request->input('servicio');
        $query->where('ingreso.servicio', '=', $servicio);
    }

    // Obtener los resultados
    $ingresos = $query->get();

    // Calcular el monto total de todos los ingresos filtrados
    $totalIngresosQuery = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->where('ingreso.fecha', '=', $fechaInicio);

    // Aplicar filtro de servicio si se proporciona
    if ($request->filled('servicio')) {
        $totalIngresosQuery->where('ingreso.servicio', '=', $servicio);
    }

    $montoTotal = $totalIngresosQuery->sum('ingreso.monto'); // Suma total de los ingresos
    $totalPasajeros = $totalIngresosQuery->sum('ingreso.pasajero'); // Suma total de pasajeros

    // Preparar los datos para el gráfico
    $data = [
        'labels' => $ingresos->pluck('ruta'),  // Rutas para el eje X
'montos' => $ingresos->pluck('total')->map(function($monto) {
    return round($monto); // Redondea los valores a números enteros
}),        'pasajeros' => $ingresos->pluck('total_pasajeros'),  // Cantidad de pasajeros por ruta
        'montoTotal' => number_format($montoTotal, 0, '.', ","), // Monto total con formato
        'totalPasajeros' => $totalPasajeros, // Total de pasajeros
    ];

    // Retornar los datos como JSON
    return response()->json($data);
}

public function ingresosPorOficinaHoy(Request $request)
{
    // Validar los datos de entrada
    $request->validate([
        'servicio' => 'nullable|string',
        'fecha_inicio' => 'nullable|date',      
    ]);

    // Obtener el día actual o la fecha proporcionada
    $fechaInicio = $request->input('fecha_inicio', now()->toDateString());

    // Abreviaciones de las ciudades
    $abreviaciones = [
        'TRUJILLO' => 'TRUJ',
        'CAJAMARCA' => 'CAXA',
        'JAEN' => 'JAEN',
        'CHICLAYO' => 'CHIC',
        'PIURA' => 'PIUR',
        'LA VICTORIA' => 'LIMA',
        'MORALES' => 'TARA',
    ];

    // Construir una cláusula CASE para ciudad_inicial
    $ciudadInicialCase = "CASE";
    foreach ($abreviaciones as $ciudad => $abreviacion) {
        $ciudadInicialCase .= " WHEN TRIM(ruta.ciudad_inicial) = '$ciudad' THEN '$abreviacion'";
    }
    $ciudadInicialCase .= " ELSE TRIM(ruta.ciudad_inicial) END";

    // Construir la consulta para sumar ingresos agrupados por ciudad inicial
    $query = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->select(
            DB::raw("$ciudadInicialCase as ciudad_inicial"),
            DB::raw('SUM(ingreso.monto) as total'),
            DB::raw('SUM(ingreso.pasajero) as total_pasajeros') // Sumar pasajeros
        )
        ->where('ingreso.fecha', '=', $fechaInicio)
        ->groupBy('ruta.ciudad_inicial')
        ->orderBy('total', 'desc'); // Ordenar de mayor a menor por monto

    // Filtrar por servicio si se proporciona
    if ($request->filled('servicio')) {
        $servicio = $request->input('servicio');
        $query->where('ingreso.servicio', '=', $servicio);
    }

    // Obtener los ingresos por ciudad inicial
    $ingresos = $query->get();

    // Calcular el monto total de todos los ingresos filtrados
    $totalIngresosQuery = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->where('ingreso.fecha', '=', $fechaInicio);

    // Aplicar filtro de servicio si se proporciona
    if ($request->filled('servicio')) {
        $totalIngresosQuery->where('ingreso.servicio', '=', $servicio);
    }

    $montoTotal = $totalIngresosQuery->sum('ingreso.monto');
    $totalPasajeros = $totalIngresosQuery->sum('ingreso.pasajero'); // Sumar todos los pasajeros

    // Preparar los datos para el gráfico
    $data = [
        'labels' => $ingresos->pluck('ciudad_inicial'),  // Ciudades iniciales para el eje Y
        'montos' => $ingresos->pluck('total')->map(function($monto) {
    return round($monto); // Redondea los valores a números enteros
}),        // Montos para el eje X
        'pasajeros' => $ingresos->pluck('total_pasajeros'), // Pasajeros para cada ciudad
        'montoTotal' => number_format($montoTotal, 0, '.', ','), // Monto total formateado con separador de miles
        'totalPasajeros' => $totalPasajeros // Total de pasajeros
    ];

    // Retornar los datos como JSON
    return response()->json($data);
}
public function ingresosPorTurnoHoy(Request $request)
{
    // Validar los datos recibidos
    $request->validate([
        'servicio' => 'nullable|string', // El servicio debe ser un string
        'fecha_inicio' => 'nullable|date', // Fecha de inicio opcional
    ]);

    // Obtener el día actual o la fecha proporcionada
    $fechaInicio = $request->input('fecha_inicio', now()->toDateString());

    // Definir las abreviaciones para las ciudades
    $abreviaciones = [
        'TRUJILLO' => 'TRUJ',
        'CAJAMARCA' => 'CAXA',
        'JAEN' => 'JAEN',
        'CHICLAYO' => 'CHIC',
        'PIURA' => 'PIUR',
        'LA VICTORIA' => 'LIMA',
        'MORALES' => 'TARA',
    ];

    // Construir una cláusula CASE para abreviar las ciudades iniciales y finales
    $ciudadInicialCase = "CASE";
    foreach ($abreviaciones as $ciudad => $abreviacion) {
        $ciudadInicialCase .= " WHEN TRIM(ruta.ciudad_inicial) = '$ciudad' THEN '$abreviacion'";
    }
    $ciudadInicialCase .= " ELSE TRIM(ruta.ciudad_inicial) END";

    $ciudadFinalCase = "CASE";
    foreach ($abreviaciones as $ciudad => $abreviacion) {
        $ciudadFinalCase .= " WHEN TRIM(ruta.ciudad_final) = '$ciudad' THEN '$abreviacion'";
    }
    $ciudadFinalCase .= " ELSE TRIM(ruta.ciudad_final) END";

    // Construir la consulta
    $query = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->join('turno', 'ingreso.turno_id', '=', 'turno.id')
        ->select(
            DB::raw("CONCAT($ciudadInicialCase, ' - ', $ciudadFinalCase) as ruta"),
            'turno.hora as turno',
            DB::raw('SUM(ingreso.monto) as total'),
            DB::raw('SUM(ingreso.pasajero) as total_pasajeros') // Sumar los pasajeros por turno y ruta
        )
        ->where('ingreso.fecha', '=', $fechaInicio) // Filtrar por fecha
        ->groupBy('ruta.ciudad_inicial', 'ruta.ciudad_final', 'turno.hora')
        ->orderBy('total', 'desc'); // Ordenar de mayor a menor por monto

    // Filtrar por servicio si se proporciona
    if ($request->filled('servicio')) {
        $servicio = $request->input('servicio');
        $query->where('ingreso.servicio', '=', $servicio);
    }

    // Obtener los resultados por ruta y turno
    $ingresos = $query->get();

    // Calcular el monto total de todos los ingresos filtrados
    $totalIngresos = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->join('turno', 'ingreso.turno_id', '=', 'turno.id')
        ->where('ingreso.fecha', '=', $fechaInicio);

    // Aplicar filtro de servicio si se proporciona
    if ($request->filled('servicio')) {
        $totalIngresos->where('ingreso.servicio', '=', $servicio);
    }

    $montoTotal = $totalIngresos->sum('ingreso.monto'); // Suma total de los ingresos
    $totalPasajeros = $totalIngresos->sum('ingreso.pasajero'); // Suma total de los pasajeros

    // Preparar los datos para el gráfico
    $data = [
        'labels' => $ingresos->map(function ($item) {
            return $item->ruta . ' (' . $item->turno . ')';
        }), // Rutas y turnos para las etiquetas
        'montos' => $ingresos->pluck('total')->map(function($monto) {
            return round($monto); // Redondea los valores a números enteros
        }),        // Montos para el eje X
        'pasajeros' => $ingresos->pluck('total_pasajeros'), // Pasajeros para cada ruta y turno
        'montoTotal' => number_format($montoTotal, 0, '.', ','), // Monto total con formato
        'totalPasajeros' => $totalPasajeros // Total de pasajeros
    ];

    // Retornar los datos como JSON
    return response()->json($data);
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
            'autos' => 'required|array',
            'servicio' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        // Obtener los parámetros de la solicitud
        $autoIds = $request->input('autos');
        $servicio = $request->input('servicio');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Consultar los ingresos de autos con pasajeros y turnos
        $query = DB::table('ingreso')
            ->select(
                'ingreso.auto_id',
                'auto.placa',
                DB::raw('SUM(ingreso.monto) as total_monto'),
                DB::raw('SUM(ingreso.pasajero) as total_pasajeros'),
                DB::raw('COUNT(ingreso.turno_id) as total_turnos'),
                DB::raw('DATE(ingreso.fecha) as fecha')
            )
            ->join('auto', 'ingreso.auto_id', '=', 'auto.id')
            ->whereIn('ingreso.auto_id', $autoIds)
            ->when($servicio, function ($query) use ($servicio) {
                return $query->where('ingreso.servicio', $servicio);
            })
            ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
                return $query->whereBetween('ingreso.fecha', [$fechaInicio, $fechaFin]);
            })
            ->when($fechaInicio, function ($query) use ($fechaInicio) {
                return $query->where('ingreso.fecha', '>=', $fechaInicio);
            })
            ->when($fechaFin, function ($query) use ($fechaFin) {
                return $query->where('ingreso.fecha', '<=', $fechaFin);
            })
            ->groupBy('ingreso.auto_id', 'auto.placa', 'fecha')
            ->orderBy('fecha')
            ->get();

        if ($query->isEmpty()) {
            return response()->json([
                'autos' => [],
                'total_general' => 0,
                'total_pasajeros' => 0,
                'total_turnos' => 0,
            ]);
        }

        // Preparar la respuesta
        $autos = [];
        $totalGeneral = 0;
        $totalPasajeros = 0;
        $totalTurnos = 0;

        foreach ($autoIds as $autoId) {
            $datosAuto = $query->filter(fn($resultado) => $resultado->auto_id == $autoId);

            $fechas = [];
            $montos = [];
            $pasajeros = [];
            $turnos = [];
            $nombreAuto = '';
            $totalMontos = 0;
            $totalPasajerosAuto = 0;
            $totalTurnosAuto = 0;
            $ultimoRegistro = null;

            foreach ($datosAuto as $resultado) {
                $fechas[] = $resultado->fecha;
                $montos[] = $resultado->total_monto;
                $pasajeros[] = $resultado->total_pasajeros;
                $turnos[] = $resultado->total_turnos;
                $totalMontos += $resultado->total_monto;
                $totalPasajerosAuto += $resultado->total_pasajeros;
                $totalTurnosAuto += $resultado->total_turnos;
                $nombreAuto = $resultado->placa;
                $ultimoRegistro = $resultado;
            }

            $montoPromedio = count($montos) > 0 ? $totalMontos / count($montos) : 0;

            $autos[] = [
                'nombre' => $nombreAuto ?: 'Auto ' . $autoId,
                'fechas' => $fechas,
                'montos' => $montos,
                'pasajeros' => $pasajeros,
                'turnos' => $turnos,
                'monto_promedio' => round($montoPromedio, 2),
                'total_pasajeros' => $totalPasajerosAuto,
                'total_turnos' => $totalTurnosAuto,
                'ultimo_registro' => $ultimoRegistro ? [
                    'fecha' => $ultimoRegistro->fecha,
                    'monto' => $ultimoRegistro->total_monto
                ] : null,
                'total' => number_format($totalMontos, 0, '.', ','),
            ];

            $totalGeneral += $totalMontos;
            $totalPasajeros += $totalPasajerosAuto;
            $totalTurnos += $totalTurnosAuto;
        }

        return response()->json([
            'autos' => $autos,
            'total_general' => number_format($totalGeneral, 0, '.', ','),
            'total_pasajeros' => $totalPasajeros,
            'total_turnos' => $totalTurnos,
        ]);
    } catch (\Exception $e) {
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
    public function indexDia()
    {

        return view('dashboard.paneldia');
    }
    public function indexoficina()
    {
        // Obtener todos los autos para el selector
        $rutas = Ruta::all();

        return view('dashboard.grafico-oficina', compact('rutas'));
    }
    public function filtrarOficina(Request $request)
{
    $request->validate([
        'ciudades' => 'required|array',
        'servicio' => 'nullable|string',
        'fecha_inicio' => 'nullable|date',
        'fecha_fin' => 'nullable|date',
    ]);

    $ciudades = $request->input('ciudades');
    $servicio = $request->input('servicio');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    $query = DB::table('ingreso')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->select(
            'ruta.ciudad_inicial',
            'ingreso.fecha',
            DB::raw('SUM(ingreso.monto) as total_monto'),
            DB::raw('SUM(ingreso.pasajero) as total_pasajeros')
        )
        ->whereIn('ruta.ciudad_inicial', $ciudades);

    if ($servicio) {
        $query->where('ingreso.servicio', $servicio);
    }

    if ($fechaInicio && $fechaFin) {
        $query->whereBetween('ingreso.fecha', [$fechaInicio, $fechaFin]);
    } elseif ($fechaInicio) {
        $query->where('ingreso.fecha', '>=', $fechaInicio);
    } elseif ($fechaFin) {
        $query->where('ingreso.fecha', '<=', $fechaFin);
    }

    $resultados = $query
        ->groupBy('ruta.ciudad_inicial', 'ingreso.fecha')
        ->orderBy('ruta.ciudad_inicial')
        ->orderBy('ingreso.fecha')
        ->get();

    if ($resultados->isEmpty()) {
        return response()->json([
            'ciudades' => [],
            'total_general' => 0,
            'total_pasajeros' => 0
        ]);
    }

    $abreviaciones = [
        'TRUJILLO' => 'TRUJ',
        'CAJAMARCA' => 'CAXA',
        'JAEN' => 'JAEN',
        'CHICLAYO' => 'CHIC',
        'PIURA' => 'PIUR',
        'LA VICTORIA' => 'LIMA',
        'MORALES' => 'TARA',
    ];

    $ciudadesResultados = [];
    $totalGeneral = 0;
    $totalPasajeros = 0;

    foreach ($ciudades as $ciudad) {
        $datosCiudad = $resultados->filter(function ($resultado) use ($ciudad) {
            return strtoupper(trim($resultado->ciudad_inicial)) === strtoupper(trim($ciudad));
        });

        $fechas = [];
        $montos = [];
        $pasajeros = [];
        $montoTotalCiudad = 0;
        $pasajerosCiudad = 0;

        foreach ($datosCiudad as $resultado) {
            $fechas[] = $resultado->fecha;
            $montos[] = round($resultado->total_monto);
            $pasajeros[] = $resultado->total_pasajeros;
            $montoTotalCiudad += $resultado->total_monto;
            $pasajerosCiudad += $resultado->total_pasajeros;
        }

        $promedio = count($montos) > 0 ? array_sum($montos) / count($montos) : 0;
        $ultimoRegistro = $datosCiudad->last();
        $ultimaFecha = $ultimoRegistro ? $ultimoRegistro->fecha : null;
        $ultimoMonto = $ultimoRegistro ? round($ultimoRegistro->total_monto, 2) : null;

        $ciudadesResultados[] = [
            'ciudad_inicial' => $abreviaciones[strtoupper(trim($ciudad))] ?? strtoupper(trim($ciudad)),
            'montoTotal' => number_format($montoTotalCiudad, 0, '.', ','),
            'fechas' => $fechas,
            'montos' => $montos,
            'pasajeros' => $pasajeros,
            'promedio' => round($promedio, 0),
            'ultimo_registro' => [
                'fecha' => $ultimaFecha,
                'monto' => $ultimoMonto,
            ],
            'total_pasajeros' => $pasajerosCiudad,
        ];

        $totalGeneral += $montoTotalCiudad;
        $totalPasajeros += $pasajerosCiudad;
    }

    return response()->json([
        'ciudades' => $ciudadesResultados,
        'total_general' => number_format($totalGeneral, 0, '.', ','),
        'total_pasajeros' => $totalPasajeros
    ]);
}
public function filtrarRuta(Request $request)
{
    // Validar los datos recibidos
    $request->validate([
        'rutas' => 'required|array',       // Las rutas seleccionadas deben ser un array
        'servicio' => 'nullable|string',   // El servicio debe ser un string "spi" o "spp"
        'fecha_inicio' => 'nullable|date',   // Fecha de inicio válida, pero puede ser nula
        'fecha_fin' => 'nullable|date',      // Fecha de fin válida, pero puede ser nula
    ]);
    $rutaIds = $request->input('rutas');
    $servicio = $request->input('servicio');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');

    $query = DB::table('ingreso')
        ->select(
            'ingreso.ruta_id',
            'ingreso.fecha',
            DB::raw('SUM(ingreso.monto) as total_monto'),
            DB::raw('SUM(ingreso.pasajero) as total_pasajeros'),
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
    $totalGeneral = 0;
    $totalGeneralPasajeros = 0;

    foreach ($rutaIds as $rutaId) {
        $datosRuta = $resultados->filter(function ($resultado) use ($rutaId) {
            return $resultado->ruta_id == $rutaId;
        });

        // Construir fechas, montos y pasajeros por cada ruta
        $fechas = [];
        $montos = [];
        $pasajeros = [];
        $totalMontos = 0;
        $totalPasajerosRuta = 0;
        $nombreRuta = '';
        $ultimoRegistro = null;

        foreach ($datosRuta as $resultado) {
            $fechas[] = $resultado->fecha;
            $montos[] = $resultado->total_monto;
            $pasajeros[] = $resultado->total_pasajeros;
            $totalMontos += $resultado->total_monto;
            $totalPasajerosRuta += $resultado->total_pasajeros;
            
            // Construir el nombre de la ruta si aún no se ha definido
            if (!$nombreRuta) {
                $ciudad_inicial = strtoupper(trim($resultado->ciudad_inicial));
                $ciudad_final = strtoupper(trim($resultado->ciudad_final));
                $ciudadInicial = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
                $ciudadFinal = $abreviaciones[$ciudad_final] ?? $ciudad_final;
                $nombreRuta = $ciudadInicial . ' - ' . $ciudadFinal;
            }
                $ultimoRegistro = $resultado;
        }
        $montoPromedio = count($montos) > 0 ? $totalMontos / count($montos) : 0;
        $rutas[] = [
            'nombre' => $nombreRuta ?: 'Ruta ' . $rutaId,
            'fechas' => $fechas,
            'montos' => $montos,
            'pasajeros' => $pasajeros,
            'monto_promedio' => round($montoPromedio, 2),
            'ultimo_registro' => $ultimoRegistro
                ? [
                    'fecha' => $ultimoRegistro->fecha,
                    'monto' => $ultimoRegistro->total_monto,
                ]
                : null,
            'total' => number_format($totalMontos, 0, '.', ','),
            'total_pasajeros' => $totalPasajerosRuta
        ];
        $totalGeneral += $totalMontos;
        $totalGeneralPasajeros += $totalPasajerosRuta;
    }
    return response()->json([
        'rutas' => $rutas,
        'total_general' => number_format($totalGeneral, 0, '.', ','),
        'total_pasajeros' => $totalGeneralPasajeros
    ]);
}
    public function indexautopie()
    {
        $autos = Auto::all();

        return view('dashboard.graficoautopie', compact('autos'));
    }
    public function ingresosPorAutos(Request $request)
{
    $autosIds = $request->input('autos');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');
    $resultados = Ingreso::whereIn('auto_id', $autosIds)
        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
        ->groupBy('auto_id')
        ->selectRaw('auto_id, SUM(monto) as total_monto, SUM(pasajero) as total_pasajeros')
        ->get();
    $totalGeneral = $resultados->sum('total_monto');
    $totalPasajeros = $resultados->sum('total_pasajeros');
    $data = $resultados->map(function($resultado) use ($totalGeneral, $totalPasajeros) {
        $porcentaje = $totalGeneral > 0 ? ($resultado->total_monto / $totalGeneral) * 100 : 0; 
        return [
            'placa' => $resultado->auto->placa,
            'total_monto' => $resultado->total_monto,
            'total_pasajeros' => $resultado->total_pasajeros,
            'porcentaje' => number_format($porcentaje, 2),
            'total_general' => number_format($totalGeneral, 2),
            'total_pasajeros_general' => $totalPasajeros
        ];
    });

    return response()->json($data);
}


public function indexrutapie()
{
    $rutas = Ruta::all();

    return view('dashboard.graficorutapie', compact('rutas'));
}
public function ingresosPorRutas(Request $request)
{
    $rutasIds = $request->input('rutas');
    $fechaInicio = $request->input('fecha_inicio');
    $fechaFin = $request->input('fecha_fin');
    $resultados = Ingreso::whereIn('ruta_id', $rutasIds)
        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
        ->groupBy('ruta_id')
        ->selectRaw('ruta_id, SUM(monto) as total_monto, SUM(pasajero) as total_pasajeros')
        ->get();

    $totalGeneral = $resultados->sum('total_monto');
    $totalPasajerosGeneral = $resultados->sum('total_pasajeros');
    $abreviaciones = [
        'TRUJILLO' => 'TRUJ',
        'CAJAMARCA' => 'CAXA',
        'JAEN' => 'JAEN',
        'CHICLAYO' => 'CHIC',
        'PIURA' => 'PIUR',
        'LAVICTORIA' => 'LIMA',
        'MORALES' => 'TARA'
    ];
    $data = $resultados->map(function($resultado) use ($totalGeneral, $totalPasajerosGeneral) {
        $porcentaje = $totalGeneral > 0 ? ($resultado->total_monto / $totalGeneral) * 100 : 0; // Evitar división por cero
        $ciudadInicial = strtoupper(trim($resultado->ruta->ciudad_inicial));
        $ciudadFinal = strtoupper(trim($resultado->ruta->ciudad_final));
    
        $rutainicial = $abreviaciones[$ciudadInicial] ?? $ciudadInicial;
        $rutafinal = $abreviaciones[$ciudadFinal] ?? $ciudadFinal;
        return [
            'rutainicial' => $rutainicial,
            'rutafinal' => $rutafinal,
            'total_monto' => $resultado->total_monto,
            'total_pasajeros' => $resultado->total_pasajeros, 
            'porcentaje' => number_format($porcentaje, 2), 
            'total_general' => number_format($totalGeneral, 2),
            'total_pasajeros_general' => $totalPasajerosGeneral 
        ];
    });

    return response()->json($data);
}
    public function indexturno()
    {
    $rutas = Ruta::all();

    return view('dashboard.graficoturno', compact('rutas'));
    }

    public function obtenerTurnosPorRuta(Request $request, $rutaId)
{
    $tipoServicio = $request->input('servicio');
    $query = Ingreso::where('ruta_id', $rutaId);
    if ($tipoServicio !== "Total") {
        $query->where('servicio', $tipoServicio);
    }
    $turnos = $query->distinct()->pluck('turno_id');
    if ($turnos->isEmpty()) {
        return response()->json([]);
    }
    $turnosDetalles = Turno::whereIn('id', $turnos)
        ->orderBy('hora') 
        ->get();

    return response()->json($turnosDetalles);
}
public function obtenerIngresosFiltrados(Request $request)
{
    try {
        // Validar los datos recibidos
        $request->validate([
            'turnos' => 'required|array',      
            'ruta' => 'required|integer',     
            'servicio' => 'required|string',   
            'fecha_inicio' => 'nullable|date', 
            'fecha_fin' => 'nullable|date',   
        ]);
        $turnoIds = $request->input('turnos');
        $ruta = $request->input('ruta');
        $servicio = $request->input('servicio');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $query = DB::table('ingreso')
            ->select(
                'ingreso.turno_id',
                'turno.hora',
                DB::raw('SUM(ingreso.monto) as total_monto'),
                DB::raw('SUM(ingreso.pasajero) as total_pasajeros'),
                DB::raw('DATE(ingreso.fecha) as fecha')
            )
            ->join('turno', 'ingreso.turno_id', '=', 'turno.id')
            ->whereIn('ingreso.turno_id', $turnoIds)
            ->where('ingreso.ruta_id', $ruta)
            ->when($servicio !== 'Total', function ($query) use ($servicio) {
                return $query->where('ingreso.servicio', $servicio);
            })
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
        if ($query->isEmpty()) {
            return response()->json([
                'turnos' => [],
                'total_general' => 0,
                'total_pasajeros' => 0,
            ]);
        }
        $turnos = [];
        $totalGeneral = 0;
        $totalPasajeros = 0;

        foreach ($turnoIds as $turnoId) {
            $datosTurno = $query->filter(function ($resultado) use ($turnoId) {
                return $resultado->turno_id == $turnoId;
            });
            $fechas = [];
            $montos = [];
            $cantidadPasajeros = [];
            $nombreTurno = '';
            $totalMontos = 0;
            $totalPasajerosTurno = 0;
            $ultimoRegistro = null;

            foreach ($datosTurno as $resultado) {
                $fechas[] = $resultado->fecha;
                $montos[] = $resultado->total_monto;
                $cantidadPasajeros[] = $resultado->total_pasajeros;
                $totalMontos += $resultado->total_monto;
                $totalPasajerosTurno += $resultado->total_pasajeros;
                $nombreTurno = $resultado->hora;
                $ultimoRegistro = $resultado;
            }
            $montoPromedio = count($montos) > 0 ? $totalMontos / count($montos) : 0;
            $turnos[] = [
                'nombre' => $nombreTurno ?: 'Turno ' . $turnoId,
                'fechas' => $fechas,
                'montos' => $montos,
                'total' => number_format($totalMontos, 0, '.', ','),
                'pasajeros' => $cantidadPasajeros,
                'monto_promedio' => round($montoPromedio, 2),
                'ultimo_registro' => $ultimoRegistro
                    ? [
                        'fecha' => $ultimoRegistro->fecha,
                        'monto' => $ultimoRegistro->total_monto,
                    ]
                    : null
            ];
            $totalGeneral += $totalMontos;
            $totalPasajeros += $totalPasajerosTurno;
        }
        return response()->json([
            'turnos' => $turnos,
            'total_general' => number_format($totalGeneral, 0, '.', ','),
            'total_pasajeros' => $totalPasajeros,
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

    // Consulta base
    $query = Ingreso::query()
        ->where('auto_id', $autoId);

    if ($fechaInicio) {
        $query->where('fecha', '>=', $fechaInicio);
    }

    if ($fechaFin) {
        $query->where('fecha', '<=', $fechaFin);
    }

    // Modificar la consulta para incluir número de pasajeros
    $ingresos = $query->with('ruta') // Asegúrate de definir la relación 'ruta' en tu modelo
        ->selectRaw('
            ruta.ciudad_inicial, 
            ruta.ciudad_final, 
            COUNT(ingreso.turno_id) as numero_turnos, 
            SUM(ingreso.monto) as monto,
            SUM(ingreso.pasajero) as total_pasajeros
        ')
        ->join('ruta', 'ingreso.ruta_id', '=', 'ruta.id')
        ->groupBy('ruta.ciudad_inicial', 'ruta.ciudad_final')
        ->get();

    // Formatear la respuesta
    $labels = [];
    $data = [];
    $numeroTurnos = [];
    $totalIngresos = 0;
    $totalPasajerosGeneral = 0;
    $pasajerosPorRuta = [];

    $abreviaciones = [
        'TRUJILLO' => 'TRUJ',
        'CAJAMARCA' => 'CAXA',
        'JAEN' => 'JAEN',
        'CHICLAYO' => 'CHIC',
        'PIURA' => 'PIUR',
        'LA VICTORIA' => 'LIMA',
        'MORALES' => 'TARA',
    ];

    foreach ($ingresos as $ingreso) {
        $ciudad_inicial = strtoupper(trim($ingreso->ciudad_inicial));
        $ciudad_final = strtoupper(trim($ingreso->ciudad_final));
        $ciudadInicial = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
        $ciudadFinal = $abreviaciones[$ciudad_final] ?? $ciudad_final;

        // Construir etiquetas con ciudad inicial y ciudad final
        $labels[] = $ciudadInicial . ' - ' . $ciudadFinal;
        $data[] = $ingreso->monto;
        $numeroTurnos[] = $ingreso->numero_turnos;
        $pasajerosPorRuta[] = $ingreso->total_pasajeros;
        $totalIngresos += $ingreso->monto;
        $totalPasajerosGeneral += $ingreso->total_pasajeros;
    }

    return response()->json([
        'labels' => $labels,
        'data' => $data,
        'numeroTurnos' => $numeroTurnos,
        'pasajerosPorRuta' => $pasajerosPorRuta,
        'total' => $totalIngresos,
        'total_pasajeros_general' => $totalPasajerosGeneral,
    ]);
}




}
