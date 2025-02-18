@extends('dashboard.index')
@section('title', 'Gráfico de Ingresos por Autos')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <br><br>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm p-4">
                <div class="position-relative mt-4">
                    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 0px; z-index: 10;">
                        <a class="btn btn-light me-1" href="{{ route('graficoDia') }}" id="btn-dia">I.Dia</a>
                        <a class="btn btn-light me-1" href="{{ route('grafico.index2') }}" id="btn-general">I.Total</a>
                        <a class="btn btn-light me-1" href="{{ route('graficooficina') }}" >I.Oficina</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoruta') }}" >I.Ruta</a>
                        <a class="btn btn-light me-1" href="{{ route('indexrutapie') }}" >I.Ruta Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexturno') }}" >I.Turno</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoauto') }}" >I.Placa</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautopie') }}" >I.Placa Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautoruta') }}">I. Placa-Ruta</a>
                    </div>
                </div>

                <!-- Selección de rango de fechas -->
                <div class="row justify-content-center mb-4">
                    <div class="col-md-5">
                        <label for="fechaInicio">Fecha de Inicio:</label>
                        <input type="date" id="fechaInicio" class="form-control">
                    </div>
                    <div class="col-md-5">
                        <label for="fechaFin">Fecha de Fin:</label>
                        <input type="date" id="fechaFin" class="form-control">
                    </div>
                </div>

                <!-- Selección de auto -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5>Selecciona Placa:</h5>
                        <select id="autoSelect" class="form-select">
                            <option value="" disabled selected>Selecciona una placa</option>
                            @foreach ($autos as $auto)
                                <option value="{{ $auto->id }}">{{ $auto->placa }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-center mt-4" id="infoIngresos"> <!-- Ocultado por defecto -->
                    <h4 class="card-title text-center mb-4">INGRESOS POR PLACA-RUTA</h4>
                    <h5 id="infoTotales"></h5>
                </div>
                <div class="position-relative mt-4">
                    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
                        <button class="btn btn-light me-1" id="btn-semana">Semana</button>
                        <button class="btn btn-light me-1" id="btn-mes">Mes</button>
                        <button class="btn btn-light" id="btn-año">Año</button>
                    </div>
    
                    <div class="card shadow-sm mt-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div id="graficoPie" style="width: 100%; height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    .btn-light {
        background-color: white;
        color: #333;
        border: none;
        border-radius: 5px;
        font-size: 12px;
        padding: 5px 10px;
    }

    .btn-light:hover {
        background-color: #f8f9fa;
    }
    .graficoPie {
    width: 500px;
    height: 400px;
}
</style>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
google.charts.load('current', {'packages':['corechart', 'pie']});
google.charts.setOnLoadCallback(function() {
    console.log('Google Charts loaded successfully');
});
    
    const autoSelect = document.getElementById('autoSelect');
    const fechaInicioInput = document.getElementById('fechaInicio');
    const fechaFinInput = document.getElementById('fechaFin');

    fechaInicioInput.addEventListener('change', updateChart);
    fechaFinInput.addEventListener('change', updateChart);
    autoSelect.addEventListener('change', updateChart);

    function updateChart() {
    const selectedAuto = autoSelect.value;
    const fechaInicio = fechaInicioInput.value || null;
    const fechaFin = fechaFinInput.value || null;

    if (!selectedAuto) {
        return;
    }

    fetch('{{ route("ingresosPorRutasPorAuto") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            auto: selectedAuto, 
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Datos recibidos:', data); 
        document.getElementById('infoTotales').innerHTML = 
            `Importe Total: S/ ${data.total.toLocaleString('en-US')} - Pasajeros: ${parseInt(data.total_pasajeros_general).toLocaleString('en-US')})`;
        // Datos para Google Charts
        const chartData = [['Ruta', 'Monto']];

        data.labels.forEach((label, index) => {
            const turnos = data.numeroTurnos[index];
            const monto = parseFloat(data.data[index]);
            const pasajeros = data.pasajerosPorRuta[index];

            chartData.push([`${label} (Turnos: ${turnos}, Pasajeros: ${pasajeros})`, monto]);
        });

        drawChart(chartData);
    })
    .catch(error => console.error('Error fetching data:', error));
}

function drawChart(chartData) {
    var data = google.visualization.arrayToDataTable(chartData);
    
    var formatter = new google.visualization.NumberFormat({
        prefix: 'S/.',
        fractionDigits: 0 
    });

    formatter.format(data, 1); 

    var options = {
        title: 'Monto por Ruta',
        is3D: true,
        pieSliceText: 'value', 
        tooltip: { text: 'percentage' },
        slices: { 0: { offset: 0.1 }, 1: { offset: 0.1 }, 2: { offset: 0.1 } },
        pieSliceTextStyle: { color: 'black', fontSize: 10 },
        legend: { position: 'labeled', textStyle: { fontSize: 12 } },
        chartArea: { width: '90%', height: '90%' },
        sliceVisibilityThreshold: 0 
    };

    var chart = new google.visualization.PieChart(document.getElementById('graficoPie'));
    chart.draw(data, options);

    google.visualization.events.addListener(chart, 'ready', function () {
        chart.draw(data, options);
    });
}
function aplicarFiltroFecha(filtro) {
        const hoy = new Date();
        let fechaInicio, fechaFin;

        switch(filtro) {
            case 'dia':
                fechaInicio = hoy.toISOString().split('T')[0];
                fechaFin = hoy.toISOString().split('T')[0];
                break;
            case 'semana':
                fechaInicio = new Date(hoy.setDate(hoy.getDate() - 7)).toISOString().split('T')[0];
                fechaFin = new Date().toISOString().split('T')[0];
                break;
            case 'mes':
                fechaInicio = new Date(hoy.setMonth(hoy.getMonth() - 1)).toISOString().split('T')[0];
                fechaFin = new Date().toISOString().split('T')[0];
                break;
            case 'año':
                fechaInicio = new Date(hoy.setFullYear(hoy.getFullYear() - 1)).toISOString().split('T')[0];
                fechaFin = new Date().toISOString().split('T')[0];
                break;
        }

        fechaInicioInput.value = fechaInicio;
        fechaFinInput.value = fechaFin;

        updateChart();
    }
    document.getElementById('btn-semana').addEventListener('click', () => aplicarFiltroFecha('semana'));
    document.getElementById('btn-mes').addEventListener('click', () => aplicarFiltroFecha('mes'));
    document.getElementById('btn-año').addEventListener('click', () => aplicarFiltroFecha('año'));

</script>

@endsection
