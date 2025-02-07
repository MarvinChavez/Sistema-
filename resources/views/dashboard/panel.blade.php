@extends('dashboard.index')
@section('title', 'Home Page')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <br><br>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm p-4">
                <h4 class="card-title text-center mb-4">Ingresos totales</h4>
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
            <div class="text-center mt-4" id="infoIngresos" style="display: none;"> <!-- Ocultado por defecto -->
                <h2>INGRESOS TOTALES</h2>
                <h5 id="infoTotales">Importe Total: S/ 0 <br> P(): 0</h5>
                <h5 id="rangoFechas">Rango de Fechas: - </h5>
            </div>

            <!-- Gráfico -->
            <!-- Gráfico -->
<div class="position-relative mt-4">
    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
        <button class="btn btn-light me-1" id="btn-semana">Semana</button>
        <button class="btn btn-light me-1" id="btn-mes">Mes</button>
        <button class="btn btn-light" id="btn-año">Año</button>
    </div>

    <div class="card shadow-sm mt-12 container-fluid"  id="grafico-container">
        <div style="padding-top: 50px;width: auto; height: 700px">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0"></script>
<script>
    let ctx = document.getElementById('graficoIngresos').getContext('2d');
    let graficoIngresos;
    // Función para enviar los datos de filtrado y actualizar la gráfica
    function filtrarDatos(fechaInicio, fechaFin) {
    // Obtener el valor del tipo de servicio seleccionado
    let servicio = document.getElementById('servicio').value;
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
    console.log('Datos recibidos:', data);

    // Extraemos las fechas y los montos de los ingresos
    const fechas = data.ingresos.map(item => {
        const fechaObj = new Date(item.fecha); // Convertir a objeto Date
        fechaObj.setDate(fechaObj.getDate() + 1); // Sumar un día
        return fechaObj;
    });

    const montosFormateados = data.ingresos.map(item => item.monto);
    const pasajeros = data.ingresos.map(item => parseInt(item.pasajeros));

    // Actualizamos el gráfico
    if (graficoIngresos) {
        graficoIngresos.destroy();  // Eliminamos el gráfico anterior
    }
    
    graficoIngresos = new Chart(ctx, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                data: montosFormateados,
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
                legend: {
                    display: false // Ocultar la leyenda
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            // Formatear el valor del eje Y (monto)
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(context.parsed.y);
                            }

                            // Obtener el índice de la fecha en los ingresos
                            const index = context.dataIndex;
                            const pasajerosCount = pasajeros[index]; // Obtener el número de pasajeros

                            // Agregar los pasajeros al tooltip
                            label += ` | Pasajeros: ${pasajerosCount}`;
                            return label;
                        },
                        title: function(context) {
                            // Formatear la fecha para mostrar solo día y mes
                            const fecha = context[0].parsed.x;
                            return new Date(fecha).toLocaleDateString('es-PE', { day: 'numeric', month: 'long' });
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time', // Configuración para tiempo
                    time: {
                        unit: 'day' // Unidad de tiempo: días
                    },
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
                    },
                    ticks: {
                        autoSkip: true, // Activar el salto automático de etiquetas
                        maxTicksLimit: 7, // Limitar a 7 etiquetas como máximo
                        maxRotation: 0, // Sin rotación para las etiquetas
                        minRotation: 0 // Sin rotación para las etiquetas
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

    // Actualizar el monto total
    document.getElementById('infoTotales').innerHTML = `Importe Total: S/ ${(data.montoTotal).toLocaleString('en-US')}
    P N(${parseInt(data.totalPasajeros).toLocaleString('en-US')})`;
});

}

    // Eventos para los botones de filtro
    document.getElementById('btn-semana').addEventListener('click', () => {
        let fechaFin = new Date();
        let fechaInicio = new Date();
        fechaInicio.setDate(fechaFin.getDate() - 7);
        actualizarRangoFechas(fechaInicio, fechaFin);
        filtrarDatos(fechaInicio.toISOString().split('T')[0], fechaFin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-mes').addEventListener('click', () => {
        let fechaFin = new Date();
        let fechaInicio = new Date();
        fechaInicio.setMonth(fechaFin.getMonth() - 1);
        actualizarRangoFechas(fechaInicio, fechaFin);
        filtrarDatos(fechaInicio.toISOString().split('T')[0], fechaFin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-año').addEventListener('click', () => {
        let fechaFin = new Date();
        let fechaInicio = new Date();
        fechaInicio.setFullYear(fechaFin.getFullYear() - 1);
        actualizarRangoFechas(fechaInicio, fechaFin);
        filtrarDatos(fechaInicio.toISOString().split('T')[0], fechaFin.toISOString().split('T')[0]);
    });

    // Evento para el formulario de filtros de fechas personalizado
    document.getElementById('filtros-form').addEventListener('submit', function (e) {
        e.preventDefault();
        let fechaInicio = document.getElementById('fecha_inicio').value;
        let fechaFin = document.getElementById('fecha_fin').value;
        let fecha_inicio2 = new Date(fechaInicio);
    let fecha_fin2 = new Date(fechaFin);

    // Sumamos un día a las fechas
    fecha_inicio2.setDate(fecha_inicio2.getDate() + 1);
    fecha_fin2.setDate(fecha_fin2.getDate() + 1);
        filtrarDatos(fechaInicio, fechaFin);
        actualizarRangoFechas(fecha_inicio2, fecha_fin2)

    });
    function cambiarTitulo(nuevoTitulo) {
    graficoIngresos.options.plugins.title.text = nuevoTitulo; // Cambiar el texto del título
    graficoIngresos.update(); // Actualizar el gráfico
}
function actualizarRangoFechas(fecha_inicio, fecha_fin) {
    const rangoFechas = document.getElementById('rangoFechas');
    const fechaInicioFormateada = new Date(fecha_inicio).toLocaleDateString('es-PE');
    const fechaFinFormateada = new Date(fecha_fin).toLocaleDateString('es-PE');
    rangoFechas.textContent = `${fechaInicioFormateada} - ${fechaFinFormateada}`;
    infoIngresos.style.display = 'block';

}

</script>

@endsection
