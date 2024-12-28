@extends('dashboard.index')
@section('title', 'Gráfico de Ingresos por Rutas')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <br><br>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm p-4">
                <div class="position-relative mt-4">
                    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 0px; z-index: 10;">
                        <a class="btn btn-light me-1" href="{{ route('grafico.index2') }}" id="btn-general">I.Total</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoruta') }}" id="btn-ruta">I.Ruta</a>
                        <a class="btn btn-light me-1" href="{{ route('indexrutapie') }}" id="btn-auto">I.Ruta Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexturno') }}" id="btn-ruta">I.Turno</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoauto') }}" id="btn-auto">I.Placa</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautopie') }}" id="btn-auto">I.Placa Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautoruta') }}" id="btn-pie">I. Placa-Ruta</a>
                    </div>
                </div>
                <h4 class="card-title text-center mb-4">Gráfico de Ingresos por Rutas</h4>

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

                <!-- Filtros de Fecha en la parte superior (Opcionales) -->
                <div class="d-flex justify-content-center mb-4">
                    <button class="btn btn-light mx-1 filtro-fecha" data-filtro="dia">Día</button>
                    <button class="btn btn-light mx-1 filtro-fecha" data-filtro="semana">Semana</button>
                    <button class="btn btn-light mx-1 filtro-fecha" data-filtro="mes">Mes</button>
                    <button class="btn btn-light mx-1 filtro-fecha" data-filtro="año">Año</button>
                </div>

                 
                <div class="row">
                    <!-- Lista de rutas -->
                    <div class="col-md-4">
                        <h5>Selecciona Rutas:</h5>
                        <div id="rutas-list" class="list-group" style="max-height: 200px; overflow-y: auto;">
                            @foreach ($rutas as $ruta)
                    @php
                        $ciudad_inicial_abreviada = $abreviaciones[$ruta->ciudad_inicial] ?? $ruta->ciudad_inicial;
                        $ciudad_final_abreviada = $abreviaciones[$ruta->ciudad_final] ?? $ruta->ciudad_final;
                    @endphp
                    <label class="list-group-item">
                        <input type="checkbox" value="{{ $ruta->id }}" class="ruta-checkbox"> 
                        {{ $ciudad_inicial_abreviada }} - {{ $ciudad_final_abreviada }}
                    </label>
                @endforeach
                        </div>
                    </div>

                    <!-- Gráfico de pie -->
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <canvas id="graficoPie" style="height: 400px; width: 100%;"></canvas>
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
        background-color: white; /* Fondo blanco */
        color: #333; /* Color del texto */
        border: none; /* Sin borde */
        border-radius: 5px; /* Bordes redondeados */
        box-shadow: none; /* Sin sombra */
        font-size: 12px; /* Tamaño de fuente más pequeño */
        padding: 5px 10px; /* Espaciado interno más pequeño */
    }

    .btn-light:hover {
        background-color: #f8f9fa; /* Fondo al pasar el mouse */
    }
</style>
<script>
    // Configuración inicial del gráfico de pie
    let ctxPie = document.getElementById('graficoPie').getContext('2d');
    let graficoPie = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                label: 'Porcentaje de Monto por Ruta',
                data: [],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(153, 102, 255, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            const value = context.raw;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(2) + '%' : '0%';
                            if (label) {
                                label += ': ';
                            }
                            label += `${value} S/.\n(${percentage})`; // Añadir el porcentaje al tooltip
                            return label;
                        }
                    }
                },
                // Mostrar porcentaje dentro del gráfico
                datalabels: {
                    formatter: (value, context) => {
                        const total = context.chart.data.datasets[0].data.reduce((acc, curr) => acc + curr, 0);
                        if (total === 0) return '0%'; // Evitar NaN
                        const percentage = ((value / total) * 100).toFixed(2) + '%';
                        return percentage;
                    },
                    color: '#fff',
                }
            }
        },
        plugins: [ChartDataLabels] // Añadir plugin de etiquetas de datos
    });

    const checkboxes = document.querySelectorAll('.ruta-checkbox');
    const filtroFechaBotones = document.querySelectorAll('.filtro-fecha');
    const fechaInicioInput = document.getElementById('fechaInicio');
    const fechaFinInput = document.getElementById('fechaFin');

    // Añadir listeners a los checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateChart);
    });

    // Añadir listeners a los botones de filtro rápido de fechas
    filtroFechaBotones.forEach(boton => {
        boton.addEventListener('click', function() {
            aplicarFiltroFecha(this.dataset.filtro);
        });
    });

    // Listener para las fechas seleccionadas
    fechaInicioInput.addEventListener('change', updateChart);
    fechaFinInput.addEventListener('change', updateChart);

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

        // Actualizar campos de fecha
        fechaInicioInput.value = fechaInicio;
        fechaFinInput.value = fechaFin;

        updateChart();
    }

    const abreviaciones = {
    'TRUJILLO': 'TRUJ',
    'CAJAMARCA': 'CAXA',
    'JAEN': 'JAEN',
    'CHICLAYO': 'CHIC',
    'PIURA': 'PIUR',
    'LAVICTORIA': 'LIMA',
    'MORALES': 'TARA',
};
    function updateChart() {
        const selectedRutas = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);

        const fechaInicio = fechaInicioInput.value || null;
        const fechaFin = fechaFinInput.value || null;

        if (selectedRutas.length === 0) {
            graficoPie.data.labels = [];
            graficoPie.data.datasets[0].data = [];
            graficoPie.update();
            return;
        }

        // Hacer la solicitud POST con los rutas seleccionados y el rango de fechas
        fetch('{{ route("ingresosPorRutas") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                rutas: selectedRutas,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos:', data);
            if (!data.length) {
        // Si no hay datos, limpiar el gráfico
        graficoPie.data.labels = [];
        graficoPie.data.datasets[0].data = [];
        graficoPie.update();
        return;
    }

    graficoPie.data.labels = data.map(item => {
    const inicialAbreviacion = abreviaciones[item.rutainicial] || item.rutainicial;
    const finalAbreviacion = abreviaciones[item.rutafinal] || item.rutafinal;
    return `${inicialAbreviacion} - ${finalAbreviacion}`;
});
    // Convierte total_monto a número
    graficoPie.data.datasets[0].data = data.map(item => parseFloat(item.total_monto));

    graficoPie.update();
        })
        .catch(error => console.error('Error fetching data:', error));
    }
</script>
@endsection

