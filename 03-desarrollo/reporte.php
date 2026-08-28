<?php
require 'config.php';
requerirAdmin();

/* ===========================================================
   Antes estos datos se pedían con fetch() a reporte_datos.php.
   Ahora se calculan aquí mismo, en PHP, y se insertan directo
   como JSON en el <script> de abajo: un archivo menos, y una
   petición de red menos cada vez que carga la página.
   =========================================================== */

// ---- Total de clientes registrados ----
$totalClientes = $conexion->query("SELECT COUNT(*) AS total FROM clientes")->fetch_assoc()['total'];

// ---- Clientes registrados, con sus datos completos (nunca la contraseña) ----
$clientesDatos = [];
$resClientesDatos = $conexion->query(
    "SELECT id, nombre, ciudad, telefono, correo, direccion, documento, condicion_salud, eps, fecha_registro
     FROM clientes ORDER BY fecha_registro DESC"
);
while ($cl = $resClientesDatos->fetch_assoc()) {
    $clientesDatos[] = [
        'id' => (int)$cl['id'],
        'nombre' => $cl['nombre'],
        'ciudad' => $cl['ciudad'],
        'telefono' => $cl['telefono'],
        'correo' => $cl['correo'],
        'direccion' => $cl['direccion'],
        'documento' => $cl['documento'],
        'condicionSalud' => $cl['condicion_salud'],
        'eps' => $cl['eps'],
        'fechaRegistro' => $cl['fecha_registro']
    ];
}

// ---- Compras (pedidos) con el nombre del cliente y del producto ----
$comprasDatos = [];
$resCompras = $conexion->query(
    "SELECT cl.nombre AS cliente, pr.nombre AS producto, pe.precio, pe.fecha
     FROM pedidos pe
     JOIN clientes cl ON cl.id = pe.cliente_id
     JOIN productos pr ON pr.id = pe.producto_id
     ORDER BY pe.fecha DESC"
);
while ($c = $resCompras->fetch_assoc()) {
    $comprasDatos[] = [
        'cliente'  => $c['cliente'],
        'producto' => $c['producto'],
        'precio'   => (float)$c['precio'],
        'fecha'    => $c['fecha']
    ];
}

// ---- Conversaciones (las más recientes primero) ----
$conversacionesDatos = [];
$resConv = $conexion->query("SELECT id, fecha_inicio, fecha_fin, calificacion FROM conversaciones ORDER BY fecha_inicio DESC");
while ($conv = $resConv->fetch_assoc()) {
    $stmtMsg = $conexion->prepare("SELECT tipo, texto, hora FROM mensajes WHERE conversacion_id = ? ORDER BY hora ASC");
    $stmtMsg->bind_param('i', $conv['id']);
    $stmtMsg->execute();
    $resMsg = $stmtMsg->get_result();

    $mensajes = [];
    while ($m = $resMsg->fetch_assoc()) {
        $mensajes[] = ['tipo' => $m['tipo'], 'texto' => $m['texto'], 'hora' => $m['hora']];
    }
    $stmtMsg->close();

    $conversacionesDatos[] = [
        'id' => $conv['id'],
        'inicio' => $conv['fecha_inicio'],
        'fin' => $conv['fecha_fin'],
        'calificacion' => $conv['calificacion'] !== null ? (int)$conv['calificacion'] : null,
        'mensajes' => $mensajes
    ];
}

$datosReporte = [
    'error' => false,
    'totalClientes' => (int)$totalClientes,
    'conversaciones' => $conversacionesDatos,
    'compras' => $comprasDatos,
    'clientes' => $clientesDatos
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FarmaVida - Reporte mensual de atención</title>
<link rel="stylesheet" href="styles.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<header>
  <div class="nav-container">
    <div class="logo">Farma<span>Vida</span></div>
    <nav class="nav-links">
      <span style="font-size:0.85rem; color:var(--texto-suave);">Hola, <?= htmlspecialchars($_SESSION['admin_nombre']) ?></span>
      <a href="productos_admin.php">Productos</a>
      <a href="index.php">← Volver al sitio</a>
      <a href="login.php?salir=1">Salir</a>
    </nav>
  </div>
</header>

<div class="reporte-header">
  <div class="section-inner">
    <h1>📊 Reporte mensual de atención al cliente</h1>
    <p id="periodo-texto">Cargando periodo...</p>
  </div>
</div>

<div class="kpis">
  <div class="kpi-card">
    <div class="kpi-label">Personas atendidas por el chat</div>
    <div class="kpi-valor" id="kpi-atendidos">0</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Calificación promedio</div>
    <div class="kpi-valor" id="kpi-calificacion">0.0 ★</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Clientes registrados</div>
    <div class="kpi-valor" id="kpi-clientes">0</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Conversaciones sin calificar</div>
    <div class="kpi-valor coral" id="kpi-pendientes">0</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Ventas totales</div>
    <div class="kpi-valor" id="kpi-ventas">$0</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Pedidos realizados</div>
    <div class="kpi-valor" id="kpi-pedidos">0</div>
  </div>
</div>

<section style="padding-top:0;">
  <div class="section-inner">
    <div class="reporte-grid">

      <!-- Columna izquierda: gráfico + tabla -->
      <div>
        <div class="panel" style="margin-bottom:24px;">
          <h3>Distribución de calificaciones</h3>
          <canvas id="grafico-calificaciones" height="180"></canvas>
        </div>

        <div class="panel">
          <h3>Últimas conversaciones</h3>
          <table class="tabla-conv" id="tabla-conversaciones">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Mensajes</th>
                <th>Calificación</th>
              </tr>
            </thead>
            <tbody id="tabla-body">
              <!-- se llena con JS -->
            </tbody>
          </table>
          <div class="vacio" id="tabla-vacia" style="display:none;">
            Todavía no hay conversaciones registradas. Usa el chat en la página principal para generar datos.
          </div>
        </div>

        <div class="panel" style="margin-top:24px;">
          <h3>🛒 Compras de clientes</h3>
          <p style="font-size:0.82rem; color:var(--texto-suave); margin-bottom:14px;">
            Cada compra viene de la tabla <code>pedidos</code>, cruzada con <code>clientes</code> y
            <code>productos</code> para mostrar quién compró y qué compró.
          </p>
          <table class="tabla-conv" id="tabla-compras">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody id="tabla-compras-body">
              <!-- se llena con JS -->
            </tbody>
          </table>
          <div class="vacio" id="compras-vacia" style="display:none;">
            Todavía no se han registrado compras. Cuando un cliente compre un producto desde la página principal, aparecerá aquí.
          </div>
        </div>
      </div>

      <!-- Columna derecha: sugerencias -->
      <div>
        <div class="panel">
          <h3>💡 Sugerencias para la administración</h3>
          <p style="font-size:0.82rem; color:var(--texto-suave); margin-bottom:14px;">
            Generadas automáticamente a partir de las calificaciones y comentarios de los clientes.
          </p>
          <div id="sugerencias-lista"></div>
        </div>

        <div class="panel" style="margin-top:24px;">
          <h3>⚙️ Fuente de datos</h3>
          <p style="font-size:0.82rem; color:var(--texto-suave); margin-bottom:14px;">
            Este reporte consulta directamente la base de datos MySQL (tablas <code>clientes</code>,
            <code>conversaciones</code> y <code>mensajes</code>) a través de <code>reporte_datos.php</code>.
            El archivo <code>database.sql</code> ya incluye algunos registros de ejemplo.
          </p>
          <button class="btn btn-secondary" id="btn-recargar" style="width:100%;">🔄 Recargar datos</button>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ===================== CLIENTES REGISTRADOS ===================== -->
<section style="padding-top:0;">
  <div class="section-inner">
    <div class="panel">
      <h3>👥 Clientes registrados</h3>
      <p style="font-size:0.82rem; color:var(--texto-suave); margin-bottom:14px;">
        Datos completos de la tabla <code>clientes</code> (nunca se muestra la contraseña, que además está
        guardada cifrada). La columna <strong style="color:var(--coral-oscuro);">Condición de salud</strong> es
        un dato sensible según la Ley 1581 de 2012: úsala solo para fines de atención, nunca la compartas
        fuera de este panel.
      </p>
      <div style="overflow-x:auto;">
        <table class="tabla-conv" id="tabla-clientes">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Ciudad</th>
              <th>Teléfono</th>
              <th>Correo</th>
              <th>Dirección</th>
              <th>Documento</th>
              <th>EPS</th>
              <th style="color:var(--coral-oscuro);">Condición de salud</th>
              <th>Registrado</th>
            </tr>
          </thead>
          <tbody id="tabla-clientes-body">
            <!-- se llena con JS -->
          </tbody>
        </table>
      </div>
      <div class="vacio" id="clientes-vacio" style="display:none;">
        Todavía no hay clientes registrados. Aparecerán aquí en cuanto alguien complete el registro en el sitio.
      </div>
    </div>
  </div>
</section>

<footer>
  <p><strong>FarmaVida</strong> · Panel interno de administración</p>
</footer>

<script>
/* ===========================================================
   REPORTE.PHP - lógica del panel de administración
   Los datos se piden por fetch() a reporte_datos.php, que a su
   vez los saca de MySQL (y ahora exige sesión de administrador).
   =========================================================== */

// Los datos ya vienen calculados desde PHP (arriba en reporte.php),
// insertados directo aquí como JSON. Ya no hace falta reporte_datos.php
// ni una petición fetch() para tener los datos iniciales.
const DATOS_REPORTE = <?= json_encode($datosReporte) ?>;

function cargarDatosDesdeServidor() {
  // "Recargar datos" simplemente vuelve a pedir la página al servidor,
  // que recalcula todo en PHP con la información más reciente.
  return Promise.resolve(DATOS_REPORTE);
}

function formatearFecha(iso) {
  const d = new Date(iso);
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

// El nombre del cliente y del producto vienen de datos que el propio
// usuario escribió al registrarse, así que los escapamos antes de
// meterlos en innerHTML (para no abrir la puerta a un XSS guardado).
function escapeHtml(texto) {
  return String(texto)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function renderReporte() {
  let data;
  try {
    data = await cargarDatosDesdeServidor();
  } catch (err) {
    document.querySelector('.section-inner').innerHTML =
      '<div class="vacio">⚠️ No se pudo conectar con la base de datos. Revisa que Apache y MySQL estén encendidos en XAMPP, y que hayas importado database.sql.<br><br>Detalle: ' + err.message + '</div>';
    return;
  }

  const conversaciones = data.conversaciones;
  const clientes = { length: data.totalClientes };

  // Periodo (mes actual, en español)
  const ahora = new Date();
  const mesTexto = ahora.toLocaleDateString('es-CO', { month: 'long', year: 'numeric' });
  document.getElementById('periodo-texto').textContent =
    'Periodo: ' + mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1);

  // ---- KPIs ----
  const calificadas = conversaciones.filter(c => c.calificacion !== null && c.calificacion !== undefined);
  const promedio = calificadas.length
    ? (calificadas.reduce((s, c) => s + c.calificacion, 0) / calificadas.length)
    : 0;

  document.getElementById('kpi-atendidos').textContent = conversaciones.length;
  document.getElementById('kpi-calificacion').textContent = promedio.toFixed(1) + ' ★';
  document.getElementById('kpi-clientes').textContent = clientes.length;
  document.getElementById('kpi-pendientes').textContent = conversaciones.length - calificadas.length;

  // ---- Gráfico de distribución de calificaciones (1 a 5 estrellas) ----
  const conteoEstrellas = [0, 0, 0, 0, 0]; // índice 0 = 1 estrella ... índice 4 = 5 estrellas
  calificadas.forEach(c => { conteoEstrellas[c.calificacion - 1]++; });

  const ctx = document.getElementById('grafico-calificaciones');
  if (window.graficoActual) window.graficoActual.destroy();
  window.graficoActual = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['1 ★', '2 ★', '3 ★', '4 ★', '5 ★'],
      datasets: [{
        label: 'N° de clientes',
        data: conteoEstrellas,
        backgroundColor: ['#E85D4E', '#E8875D', '#F0A93E', '#8FBF8A', '#1B6F5C'],
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });

  // ---- Tabla de conversaciones ----
  const tbody = document.getElementById('tabla-body');
  tbody.innerHTML = '';
  const tablaVacia = document.getElementById('tabla-vacia');

  if (conversaciones.length === 0) {
    tablaVacia.style.display = 'block';
  } else {
    tablaVacia.style.display = 'none';
    conversaciones.slice().reverse().slice(0, 10).forEach(c => {
      const tr = document.createElement('tr');
      const cal = c.calificacion ? '★'.repeat(c.calificacion) + '☆'.repeat(5 - c.calificacion) : 'Sin calificar';
      tr.innerHTML = `<td>${formatearFecha(c.inicio)}</td><td>${c.mensajes.length}</td><td>${cal}</td>`;
      tbody.appendChild(tr);
    });
  }

  // ---- Compras (ventas reales de la tabla pedidos) ----
  const compras = data.compras || [];

  document.getElementById('kpi-pedidos').textContent = compras.length;
  const totalVentas = compras.reduce((suma, c) => suma + Number(c.precio), 0);
  document.getElementById('kpi-ventas').textContent =
    '$' + totalVentas.toLocaleString('es-CO', { maximumFractionDigits: 0 });

  const tbodyCompras = document.getElementById('tabla-compras-body');
  tbodyCompras.innerHTML = '';
  const comprasVacia = document.getElementById('compras-vacia');

  if (compras.length === 0) {
    comprasVacia.style.display = 'block';
  } else {
    comprasVacia.style.display = 'none';
    compras.slice(0, 15).forEach(c => {
      const tr = document.createElement('tr');
      tr.innerHTML =
        `<td>${escapeHtml(c.cliente)}</td>` +
        `<td>${escapeHtml(c.producto)}</td>` +
        `<td>$${Number(c.precio).toLocaleString('es-CO', { maximumFractionDigits: 0 })}</td>` +
        `<td>${formatearFecha(c.fecha)}</td>`;
      tbodyCompras.appendChild(tr);
    });
  }

  // ---- Clientes registrados ----
  renderClientes(data.clientes);

  // ---- Sugerencias automáticas ----
  renderSugerencias(conversaciones, calificadas, promedio);
}

function renderClientes(clientes) {
  const tbody = document.getElementById('tabla-clientes-body');
  const vacio = document.getElementById('clientes-vacio');
  tbody.innerHTML = '';

  if (!clientes || clientes.length === 0) {
    vacio.style.display = 'block';
    return;
  }
  vacio.style.display = 'none';

  clientes.forEach(cl => {
    const tr = document.createElement('tr');
    const condicion = cl.condicionSalud
      ? escapeHtml(cl.condicionSalud)
      : '<span style="color:var(--texto-suave);">No especificada</span>';

    tr.innerHTML =
      `<td>${escapeHtml(cl.nombre)}</td>` +
      `<td>${escapeHtml(cl.ciudad)}</td>` +
      `<td>${escapeHtml(cl.telefono)}</td>` +
      `<td>${escapeHtml(cl.correo)}</td>` +
      `<td>${escapeHtml(cl.direccion)}</td>` +
      `<td>${escapeHtml(cl.documento)}</td>` +
      `<td>${escapeHtml(cl.eps)}</td>` +
      `<td>${condicion}</td>` +
      `<td>${formatearFecha(cl.fechaRegistro)}</td>`;
    tbody.appendChild(tr);
  });
}

function renderSugerencias(conversaciones, calificadas, promedio) {
  const cont = document.getElementById('sugerencias-lista');
  cont.innerHTML = '';
  const sugerencias = [];

  if (conversaciones.length === 0) {
    cont.innerHTML = '<div class="vacio">Aún no hay suficientes datos para generar sugerencias. Registra conversaciones desde el chat.</div>';
    return;
  }

  // Regla 1: calificación promedio baja
  if (promedio > 0 && promedio < 3.5) {
    sugerencias.push({
      icono: '⚠️',
      titulo: 'Revisar calidad de la atención',
      texto: `La calificación promedio (${promedio.toFixed(1)} ★) está por debajo del estándar deseado (4.0). Se recomienda capacitar al equipo o ampliar las respuestas del chatbot.`
    });
  }

  // Regla 2: muchas conversaciones sin calificar
  const sinCalificar = conversaciones.length - calificadas.length;
  if (sinCalificar > conversaciones.length * 0.4 && conversaciones.length >= 3) {
    sugerencias.push({
      icono: '📋',
      titulo: 'Incentivar la calificación del servicio',
      texto: `El ${Math.round((sinCalificar / conversaciones.length) * 100)}% de las conversaciones no fueron calificadas. Considera pedir la calificación de forma más visible al cerrar el chat.`
    });
  }

  // Regla 3: muchas preguntas sobre un mismo tema (según palabras frecuentes)
  const todasPalabras = conversaciones
    .flatMap(c => c.mensajes.filter(m => m.tipo === 'user').map(m => m.texto.toLowerCase()))
    .join(' ');

  const temas = [
    { palabra: 'envio', nombre: 'envíos y domicilios' },
    { palabra: 'precio', nombre: 'precios de productos' },
    { palabra: 'formula', nombre: 'validación de fórmulas médicas' },
    { palabra: 'pedido', nombre: 'estado de pedidos' },
    { palabra: 'humano', nombre: 'solicitudes de asesor humano' }
  ];
  temas.forEach(t => {
    const apariciones = (todasPalabras.match(new RegExp(t.palabra, 'g')) || []).length;
    if (apariciones >= 3) {
      sugerencias.push({
        icono: '🔎',
        titulo: `Alta demanda: ${t.nombre}`,
        texto: `Se detectaron ${apariciones} consultas relacionadas con ${t.nombre} este periodo. Considera destacar esta información de forma más visible en la página principal.`
      });
    }
  });

  // Regla 4: si hay muchas solicitudes de "hablar con humano"
  if (todasPalabras.includes('humano') || todasPalabras.includes('asesor')) {
    sugerencias.push({
      icono: '🙋',
      titulo: 'Reforzar el equipo de asesores humanos',
      texto: 'Varios clientes solicitaron hablar con una persona. Evalúa tener más asesores disponibles en horas pico.'
    });
  }

  // Si todo está bien, mensaje positivo
  if (sugerencias.length === 0) {
    sugerencias.push({
      icono: '✅',
      titulo: 'Buen desempeño general',
      texto: 'No se detectaron alertas relevantes este periodo. La atención al cliente se mantiene dentro de los parámetros esperados.'
    });
  }

  sugerencias.forEach(s => {
    const div = document.createElement('div');
    div.className = 'sugerencia-item';
    div.innerHTML = `
      <div class="sugerencia-icono">${s.icono}</div>
      <div>
        <h4>${s.titulo}</h4>
        <p>${s.texto}</p>
      </div>`;
    cont.appendChild(div);
  });
}

/* ---- Botón de recarga: como los datos ya vienen del servidor
   al cargar la página, "recargar" ahora significa pedirle a PHP
   una versión fresca (por si otro admin registró algo mientras
   tanto). ---- */
document.getElementById('btn-recargar').addEventListener('click', () => location.reload());

renderReporte();
</script>

</body>
</html>
