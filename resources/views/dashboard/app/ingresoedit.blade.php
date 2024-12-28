@extends('dashboard.index')

@section('content')
<div class="container">
    <br>
    <br>
    <h1>Editar Ingreso</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $abreviaciones = [
            'TRUJILLO' => 'TRUJ',
            'CAJAMARCA' => 'CAXA',
            'JAEN' => 'JAEN',
            'CHICLAYO' => 'CHIC',
            'PIURA' => 'PIUR',
            'LAVICTORIA' => 'LIMA',
            'MORALES' => 'TARA',
        ];
    @endphp

    <form action="{{ route('ingresos.update', $ingreso->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="auto_id">Auto</label>
            <select name="auto_id" class="form-control">
                @foreach ($autos as $auto)
                    <option value="{{ $auto->id }}" {{ $ingreso->auto_id == $auto->id ? 'selected' : '' }}>
                        {{ $auto->placa }} ({{ $auto->proveedor }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="ruta_id">Ruta</label>
            <select name="ruta_id" class="form-control">
                @foreach ($rutas as $ruta)
                    @php
                        // Obtener la abreviación de las ciudades si existen
                        $ciudad_inicial_abreviada = $abreviaciones[$ruta->ciudad_inicial] ?? $ruta->ciudad_inicial;
                        $ciudad_final_abreviada = $abreviaciones[$ruta->ciudad_final] ?? $ruta->ciudad_final;
                    @endphp
                    <option value="{{ $ruta->id }}" {{ $ingreso->ruta_id == $ruta->id ? 'selected' : '' }}>
                        {{ $ciudad_inicial_abreviada }} - {{ $ciudad_final_abreviada }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="turno_id">Turno</label>
            <select name="turno_id" class="form-control">
                @foreach ($turnos as $turno)
                    <option value="{{ $turno->id }}" {{ $ingreso->turno_id == $turno->id ? 'selected' : '' }}>
                        {{ $turno->hora }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="servicio">Serial</label>
            <input type="text" name="servicio" class="form-control" value="{{ $ingreso->servicio }}">
        </div>

        <div class="form-group">
            <label for="monto">Monto</label>
            <input type="number" name="monto" class="form-control" value="{{ $ingreso->monto }}">
        </div>

        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ $ingreso->fecha }}">
        </div>

        <div class="form-group">
            <label for="serial">Serial</label>
            <input type="text" name="serial" class="form-control" value="{{ $ingreso->serial }}">
        </div>

        <button type="submit" class="btn btn-primary">Actualizar Ingreso</button>
    </form>
</div>
@endsection
