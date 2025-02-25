@extends('dashboard.index')

@section('title', 'Gráfico por Ruta y Turnos')

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
                <h4 class="card-title text-center mb-4">INGRESOS POR TURNO</h4>
                <form id="turnosForm" class="row g-3">
                    <div class="col-md-6">
                        <label for="rutaSelect" class="form-label">Ruta:</label>
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
                        <select id="rutaSelect" class="form-select">
                            <option value="">Selecciona una ruta</option>
                            @foreach ($rutas as $ruta)
                            @php
                            $ciudad_inicial = strtoupper(trim($ruta->ciudad_inicial));
                            $ciudad_final = strtoupper(trim($ruta->ciudad_final));
                            $ciudad_inicial_abreviada = $abreviaciones[$ciudad_inicial] ?? $ciudad_inicial;
                            $ciudad_final_abreviada = $abreviaciones[$ciudad_final] ?? $ciudad_final;
                            @endphp
                                <option value="{{ $ruta->id }}"> {{ $ciudad_inicial_abreviada }} - {{ $ciudad_final_abreviada }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="turnoCheckboxContainer" class="form-label">Turnos:</label>
                        <div id="turnoCheckboxContainer" class="form-check">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="servicio" class="form-label">Tipo Servicio:</label>
                        <select id="servicio" name="servicio" class="form-select">
                            <option value="Total">Total</option>
                            <option value="SPI">SPI</option>
                            <option value="SPP">SPP</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fechaInicio" class="form-label">Fecha Inicio:</label>
                        <input type="date" id="fechaInicio" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFin" class="form-label">Fecha Fin:</label>
                        <input type="date" id="fechaFin" class="form-control">
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
                    <h2>INGRESOS POR TURNO</h2>
                    <h5 id="infoTotales">Importe Total: S/ 0 <br> P(): 0</h5>
                    <h5 id="rangoFechas">Rango de Fechas: - </h5>
                </div>
                <div class="position-relative mt-4">
                    <div class="d-flex justify-content-start position-absolute" style="top: -30px; left: 10px; z-index: 10;">
                        <button class="btn btn-light me-1" id="btn-semana">Semana</button>
                        <button class="btn btn-light me-1" id="btn-mes">Mes</button>
                        <button class="btn btn-light" id="btn-año">Año</button>
                    </div>
                    <div class="card shadow-sm mt-12 container-fluid">
                        <div style="padding-top: 50px;width: auto; height: 700px">
                            <canvas id="graficoTurno" style="width: 100%; height: auto;"></canvas>
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

    .card-promo {
        margin: 2px;
        padding: 15px;
        text-align: center;
        flex: 1; /* Para que las tarjetas se distribuyan bien */
    }
</style>

<!-- Scripts para manejar el gráfico y las acciones -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script>
    document.getElementById('rutaSelect').addEventListener('change', function() {
    const rutaId = this.value;
    const turnoContainer = document.getElementById('turnoCheckboxContainer');
    const servicioSelect = document.getElementById('servicio'); // Obtener el elemento select del tipo de servicio
    const tipoServicio = servicioSelect.value; // Obtener el valor seleccionado

    if (rutaId && tipoServicio) { // Verificar que ambos valores están presentes
        fetch(`/turnos/${rutaId}?servicio=${tipoServicio}`) // Pasar el tipo de servicio como parámetro
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al obtener los turnos: ' + response.statusText);
                }
                return response.json();
            })
            .then(turnos => {
                turnoContainer.innerHTML = ''; // Limpiar opciones anteriores
                turnos.forEach(turno => {
                    // Crear un contenedor de checkbox
                    const checkboxWrapper = document.createElement('div');
                    checkboxWrapper.classList.add('form-check');

                    // Crear el checkbox
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'turnoCheckboxContainer[]';
                    checkbox.value = turno.id;
                    checkbox.id = `turno-${turno.id}`;
                    checkbox.classList.add('form-check-input');

                    // Crear la etiqueta para el checkbox
                    const label = document.createElement('label');
                    label.htmlFor = `turno-${turno.id}`;
                    label.classList.add('form-check-label');
                    label.textContent = turno.hora;

                    // Agregar el checkbox y la etiqueta al contenedor
                    checkboxWrapper.appendChild(checkbox);
                    checkboxWrapper.appendChild(label);

                    // Agregar el contenedor de checkbox al contenedor principal
                    turnoContainer.appendChild(checkboxWrapper);
                });
            })
            .catch(error => console.error('Error al obtener turnos:', error));
    } else {
        turnoContainer.innerHTML = ''; // Limpiar si no hay ruta o tipo de servicio
    }
});
document.getElementById('servicio').addEventListener('change', function() {
    document.getElementById('rutaSelect').dispatchEvent(new Event('change')); 
});
    let ctx = document.getElementById('graficoTurno').getContext('2d');
    let graficoTurno;

    function fetchTurnoData(autosSeleccionados, fecha_inicio, fecha_fin) {
    let servicio = document.getElementById('servicio').value;
    let rutaS = document.getElementById('rutaSelect').value;

    fetch('{{ route("obtenerIngresosFiltrados") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            turnos: autosSeleccionados,
            ruta:rutaS,
            fecha_inicio: fecha_inicio,
            fecha_fin: fecha_fin,
            servicio: servicio 
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Datos recibidos:', data);
if (!data.turnos || data.turnos.length === 0) {
    graficoTurno.data.labels = [];  
    graficoTurno.data.datasets = [{  
        label: 'No hay datos disponibles',
        data: [],
        borderColor: 'rgba(0,0,0,0)',  
        backgroundColor: 'rgba(0,0,0,0)', 
        fill: false
    }];
    graficoTurno.update(); 
    return;  
}

let todasLasFechas = [];

document.getElementById('infoTotales').innerHTML = `Importe Total: S/ ${(data.total_general).toLocaleString('en-US')} - Pasajeros: ${parseInt(data.total_pasajeros).toLocaleString('en-US')})`;
data.turnos.forEach(turno => {
    todasLasFechas = [...todasLasFechas, ...turno.fechas];
});

todasLasFechas = [...new Set(todasLasFechas)].sort();

        if (graficoTurno) {
            graficoTurno.destroy();
        }
        graficoTurno = new Chart(ctx, {
         type: 'line',
         data: {
        labels: todasLasFechas,
        datasets: data.turnos.map((turno, index) => {
            const montos = todasLasFechas.map(fecha => {
                const indexFecha = turno.fechas.indexOf(fecha);
                return indexFecha >= 0 ? turno.montos[indexFecha] : NaN; // Usa NaN para continuar la línea
            });

            const pasajerosData = todasLasFechas.map(fecha => {
                const indexFecha = turno.fechas.indexOf(fecha);
                const pasajeros = indexFecha >= 0 ? turno.pasajeros[indexFecha] : 0;
                return indexFecha >= 0 ? turno.pasajeros[indexFecha] : 0;

            });
            return {
                label: `${turno.nombre} (TOTAL: S/. ${turno.total} - P= ${turno.totalp})`,
                data: montos,
                borderColor: generarColor(index),
                backgroundColor: generarColor(index),
                tension: 0.2,
                pointRadius: 2.5,
                pointHoverRadius: 6,
                fill: false,
                spanGaps: true,
                pasajerosData: pasajerosData // Agregamos los datos de pasajeros para acceder en el tooltip
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
                        let label = 'Monto';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(context.parsed.y);
                        }
                        let pasajeros = context.dataset.pasajerosData[context.dataIndex] ?? 0; // Obtener pasajeros de la fecha específica
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
// Actualizar el gráfico
graficoTurno.update();

document.getElementById('btn-mostrar-promedios').addEventListener('click', () => {
            mostrarMontosPromedio(data.turnos);
        });

        document.getElementById('btn-mostrar-montos').addEventListener('click', () => {
            mostrarMontos(data.turnos);
        });
    })
    .catch(error => {
        console.error("Error al obtener los datos:", error);
    });
}
    document.getElementById('btn-limpiar').addEventListener('click', function () {
    document.getElementById('filtros-container').classList.remove('d-none');
    document.querySelector('button[type="submit"]').classList.remove('d-none');
    graficoTurno.data.labels = [];
    graficoTurno.data.datasets = [];
    graficoTurno.update();
    infoIngresos.style.display = 'none'; // Oculta el div    
    document.getElementById('montos').innerHTML = '';
    document.getElementById('montoTotal').textContent = 0;

    });
    document.getElementById('turnosForm').addEventListener('submit', function (event) {
        event.preventDefault();
        let autosSeleccionados = Array.from(document.querySelectorAll('input[name="turnoCheckboxContainer[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = document.getElementById('fechaInicio').value;
        let fecha_fin = document.getElementById('fechaFin').value;
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
        fetchTurnoData(autosSeleccionados, fecha_inicio, fecha_fin);
    });
    document.getElementById('btn-semana').addEventListener('click', function () {
        let autosSeleccionados = Array.from(document.querySelectorAll('input[name="turnoCheckboxContainer[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setDate(fecha_inicio.getDate() - 7);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        actualizarRangoFechas(fecha_inicio, fecha_fin);
        document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    document.getElementById('montos').innerHTML = '';

        fetchTurnoData(autosSeleccionados, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });

    document.getElementById('btn-mes').addEventListener('click', function () {
        let autosSeleccionados = Array.from(document.querySelectorAll('input[name="turnoCheckboxContainer[]"]:checked')).map(checkbox => checkbox.value);
    let fecha_fin = new Date();
    fecha_fin.setHours(23, 59, 59);

    // Para el cálculo de un mes atrás
    let fecha_inicio = new Date(fecha_fin);
    fecha_inicio.setMonth(fecha_inicio.getMonth() - 1);
    actualizarRangoFechas(fecha_inicio, fecha_fin);
    document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    document.getElementById('montos').innerHTML = '';

    fetchTurnoData(autosSeleccionados, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
});

    document.getElementById('btn-año').addEventListener('click', function () {
        let autosSeleccionados = Array.from(document.querySelectorAll('input[name="turnoCheckboxContainer[]"]:checked')).map(checkbox => checkbox.value);
        let fecha_inicio = new Date();
        fecha_inicio.setFullYear(fecha_inicio.getFullYear() - 1);
        let fecha_fin = new Date();
        fecha_fin.setHours(23, 59, 59);
        actualizarRangoFechas(fecha_inicio, fecha_fin);
        document.getElementById('filtros-container').classList.add('d-none'); // Oculta el contenedor de filtros
    document.querySelector('button[type="submit"]').classList.add('d-none'); // Oculta el botón "Filtrar"
    document.getElementById('montos').innerHTML = '';

        fetchTurnoData(autosSeleccionados, fecha_inicio.toISOString().split('T')[0], fecha_fin.toISOString().split('T')[0]);
    });
    
function mostrarMontosPromedio(turnos) {
    const contenedor = document.getElementById('montos');
    contenedor.innerHTML = '';
    let index = 0;

    turnos.forEach(turno => {
        const { nombre, monto_promedio } = turno;

        const card = document.createElement('div');
        card.className = 'col-auto'; // Ajustar tamaño dinámico para que se agrupen mejor
        card.style.padding = '2px'; // Reducir padding entre los cards
        card.innerHTML = `
            <div class="card card-promedio" style="background-color: ${generarColor(index)};">
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
function mostrarMontos(turnos) {
    const contenedor = document.getElementById('montos');
    contenedor.innerHTML = '';
    let index = 0;

    turnos.forEach(turno => {
        const { nombre, ultimo_registro } = turno;

        const fecha = ultimo_registro?.fecha || 'N/A';
        const monto = ultimo_registro?.monto;

        const card = document.createElement('div');
        card.className = 'col-auto'; // Ajustar tamaño dinámico para que se agrupen mejor
        card.style.padding = '2px'; // Reducir padding entre los cards
        card.innerHTML = `
            <div class="card card-promedio2" style="background-color: ${generarColor(index)};">
                <p style="font-size: 13px; color: black; font-family: Georgia, serif;">${fecha}</p>
                <p style="font-size: 15px; color: black; font-family: Georgia, serif;">
                    <span style="font-size: 12px;">S/.</span> ${Math.round(monto).toLocaleString('en-US')}
                </p>
            </div>`;
        contenedor.appendChild(card);

        index++;
    });
}
    function generarColor(index) {
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
    function actualizarRangoFechas(fecha_inicio, fecha_fin) {
    const rangoFechas = document.getElementById('rangoFechas');

    function formatearFecha(fecha) {
        const date = new Date(fecha);
        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0'); // Se suma 1 porque los meses van de 0 a 11
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
