@extends('dashboard.index')
@section('title', 'Home Page')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <br><br>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm p-4">
                <h4 class="card-title text-center mb-4">Filtros de Ingresos</h4>
                <div class="position-relative mt-4">
                    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 0px; z-index: 10;">
                        <a class="btn btn-light me-1" href="{{ route('grafico.index2') }}" id="btn-general">I.Total</a>
                        <a class="btn btn-light me-1" href="{{ route('graficooficina') }}" id="btn-ruta">I.Oficina</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoruta') }}" id="btn-ruta">I.Ruta</a>
                        <a class="btn btn-light me-1" href="{{ route('indexrutapie') }}" id="btn-auto">I.Ruta Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexturno') }}" id="btn-ruta">I.Turno</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoauto') }}" id="btn-auto">I.Placa</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautopie') }}" id="btn-auto">I.Placa Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautoruta') }}" id="btn-pie">I. Placa-Ruta</a>
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
                        <label for="fecha_inicio" class="form-label">Fecha Inicio:</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin:</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                    </div>
                    <div class="col-md-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
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
        <button class="btn btn-light me-1" id="btn-semana">Semana</button>
        <button class="btn btn-light me-1" id="btn-mes">Mes</button>
        <button class="btn btn-light" id="btn-año">Año</button>
    </div>

    <div class="card shadow-sm mt-12 container-fluid">
        <div style="padding-top: 50px;width: 800px; height: 700px">
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
   
    function filtrarhoy() {
    let servicio = document.getElementById('servicio').value;

    fetch('{{ route("grafico.hoy") }}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
        servicio: servicio  // Incluir el parámetro servicio en la solicitud
    })
})
.then(response => response.json())
.then(data => {
    console.log('Datos recibidos:', data);
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
        responsive: true, // Habilitar respuesta dinámica al tamaño del contenedor
        maintainAspectRatio: false, // Permitir que el gráfico cambie su proporción al redimensionar
        indexAxis: 'y', // Orientación horizontal
        plugins: {
            title: {
                display: true, // Habilitar el título
                text: 'Importe del día', // Título inicial
                font: {
                    size: 20, // Tamaño de fuente
                    weight: 'bold' // Grosor de fuente
                },
                padding: {
                    top: 10, // Espaciado superior
                    bottom: 30 // Espaciado inferior
                }
            },
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
                    size: 12, // Opcional: Ajusta el tamaño de la fuente
                    weight: 'bold' // Opcional: Cambia el grosor de la fuente
                }
            }
        },
        scales: {
            x: {
                        beginAtZero: true,
                        min: 0,
                        max: 20000,
                        grid: {
                            display: false,
                        },
                        ticks: {
                            stepSize: 2000,
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

    document.addEventListener('DOMContentLoaded', function () {
        filtrarhoy();
    });
    // Función para enviar los datos de filtrado y actualizar la gráfica
    function filtrarDatos(fechaInicio, fechaFin) {
    // Obtener el valor del tipo de servicio seleccionado
    let servicio = document.getElementById('servicio').value;
    if (graficoIngresos) {
    graficoIngresos.destroy(); // Destruir el gráfico anterior
} 
    fetch('{{ route("grafico.filtrar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            servicio: servicio  // Incluir el parámetro servicio en la solicitud
        })
    })
    .then(response => response.json())
    .then(data => {
        // Ordenar los ingresos por fecha
        let datosOrdenados = data.ingresos.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
        let fechas = datosOrdenados.map(item => {
        let fecha = new Date(item.fecha);
        return fecha.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
        });
        let montos = datosOrdenados.map(item => item.total);
        
        graficoIngresos = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Importe total de ingresos',
                data: [],
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                tension: 0.2,
                pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                pointRadius: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Permitir que el gráfico cambie su proporción al redimensionar
            plugins: {
                title: {
                display: true, // Habilitar el título
                text: 'Importe Total', // Título inicial
                font: {
                    size: 20, // Tamaño de fuente
                    weight: 'bold' // Grosor de fuente
                },
                padding: {
                    top: 10, // Espaciado superior
                    bottom: 30 // Espaciado inferior
                }
            },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Fecha',
                        color: '#333',
                        font: {
                            size: 16
                        }
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Monto (S/.)',
                        color: '#333',
                        font: {
                            size: 16
                        }
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)'
                    }
                }
            }
        }
    });
        // Actualizar los datos del gráfico
        graficoIngresos.data.labels = fechas;
        graficoIngresos.data.datasets[0].data = montos;
        graficoIngresos.update();

        // Actualizar el monto total
        document.getElementById('montoTotal').textContent = data.montoTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
}

    // Eventos para los botones de filtro
    document.getElementById('btn-semana').addEventListener('click', () => {
        let fechaFin = new Date();
        let fechaInicio = new Date();
        fechaInicio.setDate(fechaFin.getDate() - 7);
        filtrarDatos(fechaInicio.toISOString().split('T')[0], fechaFin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-mes').addEventListener('click', () => {
        let fechaFin = new Date();
        let fechaInicio = new Date();
        fechaInicio.setMonth(fechaFin.getMonth() - 1);
        filtrarDatos(fechaInicio.toISOString().split('T')[0], fechaFin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-año').addEventListener('click', () => {
        let fechaFin = new Date();
        let fechaInicio = new Date();
        fechaInicio.setFullYear(fechaFin.getFullYear() - 1);
        filtrarDatos(fechaInicio.toISOString().split('T')[0], fechaFin.toISOString().split('T')[0]);
    });

    // Evento para el formulario de filtros de fechas personalizado
    document.getElementById('filtros-form').addEventListener('submit', function (e) {
        e.preventDefault();
        let fechaInicio = document.getElementById('fecha_inicio').value;
        let fechaFin = document.getElementById('fecha_fin').value;
        filtrarDatos(fechaInicio, fechaFin);
    });
    function cambiarTitulo(nuevoTitulo) {
    graficoIngresos.options.plugins.title.text = nuevoTitulo; // Cambiar el texto del título
    graficoIngresos.update(); // Actualizar el gráfico
}
</script>
@endsection
