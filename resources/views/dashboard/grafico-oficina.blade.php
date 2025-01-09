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
                        <a class="btn btn-light me-1" href="{{ route('grafico.index2') }}" id="btn-general">I.Total</a>
                        <a class="btn btn-light me-1" href="{{ route('graficooficina') }}" id="btn-ciudad">I.Oficina</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoruta') }}" id="btn-ciudad">I.Ruta</a>
                        <a class="btn btn-light me-1" href="{{ route('indexrutapie') }}" id="btn-auto">I.Ruta Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexturno') }}" id="btn-ciudad">I.Turno</a>
                        <a class="btn btn-light me-1" href="{{ route('graficoauto') }}" id="btn-auto">I.Placa</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautopie') }}" id="btn-auto">I.Placa Pie</a>
                        <a class="btn btn-light me-1" href="{{ route('indexautoruta') }}" id="btn-pie">I. Placa-Ruta</a>
                    </div>
                </div>
                <h4 class="card-title text-center mb-4">Filtros de Ingresos por Oficina</h4>
                <form id="filtros-ciudad-form" class="row g-3">
                        <div class="col-md-6" style="max-height: 200px; overflow-y: auto;">
                            <label class="form-label">Oficina:</label>
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
                            @php
                            $ciudadesProcesadas = []; // Array para rastrear las ciudades ya mostradas
                        @endphp
                        
                        @foreach($rutas as $ruta)
                            @php
                                $ciudad_inicial = strtoupper(trim($ruta->ciudad_inicial));
                                $ciudad_inicial_abreviada = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
                            @endphp
                        
                            @if(!in_array($ciudad_inicial, $ciudadesProcesadas))
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ciudades[]" value="{{ $ciudad_inicial }}" id="ruta-{{ $ruta->id }}">
                                    <label class="form-check-label" for="ruta-{{ $ruta->id }}">
                                        {{ $ciudad_inicial_abreviada }}
                                    </label>
                                </div>
                                @php
                                    $ciudadesProcesadas[] = $ciudad_inicial; // Agregar la ciudad inicial al array
                                @endphp
                            @endif
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
            <div class="position-relative mt-4">
                <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
                    <button class="btn btn-light me-1" id="btn-semana">Semana</button>
                    <button class="btn btn-light me-1" id="btn-mes">Mes</button>
                    <button class="btn btn-light" id="btn-año">Año</button>
                </div>

                <div class="card shadow-sm mt-4" id="grafico-container" style="background-color: #000000">
                    <div class="card-body" style="padding-top: 50px;">
                        <canvas id="graficoRuta" style="height: 600px; width: 100%;"></canvas>
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
function fetchData(ciudadesSeleccionadas, fecha_inicio, fecha_fin) {
    let servicio = document.getElementById('servicio').value;

    fetch('{{ route("filtraroficina") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            ciudades: ciudadesSeleccionadas,
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
        let todasLasFechas = [];
        
        // Extraer todas las fechas de todos los autos
        data.ciudades.forEach(ciudad => {
            todasLasFechas = [...todasLasFechas, ...ciudad.fechas];
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
        plugins: {
            tooltip: {
                enabled: true,
                intersect: true,
                mode: 'nearest'
            }
        },
        hover: {
            mode: 'nearest',
            intersect: true
        },
        scales: {
                x: {
                    type: 'time', // Configuración para tiempo
                    time: {
                        unit: 'day' // Unidad de tiempo: días
                    },
                    ticks: {
                        maxTicksLimit: 8 // Limitar el número máximo de etiquetas visibles
                    }
                },
                y: {
                    beginAtZero: true, // Comenzar desde 0
                    min: 0, // Monto mínimo
                    max: 30000, // Monto máximo
                    ticks: {
                        stepSize: 2000 // Incremento entre ticks del eje Y
                    }
                }
            }
    },
    plugins: ['crosshair'] // Incluye el ID del plugin aquí si no es global
});
        graficoRuta.data.labels = todasLasFechas;
        graficoRuta.data.datasets = data.ciudades.map((ciudad, index) => {
            const montos = todasLasFechas.map(fecha => {
                const indexFecha = ciudad.fechas.indexOf(fecha);
                return indexFecha >= 0 ? ciudad.montos[indexFecha] : NaN; // Usar NaN para continuar la línea
            });
            return {
                label: `${ciudad.ciudad_inicial}`,
                data: montos,
                borderColor: getRandomColor(index),
                tension: 0.2,
                pointRadius: 2.5,
                pointHoverRadius: 6,
                fill: false, // No llenar el área bajo la línea
                spanGaps: true // Configurar para que la línea no se corte
            };
        });
        graficoRuta.update(); // Actualizar el gráfico después de cambiar los datos
        document.getElementById('btn-mostrar-promedios').addEventListener('click', () => {
    mostrarMontosPromedio(data.ciudades);
});

document.getElementById('btn-mostrar-montos').addEventListener('click', () => {
    mostrarMontos(data.ciudades);
});  
    })
    .catch(error => {
        console.error('Error al obtener datos:', error);
    });
      

}

document.getElementById('btn-limpiar').addEventListener('click', function () {
    // Mostrar de nuevo el contenedor de filtros y el botón "Filtrar"
    document.getElementById('filtros-container').classList.remove('d-none');
    document.querySelector('button[type="submit"]').classList.remove('d-none');
    // Limpiar el gráfico y los montos promedio
    graficoRuta.data.labels = [];
    graficoRuta.data.datasets = [];
    graficoRuta.update();
    document.getElementById('montos').innerHTML = '';
});

document.getElementById('filtros-ciudad-form').addEventListener('submit', function (event) {
    event.preventDefault();

    let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
    let fecha_inicio = document.getElementById('fecha_inicio').value;
    let fecha_fin = document.getElementById('fecha_fin').value;

    // Ocultar filtros y botón "Filtrar"
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    // Llamada a la función para filtrar datos
    fetchData(ciudadesSeleccionadas, fecha_inicio, fecha_fin);
});
    document.getElementById('btn-semana').addEventListener('click', function () {
        let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setDate(fecha_inicio.getDate() - 7);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        // Ocultar filtros y botón "Filtrar"
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
        fetchData(ciudadesSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-mes').addEventListener('click', function () {
        let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setMonth(fecha_inicio.getMonth() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        // Ocultar filtros y botón "Filtrar"
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
        fetchData(ciudadesSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-año').addEventListener('click', function () {
        let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setFullYear(fecha_inicio.getFullYear() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        // Ocultar filtros y botón "Filtrar"
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
        fetchData(ciudadesSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
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
// Mostrar montos promedio por ciudad
function mostrarMontosPromedio(ciudades) {
    const contenedor = document.getElementById('montos');
    contenedor.innerHTML = '';
    let index = 0;

    ciudades.forEach(ciudad => {
        const { ciudad_inicial, promedio } = ciudad;

        const card = document.createElement('div');
        card.className = 'col-auto'; // Ajustar tamaño dinámico para que se agrupen mejor
        card.style.padding = '2px'; // Reducir padding entre los cards
        card.innerHTML = `
            <div class="card card-promedio" style="background-color: ${getRandomColor(index)};">
                <p style="font-size: 17px; color: black; font-family: Georgia, serif;">
                    <span style="font-size: 12px;">S/.</span> ${Math.round(promedio).toLocaleString('en-US')}
                </p>
            </div>
        `;
        contenedor.appendChild(card);

        index++;
    });
}

// Mostrar último registro (fecha y monto) por ciudad
function mostrarMontos(ciudades) {
    const contenedor = document.getElementById('montos');
    contenedor.innerHTML = '';
    let index = 0;

    ciudades.forEach(ciudad => {
        const { ciudad_inicial, ultimo_registro } = ciudad;

        const fecha = ultimo_registro?.fecha || 'N/A';
        const monto = ultimo_registro?.monto;

        const card = document.createElement('div');
        card.className = 'col-auto'; // Ajustar tamaño dinámico para que se agrupen mejor
        card.style.padding = '2px'; // Reducir padding entre los cards
        card.innerHTML = `
            <div class="card card-promedio2" style="background-color: ${getRandomColor(index)};">
                <p style="font-size: 12px; color: black; font-family: Georgia, serif;">${fecha}</p>
                <p style="font-size: 15px; color: black; font-family: Georgia, serif;">
                    <span style="font-size: 12px;">S/.</span> ${Math.round(monto).toLocaleString('en-US')}
                </p>
            </div>`;
        contenedor.appendChild(card);

        index++;
    });
}


</script>

@endsection  