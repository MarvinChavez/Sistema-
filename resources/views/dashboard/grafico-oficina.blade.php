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
                <h4 class="card-title text-center mb-4">INGRESOS POR OFICINA</h4>
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
                            $ciudadesProcesadas = [];
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
                                    $ciudadesProcesadas[] = $ciudad_inicial; 
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
            <div class="text-center mt-4" id="infoIngresos" style="display: none;">
                <h2>INGRESOS POR OFICINA</h2>
                <h5 id="infoTotales">Importe Total: S/ 0 <br> P(): 0</h5>
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
                <div id="contenedor-botones" class="col-auto d-flex flex-column align-items-start">
                    <button class="btn btn-primary btn-sm mb-2" id="btn-mostrar-promedios">Promedio</button>
                    <button class="btn btn-secondary btn-sm" id="btn-mostrar-montos">Montos Finales</button>
                </div>
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
        if (!data.ciudades || data.ciudades.length === 0) {
    graficoRuta.data.labels = []; 
    graficoRuta.data.datasets = [{ 
        label: 'No hay datos disponibles',
        data: [],
        borderColor: 'rgba(0,0,0,0)',  
        backgroundColor: 'rgba(0,0,0,0)', 
        fill: false
    }];
    graficoRuta.update();
    return;
}        let todasLasFechas = [];
    document.getElementById('infoTotales').innerHTML = `Importe Total: S/ ${(data.total_general).toLocaleString('en-US')} - Pasajeros: ${parseInt(data.total_pasajeros).toLocaleString('en-US')})`;

        data.ciudades.forEach(ciudad => {
            todasLasFechas = [...todasLasFechas, ...ciudad.fechas];
        });
        todasLasFechas = [...new Set(todasLasFechas)].sort();
        if (graficoRuta) {
            graficoRuta.destroy();
        }
       
        graficoRuta = new Chart(ctx, {
    type: 'line',
    data: {
        labels: todasLasFechas,
        datasets: data.ciudades.map((ciudad, index) => {
            const montos = todasLasFechas.map(fecha => {
                const indexFecha = ciudad.fechas.indexOf(fecha);
                return indexFecha >= 0 ? ciudad.montos[indexFecha] : NaN; // Usa NaN para continuar la línea
            });
            const pasajerosData = todasLasFechas.map(fecha => {
                const indexFecha = ciudad.fechas.indexOf(fecha);
                const pasajeros = indexFecha >= 0 ? ciudad.pasajeros[indexFecha] : 0;
                return indexFecha >= 0 ? ciudad.pasajeros[indexFecha] : 0;

            });
            return {
                label: `${ciudad.ciudad_inicial} (TOTAL: S/. ${ciudad.montoTotal} - P= ${ciudad.total_pasajeros})`,
                data: montos,
                borderColor: getRandomColor(index),
                backgroundColor: getRandomColor(index),
                tension: 0.2,
                pointRadius: 2.5,
                pointHoverRadius: 6,
                fill: false,
                spanGaps: true,
                pasajerosData: pasajerosData
            };
        })
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: '#333',
                    font: { size: 14 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label ='Monto';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(context.parsed.y);
                        }
                        let pasajeros = context.dataset.pasajerosData[context.dataIndex] ?? 0;
                        
                        return `${label} - Pasajeros: ${pasajeros}`;
                    },
                    title: function(context) {
                        const fecha = context[0].parsed.x;
                        return new Date(fecha).toLocaleDateString('es-PE', { day: 'numeric', month: 'long' });
                    }
                }
            }
        },
        scales: {
            x: {
                type: 'time',
                time: { unit: 'day' },
                title: {
                    display: true,
                    text: 'Fecha',
                    color: '#333',
                    font: { size: 16 }
                },
                grid: { display: false },
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 7,
                    maxRotation: 0,
                    minRotation: 0
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Importe (S/.)',
                    color: '#333',
                    font: { size: 16 }
                },
                grid: { color: 'rgba(200, 200, 200, 0.1)' }
            }
        }
    }
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
    document.getElementById('filtros-container').classList.remove('d-none');
    document.querySelector('button[type="submit"]').classList.remove('d-none');
    infoIngresos.style.display = 'none'; 
    graficoRuta.data.labels = [];
    graficoRuta.data.datasets = [];
    graficoRuta.update();
    document.getElementById('montos').innerHTML = '';
    document.getElementById('montoTotal').textContent = 0;
});

    document.getElementById('filtros-ciudad-form').addEventListener('submit', function (event) {
    event.preventDefault();

    let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
    let fecha_inicio = document.getElementById('fecha_inicio').value;
    let fecha_fin = document.getElementById('fecha_fin').value;
    let fecha_inicio2 = new Date(fecha_inicio);
    let fecha_fin2 = new Date(fecha_fin);
    fecha_inicio2.setDate(fecha_inicio2.getDate() + 1);
    fecha_fin2.setDate(fecha_fin2.getDate() + 1);
    document.getElementById('filtros-container').classList.add('d-none');
    document.querySelector('button[type="submit"]').classList.add('d-none');
    actualizarRangoFechas(fecha_inicio2, fecha_fin2)
    fetchData(ciudadesSeleccionadas, fecha_inicio, fecha_fin);
});
    document.getElementById('btn-semana').addEventListener('click', function () {
        let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setDate(fecha_inicio.getDate() - 7);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        actualizarRangoFechas(fecha_inicio, fecha_fin);

    document.getElementById('filtros-container').classList.add('d-none'); 
    document.querySelector('button[type="submit"]').classList.add('d-none');
    document.getElementById('montos').innerHTML = '';

        fetchData(ciudadesSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-mes').addEventListener('click', function () {
        let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setMonth(fecha_inicio.getMonth() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        actualizarRangoFechas(fecha_inicio, fecha_fin);
        document.getElementById('montos').innerHTML = '';

    document.getElementById('filtros-container').classList.add('d-none');
    document.querySelector('button[type="submit"]').classList.add('d-none');
    actualizarRangoFechas(fecha_inicio, fecha_fin);
        fetchData(ciudadesSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-año').addEventListener('click', function () {
        let ciudadesSeleccionadas = Array.from(document.querySelectorAll('input[name="ciudades[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setFullYear(fecha_inicio.getFullYear() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        // Ocultar filtros y botón "Filtrar"
        document.getElementById('montos').innerHTML = '';

    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
        fetchData(ciudadesSeleccionadas, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    function getRandomColor(index) {
    const neonColors = [
        "#39FF14", 
        "#FF073A", 
        "#FFFF00", 
        "#00FFFF", 
        "#FF00FF", 
        "#FF1493", 
        "#00FF00", 
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
function actualizarRangoFechas(fecha_inicio, fecha_fin) {
    const rangoFechas = document.getElementById('rangoFechas');

    function formatearFecha(fecha) {
        const date = new Date(fecha);
        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0'); 
        const año = date.getFullYear();
        return `${dia}/${mes}/${año}`;
    }

    const fechaInicioFormateada = formatearFecha(fecha_inicio);
    const fechaFinFormateada = formatearFecha(fecha_fin);

    rangoFechas.textContent = `${fechaInicioFormateada} - ${fechaFinFormateada}`;
    infoIngresos.style.display = 'block';
}
</script>

@endsection  