@extends('dashboard.index')
@section('title', 'Home Page')

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
                <br><br>
                <form id="filtros-form" class="row g-3">
                    <div class="col-md-3">
                        <label for="servicio" class="form-label">Tipo Servicio:</label>
                        <select id="servicio" name="servicio" class="form-select">
                            <option value="">Total</option>
                            <option value="SPI">SPI</option>
                            <option value="SPP">SPP</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Seleccionar dia:</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                    </div>
                </form>
            </div>
            <div class="text-center mt-4" id="infoIngresos" style="display: none;">
                <h4 class="card-title text-center mb-4">INGRESOS DEL DÍA</h4>
                <h5 id="infoTotales">Importe Total: S/ 0 <br> P(): 0</h5>
                <h5 id="rangoFechas">Rango de Fechas: - </h5>
            </div>
            <!-- Gráfico -->
<div class="position-relative mt-4">
    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
        <button class="btn btn-light me-1" id="btn-rutas">Ruta</button>
        <button class="btn btn-light me-1" id="btn-oficina">Oficina</button>
        <button class="btn btn-light" id="btn-turno">Turno</button>
    </div>

    <div class="card shadow-sm mt-12 container-fluid">
        <div style="padding-top: 50px;width: auto; height: 700px">
            <!-- Ajusta el canvas para que sea responsivo -->
            <canvas id="graficoIngresos" style="width: 100% height: auto;"></canvas>
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
        box-shadow: none;
        font-size: 12px;
        padding: 5px 10px;
    }

    .btn-light:hover {
        background-color: #f8f9fa;
    }
</style>
<script>
    let ctx = document.getElementById('graficoIngresos').getContext('2d');
    let graficoIngresos;
   
    function filtrarRuta(fecha_inicio) {
    let servicio = document.getElementById('servicio').value;

    fetch('{{ route("grafico.barRuta") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            servicio: servicio,
            fecha_inicio: fecha_inicio
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Datos recibidos:', data);

        if (graficoIngresos) {
            graficoIngresos.destroy();
        }

        graficoIngresos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels, // Eje Y: Rutas
                datasets: [{
                    data: data.montos, // Eje X: Monto
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(tooltipItem) {
                                let monto = data.montos[tooltipItem.dataIndex];
                                let pasajeros = data.pasajeros[tooltipItem.dataIndex];

                                const formatter = new Intl.NumberFormat('es-PE', {
                                    style: 'currency',
                                    currency: 'PEN',
                                    minimumFractionDigits: 0
                                });

                                return [
                                    `Monto: ${formatter.format(monto)}`,
                                    `Pasajeros: ${pasajeros}`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end', // Coloca la etiqueta al final de la barra
                align: 'right', // Alinea la etiqueta a la derecha de la barra
                formatter: function(value, context) {
                    let pasajeros = data.pasajeros[context.dataIndex];

                    return `S/ ${value.toLocaleString('es-PE', { 
                        minimumFractionDigits: 0, 
                        maximumFractionDigits: 0 
                    })} - P= ${pasajeros}`;
                },
                font: {
                    weight: 'bold',
                    size: 12
                },
                color: '#333'
            }

                },
                scales: {
                    x: {
                        beginAtZero: true,
                        suggestedMax: Math.ceil(Math.max(...data.montos) / 6000) * 6000,
                        grid: {
                            display: false
                        },
                        ticks: {
                            stepSize: 3000
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            padding: 10
                        }
                    }
                }
            },
            plugins: [ChartDataLabels] // Activar el plugin
        });

        let fechaInicio = document.getElementById('fecha_inicio').value;
        let fecha_inicio2 = new Date(fechaInicio);
    fecha_inicio2.setDate(fecha_inicio2.getDate() + 1);

        actualizarRangoFechas(fecha_inicio2);
        document.getElementById('infoTotales').innerHTML = `Importe Total: S/ ${(data.montoTotal).toLocaleString('en-US')}- Pasajeros: ${parseInt(data.totalPasajeros).toLocaleString('en-US')}`;
    });
}


function filtrarOficina(fecha_inicio) {
    let servicio = document.getElementById('servicio').value;

    fetch('{{ route("grafico.barOficina") }}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
        servicio: servicio,
        fecha_inicio:fecha_inicio
    })
})
.then(response => response.json())
.then(data => {
    console.log('Datos recibidos:', data);
    if (graficoIngresos) {
            graficoIngresos.destroy();
        }
        graficoIngresos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels, // Eje Y: Rutas
                datasets: [{
                    data: data.montos, // Eje X: Monto
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(tooltipItem) {
                                let monto = data.montos[tooltipItem.dataIndex];
                                let pasajeros = data.pasajeros[tooltipItem.dataIndex];

                                const formatter = new Intl.NumberFormat('es-PE', {
                                    style: 'currency',
                                    currency: 'PEN',
                                    minimumFractionDigits: 0
                                });

                                return [
                                    `Monto: ${formatter.format(monto)}`,
                                    `Pasajeros: ${pasajeros}`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end', // Coloca la etiqueta al final de la barra
                align: 'right', // Alinea la etiqueta a la derecha de la barra
                formatter: function(value, context) {
                    let pasajeros = data.pasajeros[context.dataIndex];

                    return `S/ ${value.toLocaleString('es-PE', { 
                        minimumFractionDigits: 0, 
                        maximumFractionDigits: 0 
                    })} - P= ${pasajeros}`;
                },
                font: {
                    weight: 'bold',
                    size: 12
                },
                color: '#333'
            }

                },
                scales: {
                    x: {
                        beginAtZero: true,
                        suggestedMax: Math.ceil(Math.max(...data.montos) / 6000) * 6000,
                        grid: {
                            display: false
                        },
                        ticks: {
                            stepSize: 3000
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            padding: 10
                        }
                    }
                }
            },
            plugins: [ChartDataLabels] // Activar el plugin
        });
        let fechaInicio = document.getElementById('fecha_inicio').value;
        let fecha_inicio2 = new Date(fechaInicio);
    fecha_inicio2.setDate(fecha_inicio2.getDate() + 1);

        actualizarRangoFechas(fecha_inicio2);
        document.getElementById('infoTotales').innerHTML = `Importe Total: S/ ${(data.montoTotal).toLocaleString('en-US')}- Pasajeros: ${parseInt(data.totalPasajeros).toLocaleString('en-US')}`;
    });
}
function filtrarTurno(fecha_inicio) {
    let servicio = document.getElementById('servicio').value;

    fetch('{{ route("grafico.barTurno") }}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
        servicio: servicio,
        fecha_inicio:fecha_inicio
    })
})
.then(response => response.json())
.then(data => {
    console.log('Datos recibidos:', data);
    if (graficoIngresos) {
            graficoIngresos.destroy();
        }
        graficoIngresos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels, // Eje Y: Rutas
                datasets: [{
                    data: data.montos, // Eje X: Monto
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(tooltipItem) {
                                let monto = data.montos[tooltipItem.dataIndex];
                                let pasajeros = data.pasajeros[tooltipItem.dataIndex];

                                const formatter = new Intl.NumberFormat('es-PE', {
                                    style: 'currency',
                                    currency: 'PEN',
                                    minimumFractionDigits: 0
                                });

                                return [
                                    `Monto: ${formatter.format(monto)}`,
                                    `Pasajeros: ${pasajeros}`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end', // Coloca la etiqueta al final de la barra
                align: 'right', // Alinea la etiqueta a la derecha de la barra
                formatter: function(value, context) {
                    let pasajeros = data.pasajeros[context.dataIndex];

                    return `S/ ${value.toLocaleString('es-PE', { 
                        minimumFractionDigits: 0, 
                        maximumFractionDigits: 0 
                    })} - P= ${pasajeros}`;
                },
                font: {
                    weight: 'bold',
                    size: 12
                },
                color: '#333'
            }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        suggestedMax: Math.ceil(Math.max(...data.montos) / 4000) * 4000,
                        grid: {
                            display: false
                        },
                        ticks: {
                            stepSize: 3000
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            padding: 10
                        }
                    }
                }
            },
            plugins: [ChartDataLabels] // Activar el plugin
        });
        let fechaInicio = document.getElementById('fecha_inicio').value;
        let fecha_inicio2 = new Date(fechaInicio);
    fecha_inicio2.setDate(fecha_inicio2.getDate() + 1);

        actualizarRangoFechas(fecha_inicio2);
        document.getElementById('infoTotales').innerHTML = `Importe Total: S/ ${(data.montoTotal).toLocaleString('en-US')}- Pasajeros: ${parseInt(data.totalPasajeros).toLocaleString('en-US')}`;
    });
}
    // Eventos para los botones de filtro
    document.getElementById('btn-rutas').addEventListener('click', () => {
        let fecha_inicio = document.getElementById('fecha_inicio').value;
        filtrarRuta(fecha_inicio);
    });

    document.getElementById('btn-oficina').addEventListener('click', () => {
        let fecha_inicio = document.getElementById('fecha_inicio').value;
        filtrarOficina(fecha_inicio);
    });

    document.getElementById('btn-turno').addEventListener('click', () => {
        let fecha_inicio = document.getElementById('fecha_inicio').value;
        filtrarTurno(fecha_inicio);
    });
    function actualizarRangoFechas(fecha_inicio) {
    const rangoFechas = document.getElementById('rangoFechas');

    function formatearFecha(fecha) {
        const date = new Date(fecha);
        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0'); // Se suma 1 porque los meses van de 0 a 11
        const año = date.getFullYear();
        return `${dia}/${mes}/${año}`;
    }

    const fechaInicioFormateada = formatearFecha(fecha_inicio);
    rangoFechas.textContent = `${fechaInicioFormateada}`;
    infoIngresos.style.display = 'block';
}
</script>
@endsection
