@extends('dashboard.index')
@section('title', 'Gráfico por Ruta')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <br>
    <br>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm p-4" id="filtros-container">
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
                <h4 class="card-title text-center mb-4">Ingresos por Ruta</h4>
                <form id="filtros-ruta-form" class="row g-3">
                        <div class="col-md-6" style="max-height: 200px; overflow-y: auto;">
                            <label class="form-label">Ruta:</label>
                            @php
                            $abreviaciones = [
                                'TRUJILLO' => 'TRUJ',
                                'CAJAMARCA' => 'CAXA',
                                'JAEN' => 'JAEN',
                                'CHICLAYO' => 'CHIC',
                                'PIURA' => 'PIUR',
                                'LA VICTORIA' => 'LIMA',
                                'MORALES' => 'TARA',
                            ];
                            @endphp
                            @foreach($rutas as $ruta)
                            @php
                            $ciudad_inicial = strtoupper(trim($ruta->ciudad_inicial));
                            $ciudad_final = strtoupper(trim($ruta->ciudad_final));
                            $ciudad_inicial_abreviada = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
                            $ciudad_final_abreviada = $abreviaciones[$ciudad_final] ?? $ciudad_final;
                            @endphp
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rutas[]" value="{{ $ruta->id }}" id="ruta-{{ $ruta->id }}">
                                <label class="form-check-label" for="ruta-{{ $ruta->id }}">
                                    {{ $ciudad_inicial_abreviada }} - {{ $ciudad_final_abreviada }}
                                </label>
                            </div>
                            @endforeach
                        </div>
    
                        <div class="col-md-6">
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
            <div class="col-md-12 text-center mt-3">
                <button type="button" class="btn btn-secondary" id="btn-limpiar">Atrás</button>
            </div>
            <div class="text-center mt-4" id="infoIngresos" style="display: none;"> <!-- Ocultado por defecto -->
                <h2>INGRESOS POR RUTA</h2>
                <h5>Importe Total: S/ <span id="montoTotal">0.00</span></h5>
                <h5 id="rangoFechas">Rango de Fechas: - </h5>
            </div>
            <div class="position-relative mt-4">
                <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
                    <button class="btn btn-light me-1" id="btn-semana">Semana</button>
                    <button class="btn btn-light me-1" id="btn-mes">Mes</button>
                    <button class="btn btn-light" id="btn-año">Año</button>
                </div>

                <div class="card shadow-sm mt-12 container-fluid"  id="grafico-container">
                    <div style="padding-top: 50px;width: auto; height: 700px">
                        <canvas id="graficoRuta" style="width: 100%; height: auto;"></canvas>
                    </div>
                </div>       
            </div>
            <div class="row mt-4">
                <!-- Contenedor de los botones (ocupando solo el espacio necesario) -->
                <div id="contenedor-botones" class="col-auto d-flex flex-column align-items-start">
                    <button class="btn btn-primary btn-sm mb-2" id="btn-mostrar-promedios">Promedio</button>
                    <button class="btn btn-secondary btn-sm" id="btn-mostrar-montos">Montos Finales</button>
                </div>
            
                <!-- Contenedor para los cards -->
                <div id="montos" class="col d-flex flex-wrap"></div>
            </div>
            
            
            </div>
    </div>
</div>


<style>
    #contenedor-botones {
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Alinear botones a la izquierda */
    margin-right: 10px; /* Separación entre botones y cards */
}

#montos .card {
    margin: 5px; /* Espaciado entre los cards */
    transition: all 0.3s ease-in-out; /* Animación suave al actualizar */
}

    .btn {
        white-space: nowrap; /* Evita que el texto del botón se corte */
    }
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

    .card-promedio {
        margin: 1px;
        padding: 1px;
        color: white;
        border-radius: 2px;
        text-align: center;
        width: 80px;
        height: 50px;
    }
    .card-promedio2 {
    margin: 0.5px;
    padding: 0.5px;
    color: white;
    border-radius: 5px;
    text-align: center;
    width: 80px;
    height: 65px;
}

</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0"></script>
<script>
    let ctx = document.getElementById('graficoRuta').getContext('2d');
    let graficoRuta;
// Función para cargar datos al gráfico
function fetchData(rutasSeleccionadas, fecha_inicio, fecha_fin) {
    let servicio = document.getElementById('servicio').value;

    fetch('{{ route("filtrarruta") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            rutas: rutasSeleccionadas,
            fecha_inicio: fecha_inicio,
            fecha_fin: fecha_fin,
            servicio: servicio
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        if (!data.rutas || data.rutas.length === 0) {
            graficoRuta.data.labels = [];  // Vaciar las etiquetas
            graficoRuta.data.datasets = [{  // Vaciar los datasets
        label: 'No hay datos disponibles',
        data: [],
        borderColor: 'rgba(0,0,0,0)',  // Hacer la línea invisible
        backgroundColor: 'rgba(0,0,0,0)', // Sin fondo
        fill: false
    }];
    graficoRuta.update();  // Actualizar el gráfico para reflejar los cambios
    return;  // Terminar la ejecución sin continuar con más lógica
}
        document.getElementById('montoTotal').textContent = data.total_general.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        let todasLasFechas = [];
        
        // Extraer todas las fechas de todos los autos
        data.rutas.forEach(ruta => {
            todasLasFechas = [...todasLasFechas, ...ruta.fechas];
        });

        // Eliminar duplicados y ordenar las fechas
        todasLasFechas = [...new Set(todasLasFechas)].sort();

        // Establecer las fechas en las etiquetas del gráfico
        // Destruir el gráfico existente si ya hay uno
        if (graficoRuta) {
            graficoRuta.destroy();
        }
        graficoRuta = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: []
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Permitir que el gráfico cambie su proporción al redimensionar
        plugins: {
            legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#333',
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                callbacks: {
                    label: function(context) {
                        // Formatear el valor del eje Y
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(context.parsed.y);
                        }
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
                        text: 'Importe (S/.)',
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
    },
});
        graficoRuta.data.labels = todasLasFechas;
        graficoRuta.data.datasets = data.rutas.map((ruta, index) => {
            const montos = todasLasFechas.map(fecha => {
                const indexFecha = ruta.fechas.indexOf(fecha);
                return indexFecha >= 0 ? ruta.montos[indexFecha] : NaN; // Usar NaN para continuar la línea
            });
            return {
                label: `${ruta.nombre} ( TOTAL: S/. ${ruta.total})`,
                data: montos,
                borderColor: getRandomColor(index),
                backgroundColor:getRandomColor(index),
                tension: 0.2,
                pointRadius: 2.5,
                pointHoverRadius: 6,
                fill: false, // No llenar el área bajo la línea
                spanGaps: true // Configurar para que la línea no se corte
            };
        });
        graficoRuta.update(); // Actualizar el gráfico después de cambiar los datos
        document.getElementById('btn-mostrar-promedios').addEventListener('click', () => {
    mostrarMontosPromedio(data.rutas);
});

document.getElementById('btn-mostrar-montos').addEventListener('click', () => {
    mostrarMontos(data.rutas);
});  
    })
    .catch(error => {
        console.error('Error al obtener datos:', error);
    });
      

}

document.getElementById('btn-limpiar').addEventListener('click', function () {
    document.getElementById('filtros-container').classList.remove('d-none');
    document.querySelector('button[type="submit"]').classList.remove('d-none');
    graficoRuta.data.labels = [];
    graficoRuta.data.datasets = [];
    graficoRuta.update();
    infoIngresos.style.display = 'none'; // Oculta el div    

    document.getElementById('montos').innerHTML = '';
    document.getElementById('montoTotal').textContent = 0;

});

document.getElementById('filtros-ruta-form').addEventListener('submit', function (event) {
    event.preventDefault();

    let rutasSeleccionadas = Array.from(document.querySelectorAll('input[name="rutas[]"]:checked')).map(checkbox => checkbox.value);
    let fecha_inicio = document.getElementById('fecha_inicio').value;
    let fecha_fin = document.getElementById('fecha_fin').value;
    let fecha_inicio2 = new Date(fecha_inicio);
    let fecha_fin2 = new Date(fecha_fin);

    // Sumamos un día a las fechas
    fecha_inicio2.setDate(fecha_inicio2.getDate() + 1);
    fecha_fin2.setDate(fecha_fin2.getDate() + 1);
    // Ocultar filtros y botón "Filtrar"
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    // Llamada a la función para filtrar datos
    actualizarRangoFechas(fecha_inicio2, fecha_fin2)
    fetchData(rutasSeleccionadas, fecha_inicio, fecha_fin);
});
    document.getElementById('btn-semana').addEventListener('click', function () {
        let rutasSeleccionadas = Array.from(document.querySelectorAll('input[name="rutas[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setDate(fecha_inicio.getDate() - 7);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        // Ocultar filtros y botón "Filtrar"
        actualizarRangoFechas(fecha_inicio, fecha_fin);

    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    document.getElementById('montos').innerHTML = '';

        fetchData(rutasSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-mes').addEventListener('click', function () {
        let rutasSeleccionadas = Array.from(document.querySelectorAll('input[name="rutas[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setMonth(fecha_inicio.getMonth() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        // Ocultar filtros y botón "Filtrar"
        actualizarRangoFechas(fecha_inicio, fecha_fin);

    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    document.getElementById('montos').innerHTML = '';

        fetchData(rutasSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-año').addEventListener('click', function () {
        let rutasSeleccionadas = Array.from(document.querySelectorAll('input[name="rutas[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setFullYear(fecha_inicio.getFullYear() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        actualizarRangoFechas(fecha_inicio, fecha_fin);
        // Ocultar filtros y botón "Filtrar"
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    document.getElementById('montos').innerHTML = '';

        fetchData(rutasSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    function getRandomColor(index) {
    const neonColors = [
        "#39FF14", // Neon Green
        "#FF073A", // Neon Red
        "#FFFF00", // Neon Yellow
        "#00FFFF", // Neon Cyan
        "#FF00FF", // Neon Magenta
        "#FF1493", // Neon Deep Pink
        "#00FF00", // Neon Green (Otra variación)
        "#FF6347", // Neon Tomato
        "#FF4500", // Neon Orange Red
        "#32CD32", // Neon Lime Green
        "#8A2BE2", // Neon Blue Violet
        "#00CED1", // Neon Dark Turquoise
        "#FF8C00", // Neon Dark Orange
        "#FF00FF", // Neon Fuchsia
        "#FF6347", // Neon Coral
        "#B22222", // Neon Firebrick
        "#C71585", // Neon Medium Violet Red
        "#7FFF00", // Neon Chartreuse
        "#FF1493", // Neon Deep Pink
        "#9B30FF"  // Neon Purple
    ];

    return neonColors[index % neonColors.length]; // Ciclo a través de los colores
}
// Mostrar montos promedio por ruta
function mostrarMontosPromedio(rutas) {
    const contenedor = document.getElementById('montos');
    contenedor.innerHTML = '';
    let index = 0;

    rutas.forEach(ruta => {
        const { nombre, monto_promedio } = ruta;

        const card = document.createElement('div');
        card.className = 'col-auto'; // Ajustar tamaño dinámico para que se agrupen mejor
        card.style.padding = '2px'; // Reducir padding entre los cards
        card.innerHTML = `
            <div class="card card-promedio" style="background-color: ${getRandomColor(index)};">
                <p style="font-size: 16px; color: black; font-family: Georgia, serif;">
                    <span style="font-size: 12px;">S/.</span> ${Math.round(monto_promedio).toLocaleString('en-US')}
                </p>
            </div>
        `;
        contenedor.appendChild(card);

        index++;
    });
}

// Mostrar último registro (fecha y monto) por ruta
function mostrarMontos(rutas) {
    const contenedor = document.getElementById('montos');
    contenedor.innerHTML = '';
    let index = 0;

    rutas.forEach(ruta => {
        const { nombre, ultimo_registro } = ruta;

        const fecha = ultimo_registro?.fecha || 'N/A';
        const monto = ultimo_registro?.monto;

        const card = document.createElement('div');
        card.className = 'col-auto'; // Ajustar tamaño dinámico para que se agrupen mejor
        card.style.padding = '2px'; // Reducir padding entre los cards
        card.innerHTML = `
            <div class="card card-promedio2" style="background-color: ${getRandomColor(index)};">
                <p style="font-size: 13px; color: black; font-family: Georgia, serif;">${fecha}</p>
                <p style="font-size: 15px; color: black; font-family: Georgia, serif;">
                    <span style="font-size: 12px;">S/.</span> ${Math.round(monto).toLocaleString('en-US')}
                </p>
            </div>`;
        contenedor.appendChild(card);

        index++;
    });
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