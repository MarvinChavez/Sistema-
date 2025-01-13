@extends('dashboard.index')
@section('title', 'Home Page')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <br><br>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm p-4">
                <h4 class="card-title text-center mb-4">Ingresos del día</h4>
                <div class="position-relative mt-4">
                    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 0px; z-index: 10;">
                        <a class="btn btn-light me-1" href="{{ route('grafico.index2') }}" id="btn-general">I.Total</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoDia') }}" id="btn-dia">I.Dia</a>
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

            <!-- Monto Total -->
            <div class="text-center mt-4">
                <h5>Importe Total: S/ <span id="montoTotal">0.00</span></h5>
            </div>

            <!-- Gráfico -->
            <!-- Gráfico -->
<div class="position-relative mt-4">
    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
        <button class="btn btn-light me-1" id="btn-rutas">Ruta</button>
        <button class="btn btn-light me-1" id="btn-oficina">Oficina</button>
        <button class="btn btn-light" id="btn-turno">Turno</button>
    </div>

    <div class="card shadow-sm mt-12 container-fluid">
        <div style="padding-top: 50px;width: 750px; height: 700px">
            <!-- Ajusta el canvas para que sea responsivo -->
            <canvas id="graficoIngresos" style="width: 100%; height: auto;"></canvas>
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
        fecha_inicio:fecha_inicio // Incluir el parámetro servicio en la solicitud
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
            data: data.data, // Eje X: Monto
            backgroundColor: 'rgba(75, 192, 192, 0.6)', // Colores neón
            borderColor: 'rgba(75, 192, 192, 1)', // Bordes
            borderWidth: 1
        }]
    },
    options: {
        responsive: true, // Habilitar respuesta dinámica al tamturno del contenedor
        maintainAspectRatio: false, // Permitir que el gráfico cambie su proporción al redimensionar
        indexAxis: 'y', // Orientación horizontal
        plugins: {
            tooltip: {
                enabled: true // Habilitar tooltips
            },
            legend: {
                display: false // Ocultar leyenda si no es necesaria
            },
            datalabels: {
                anchor: 'end',
                align: 'right',
                formatter: (value) => {
                    const formatter = new Intl.NumberFormat('es-PE', {
                        style: 'currency',
                        currency: 'PEN', // Moneda en Soles
                        minimumFractionDigits: 0 // Sin decimales
                    });
                    return formatter.format(value); // Formatear con separador de miles y símbolo de moneda
                },
                color: '#000', // Opcional: Cambia el color de las etiquetas
                font: {
                    size: 12, // Opcional: Ajusta el tamturno de la fuente
                    weight: 'bold' // Opcional: Cambia el grosor de la fuente
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                suggestedMax: Math.ceil(Math.max(...data.data) / 4000) * 4000, // Ajustar al múltiplo de 2000 más cercano
                grid: {
                    display: false
                },
                ticks: {
                    stepSize: 3000
                }
            },
            y: {
                grid: {
                    display: false // Ocultar líneas de cuadrícula
                },
                ticks: {
                    autoSkip: false, // Mostrar todas las etiquetas
                    maxRotation: 0,  // Sin rotación para etiquetas
                    minRotation: 0,
                    padding: 10 // Separación entre etiquetas del eje Y y las barras
                }
            }
        },
        layout: {
            padding: 20 // Espaciado alrededor del gráfico
        },
        elements: {
            bar: {
                barPercentage: 0.5, // Ancho de las barras
                categoryPercentage: 0.7 // Espacio entre categorías
            }
        }
    },
    plugins: [ChartDataLabels] // Asegúrate de incluir ChartDataLabels
});



        // Actualizar el monto total
        document.getElementById('montoTotal').textContent = data.montoTotal.toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
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
            data: data.data, // Eje X: Monto
            backgroundColor: 'rgba(75, 192, 192, 0.6)', // Colores neón
            borderColor: 'rgba(75, 192, 192, 1)', // Bordes
            borderWidth: 1
        }]
    },
    options: {
        responsive: true, // Habilitar respuesta dinámica al tamturno del contenedor
        maintainAspectRatio: false, // Permitir que el gráfico cambie su proporción al redimensionar
        indexAxis: 'y', // Orientación horizontal
        plugins: {
            tooltip: {
                enabled: true // Habilitar tooltips
            },
            legend: {
                display: false // Ocultar leyenda si no es necesaria
            },
            datalabels: {
                anchor: 'end',
                align: 'right',
                formatter: (value) => {
                    const formatter = new Intl.NumberFormat('es-PE', {
                        style: 'currency',
                        currency: 'PEN', // Moneda en Soles
                        minimumFractionDigits: 0 // Sin decimales
                    });
                    return formatter.format(value); // Formatear con separador de miles y símbolo de moneda
                },
                color: '#000', // Opcional: Cambia el color de las etiquetas
                font: {
                    size: 12, // Opcional: Ajusta el tamturno de la fuente
                    weight: 'bold' // Opcional: Cambia el grosor de la fuente
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                suggestedMax: Math.ceil(Math.max(...data.data) / 4000) * 4000, // Ajustar al múltiplo de 2000 más cercano
                grid: {
                    display: false
                },
                ticks: {
                    stepSize: 3000
                }
            },
            y: {
                grid: {
                    display: false // Ocultar líneas de cuadrícula
                },
                ticks: {
                    autoSkip: false, // Mostrar todas las etiquetas
                    maxRotation: 0,  // Sin rotación para etiquetas
                    minRotation: 0,
                    padding: 10 // Separación entre etiquetas del eje Y y las barras
                }
            }
        },
        layout: {
            padding: 20 // Espaciado alrededor del gráfico
        },
        elements: {
            bar: {
                barPercentage: 0.5, // Ancho de las barras
                categoryPercentage: 0.7 // Espacio entre categorías
            }
        }
    },
    plugins: [ChartDataLabels] // Asegúrate de incluir ChartDataLabels
});



        // Actualizar el monto total
        document.getElementById('montoTotal').textContent = data.montoTotal.toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
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
            data: data.data, // Eje X: Monto
            backgroundColor: 'rgba(75, 192, 192, 0.6)', // Colores neón
            borderColor: 'rgba(75, 192, 192, 1)', // Bordes
            borderWidth: 1
        }]
    },
    options: {
        responsive: true, // Habilitar respuesta dinámica al tamturno del contenedor
        maintainAspectRatio: false, // Permitir que el gráfico cambie su proporción al redimensionar
        indexAxis: 'y', // Orientación horizontal
        plugins: {
            tooltip: {
                enabled: true // Habilitar tooltips
            },
            legend: {
                display: false // Ocultar leyenda si no es necesaria
            },
            datalabels: {
                anchor: 'end',
                align: 'right',
                formatter: (value) => {
                    const formatter = new Intl.NumberFormat('es-PE', {
                        style: 'currency',
                        currency: 'PEN', // Moneda en Soles
                        minimumFractionDigits: 0 // Sin decimales
                    });
                    return formatter.format(value); // Formatear con separador de miles y símbolo de moneda
                },
                color: '#000', // Opcional: Cambia el color de las etiquetas
                font: {
                    size: 12, // Opcional: Ajusta el tamturno de la fuente
                    weight: 'bold' // Opcional: Cambia el grosor de la fuente
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                suggestedMax: Math.ceil(Math.max(...data.data) / 4000) * 4000, // Ajustar al múltiplo de 2000 más cercano
                grid: {
                    display: false
                },
                ticks: {
                    stepSize: 3000
                }
            },
            y: {
                grid: {
                    display: false // Ocultar líneas de cuadrícula
                },
                ticks: {
                    autoSkip: false, // Mostrar todas las etiquetas
                    maxRotation: 0,  // Sin rotación para etiquetas
                    minRotation: 0,
                    padding: 10 // Separación entre etiquetas del eje Y y las barras
                }
            }
        },
        layout: {
            padding: 20 // Espaciado alrededor del gráfico
        },
        elements: {
            bar: {
                barPercentage: 0.5, // Ancho de las barras
                categoryPercentage: 0.7 // Espacio entre categorías
            }
        }
    },
    plugins: [ChartDataLabels] // Asegúrate de incluir ChartDataLabels
});



        // Actualizar el monto total
        document.getElementById('montoTotal').textContent = data.montoTotal.toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
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

</script>
@endsection
