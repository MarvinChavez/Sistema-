<?php

namespace App\Imports;

use App\Models\Auto;
use App\Models\Ingreso;
use App\Models\Ruta;
use App\Models\Turno;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Psy\Readline\Hoa\Console;
use Illuminate\Support\Facades\Log;


class IngresoImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Validación de datos nulos
            if (in_array(null, array_slice($row, 5, 12), true)) {
                Log::error('Fila incompleta: ' . json_encode($row));
                return null; // O lanza una excepción si prefieres
            }
    
            // Acceder a las columnas usando los índices
            $placa = $row[8];
            $proveedor = $row[7];
            $ciudadInicial = $row[13];
            $ciudadFinal = $row[14];
            $horaturno = $row[12];
            $servicio = trim($row[11]);
            $monto = $row[15];
            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $row[10])->format('Y-m-d');
            $serie = $row[5];
            $numero = $row[6];
    
            // Crear o encontrar el auto
            $auto = Auto::firstOrCreate(['placa' => $placa], ['proveedor' => $proveedor]);
    
            // Crear o encontrar la ruta
            $ruta = Ruta::firstOrCreate([
                'ciudad_inicial' => $ciudadInicial,
                'ciudad_final' => $ciudadFinal,
            ]);
    
            // Crear o encontrar el turno
            $turno = Turno::firstOrCreate(['hora' => $horaturno]);
    
            return new Ingreso([
                'auto_id' => $auto->id,
                'ruta_id' => $ruta->id,
                'turno_id' => $turno->id,
                'fecha' => $fecha,
                'monto' => $monto,
                'servicio'=>$servicio,
                'serial' => $serie . $numero
            ]);
        } catch (\Exception $e) {
            Log::error('Error en la importación en la fila: ' . json_encode($row) . ' Error: ' . $e->getMessage());
            throw new \Exception('Error en la importación en la fila: ' . json_encode($row), 0, $e);
        }
    }
    
}
