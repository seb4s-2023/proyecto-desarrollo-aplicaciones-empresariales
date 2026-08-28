<?php
require 'config.php';

// Traemos el catálogo desde la base de datos (tabla productos)
$productos = [];
$resProd = $conexion->query(
    "SELECT id, nombre, descripcion, categoria, precio, icono, imagen, cantidad_disponible
     FROM productos WHERE estado = 'activo' ORDER BY id"
);
while ($p = $resProd->fetch_assoc()) {
    $productos[] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FarmaVida - Tu farmacia de confianza</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- ===================== HEADER ===================== -->
<header>
  <div class="nav-container">
    <div class="logo">Farma<span>Vida</span></div>
    <nav class="nav-links">
      <a href="#productos">Productos</a>
      <a href="#registro">Registrarme</a>
      <a href="#nosotros">Nosotros</a>
      <?php if (isset($_SESSION['cliente_id'])): ?>
        <a href="dashboard_cliente.php" class="admin-link">Mi cuenta</a>
        <a href="login.php?salir=1">Salir</a>
      <?php elseif (isset($_SESSION['admin_id'])): ?>
        <a href="productos_admin.php" class="admin-link">Productos</a>
        <a href="reporte.php" class="admin-link">Panel administración</a>
        <a href="login.php?salir=1">Salir</a>
      <?php else: ?>
        <a href="login.php">Iniciar sesión</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- ===================== HERO ===================== -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <div class="eyebrow">Farmacia online</div>
      <h1>Tus medicamentos y productos de salud, sin salir de casa</h1>
      <p>Domicilios en Bucaramanga y área metropolitana, atención por chat las 24 horas y químicos farmacéuticos validando cada fórmula.</p>
      <a href="#registro" class="btn btn-primary">Registrarme como cliente</a>
      <a href="#productos" class="btn btn-secondary">Ver productos</a>
    </div>
    <div class="hero-badge">
      <div class="stat">15 min</div>
      <div class="stat-label">Tiempo promedio de respuesta del chat</div>
      <div class="stat">+2.400</div>
      <div class="stat-label">Clientes atendidos este año</div>
    </div>
  </div>
</section>

<!-- ===================== PRODUCTOS ===================== -->
<section id="productos">
  <div class="section-inner">
    <h2 class="section-title">Nuestros productos</h2>
    <p class="section-sub">Catálogo desde la base de datos. Si tienes cuenta, puedes comprar directamente aquí.</p>

    <div id="compra-mensaje" class="compra-mensaje" style="display:none;"></div>

    <div class="productos-grid">
      <?php foreach ($productos as $p): ?>
        <div class="producto-card">
          <?php if (!empty($p['imagen'])): ?>
            <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"
                 style="width:100%; height:120px; object-fit:cover; border-radius:10px; margin-bottom:14px;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
          <?php endif; ?>
          <div class="producto-icon" <?= !empty($p['imagen']) ? "style=\"display:none;\"" : '' ?>><?= htmlspecialchars($p['icono']) ?></div>
          <div style="font-size:0.72rem; color:var(--verde-oscuro); font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
            <?= htmlspecialchars($p['categoria']) ?>
          </div>
          <h3><?= htmlspecialchars($p['nombre']) ?></h3>
          <p><?= htmlspecialchars($p['descripcion']) ?></p>
          <div class="producto-precio">
            <?= $p['precio'] > 0 ? 'Desde $' . number_format($p['precio'], 0, ',', '.') : 'Precio según fórmula' ?>
          </div>
          <?php if ($p['precio'] > 0 && (int)$p['cantidad_disponible'] > 0): ?>
            <button type="button" class="btn btn-primary btn-agregar-carrito" data-id="<?= (int)$p['id'] ?>" style="width:100%; margin-top:12px;">
              🛒 Agregar al carrito
            </button>
          <?php elseif ($p['precio'] > 0): ?>
            <button type="button" class="btn btn-secondary" style="width:100%; margin-top:12px;" disabled>
              Sin disponibilidad
            </button>
          <?php else: ?>
            <a href="#" onclick="document.getElementById('chat-toggle').click(); return false;" class="btn btn-secondary" style="width:100%; margin-top:12px; text-align:center;">
              Consultar por chat
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== REGISTRO DE CLIENTE ===================== -->
<?php if (!isset($_SESSION['cliente_id'])): ?>
<section id="registro" style="background:#FAFDFC;">
  <div class="section-inner">
    <h2 class="section-title">Regístrate como cliente</h2>
    <p class="section-sub">
      Para atenderte mejor pedimos algunos datos. Los agrupamos según su nivel de sensibilidad,
      conforme a la Ley 1581 de 2012 de protección de datos personales en Colombia.
      ¿Ya tienes cuenta? <a href="login.php" style="color:var(--verde-oscuro); font-weight:600;">Inicia sesión aquí</a>.
    </p>

    <div class="registro-wrap">
      <form id="form-registro">
        <div class="form-grid">

          <!-- DATOS PÚBLICOS -->
          <div class="form-bloque">
            <h4>① Datos públicos</h4>
            <p class="desc">Información sin restricción de acceso: no afecta tu intimidad si es conocida por otros.</p>
            <div class="campo">
              <label for="nombre">Nombre completo</label>
              <input type="text" id="nombre" required placeholder="Ej: Sebastián Rojas">
            </div>
            <div class="campo">
              <label for="ciudad">Ciudad</label>
              <input type="text" id="ciudad" required placeholder="Ej: Bucaramanga">
            </div>
          </div>

          <!-- DATOS SEMIPRIVADOS -->
          <div class="form-bloque">
            <h4>② Datos semiprivados</h4>
            <p class="desc">No son públicos, pero pueden solicitarse para fines comerciales como contactarte.</p>
            <div class="campo">
              <label for="telefono">Teléfono</label>
              <input type="tel" id="telefono" required placeholder="Ej: 3001234567">
            </div>
            <div class="campo">
              <label for="correo">Correo electrónico</label>
              <input type="email" id="correo" required placeholder="Ej: correo@ejemplo.com">
            </div>
            <div class="campo">
              <label for="password">Contraseña</label>
              <input type="password" id="password" required minlength="6" placeholder="Mínimo 6 caracteres">
            </div>
          </div>

          <!-- DATOS PRIVADOS -->
          <div class="form-bloque">
            <h4>③ Datos privados</h4>
            <p class="desc">Solo le interesan a la empresa y a ti. Requieren tu autorización expresa.</p>
            <div class="campo">
              <label for="direccion">Dirección de envío</label>
              <input type="text" id="direccion" required placeholder="Ej: Cra 27 # 45-12">
            </div>
            <div class="campo">
              <label for="documento">Número de documento</label>
              <input type="text" id="documento" required placeholder="Ej: 1005678234">
            </div>
          </div>

          <!-- DATOS SENSIBLES -->
          <div class="form-bloque sensible">
            <h4>④ Datos sensibles</h4>
            <p class="desc">Datos de salud. Tienen protección reforzada: nunca se usan para discriminarte ni se venden a terceros.</p>
            <div class="campo">
              <label for="condicion-salud">¿Alguna condición de salud relevante? (opcional)</label>
              <input type="text" id="condicion-salud" placeholder="Ej: hipertensión, alergias">
            </div>
            <div class="campo">
              <label for="eps">EPS</label>
              <select id="eps" required>
                <option value="">Selecciona...</option>
                <option>Nueva EPS</option>
                <option>Sura</option>
                <option>Sanitas</option>
                <option>Compensar</option>
                <option>Otra</option>
              </select>
            </div>
          </div>
        </div>

        <div class="aviso-privacidad">
          🔒 <strong>Aviso de privacidad:</strong> tus datos sensibles (columna ④) solo se recolectan con tu
          autorización explícita, se almacenan cifrados y nunca se comparten con terceros sin tu consentimiento,
          conforme a la Ley 1581 de 2012 y el Decreto 1377 de 2013.
        </div>

        <label class="checkbox-consent">
          <input type="checkbox" required>
          Autorizo el tratamiento de mis datos personales, incluidos los datos sensibles de salud, de acuerdo con la política de privacidad de FarmaVida.
        </label>

        <button type="submit" class="btn btn-primary">Completar registro</button>

        <div id="registro-confirmacion">✅ ¡Registro exitoso! Te estamos llevando a tu cuenta...</div>
      </form>
    </div>
  </div>
</section>
<?php else: ?>
<section id="registro" style="background:#FAFDFC;">
  <div class="section-inner">
    <div class="panel" style="max-width:520px;">
      <h3>✅ Ya tienes una cuenta activa</h3>
      <p style="color:var(--texto-suave); margin:10px 0 16px;">
        Estás conectado. Revisa tus datos y tus compras en tu panel personal.
      </p>
      <a href="dashboard_cliente.php" class="btn btn-primary">Ir a mi cuenta</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== NOSOTROS ===================== -->
<section id="nosotros">
  <div class="section-inner">
    <h2 class="section-title">Sobre FarmaVida</h2>
    <p class="section-sub">
      Somos una farmacia con atención presencial y domicilios en Bucaramanga, comprometida con la
      protección de los datos de nuestros clientes y con una atención rápida y humana, apoyada en
      tecnología de chat en línea.
    </p>
  </div>
</section>

<footer>
  <p><strong>FarmaVida</strong> · Proyecto académico de e-commerce con protección de datos</p>
  <p>Bucaramanga, Colombia</p>
</footer>

<!-- ===================== CARRITO ===================== -->
<button id="carrito-toggle" title="Ver mi carrito">
  🛒
  <span id="carrito-badge" class="carrito-badge" style="display:none;">0</span>
</button>

<div id="carrito-panel">
  <div class="carrito-header">
    <div class="titulo">Mi carrito</div>
    <button id="carrito-close">✕</button>
  </div>

  <div class="carrito-body" id="carrito-body">
    <div class="carrito-vacio">Tu carrito está vacío. Agrega productos desde el catálogo.</div>
  </div>

  <div class="carrito-footer">
    <div class="carrito-total">
      <span>Total</span>
      <span id="carrito-total">$0</span>
    </div>
    <button type="button" id="btn-confirmar-compra" class="btn btn-primary" style="width:100%;" disabled>
      Confirmar compra
    </button>
  </div>
</div>

<!-- ===================== CHATBOT ===================== -->
<button id="chat-toggle">💬</button>

<div id="chat-panel">
  <div class="chat-header">
    <div>
      <div class="titulo">Atención FarmaVida</div>
      <div class="estado">● En línea</div>
    </div>
    <button id="chat-close">✕</button>
  </div>

  <div class="chat-body" id="chat-body">
    <div class="msg bot">¡Hola! Bienvenido a FarmaVida 👋 ¿En qué te puedo ayudar?</div>
  </div>

  <div class="chat-quick">
    <button type="button">Horarios</button>
    <button type="button">Envíos</button>
    <button type="button">Precios</button>
    <button type="button">Hablar con un asesor</button>
  </div>

  <div class="chat-rating" id="chat-rating-box" style="display:none;">
    <p>Antes de cerrar, ¿cómo calificarías la atención?</p>
    <div class="estrellas">
      <span data-valor="1">★</span>
      <span data-valor="2">★</span>
      <span data-valor="3">★</span>
      <span data-valor="4">★</span>
      <span data-valor="5">★</span>
    </div>
  </div>

  <form class="chat-input" id="chat-form">
    <input type="text" id="chat-input" placeholder="Escribe tu mensaje..." autocomplete="off">
    <button type="submit">Enviar</button>
  </form>
</div>

<script>
  // El JS necesita saber si hay sesión de cliente para permitir comprar
  window.CLIENTE_LOGUEADO = <?= isset($_SESSION['cliente_id']) ? 'true' : 'false' ?>;
</script>
<script src="script.js"></script>
</body>
</html>
