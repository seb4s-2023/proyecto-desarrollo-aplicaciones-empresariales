<?php
require 'config.php';
requerirCliente();

$stmt = $conexion->prepare(
    "SELECT nombre, ciudad, telefono, correo, direccion, eps, fecha_registro FROM clientes WHERE id = ?"
);
$stmt->bind_param('i', $_SESSION['cliente_id']);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Historial de compras de este cliente
$stmtPedidos = $conexion->prepare(
    "SELECT pr.nombre, pe.precio, pe.fecha
     FROM pedidos pe
     JOIN productos pr ON pr.id = pe.producto_id
     WHERE pe.cliente_id = ?
     ORDER BY pe.fecha DESC"
);
$stmtPedidos->bind_param('i', $_SESSION['cliente_id']);
$stmtPedidos->execute();
$pedidos = $stmtPedidos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPedidos->close();

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi cuenta - FarmaVida</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <div class="nav-container">
    <div class="logo">Farma<span>Vida</span></div>
    <nav class="nav-links">
      <a href="index.php">Volver al sitio</a>
      <a href="login.php?salir=1">Salir</a>
    </nav>
  </div>
</header>

<div class="reporte-header">
  <div class="section-inner">
    <h1>👤 Mi cuenta</h1>
    <p>Hola, <?= htmlspecialchars($cliente['nombre']) ?></p>
  </div>
</div>

<section>
  <div class="section-inner">
    <div class="panel" style="max-width:520px;">
      <h3>Mis datos</h3>
      <p><strong>Ciudad:</strong> <?= htmlspecialchars($cliente['ciudad']) ?></p>
      <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?></p>
      <p><strong>Correo:</strong> <?= htmlspecialchars($cliente['correo']) ?></p>
      <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion']) ?></p>
      <p><strong>EPS:</strong> <?= htmlspecialchars($cliente['eps']) ?></p>
      <p><strong>Cliente desde:</strong> <?= htmlspecialchars($cliente['fecha_registro']) ?></p>
    </div>

    <div class="panel" style="max-width:520px; margin-top:24px;">
      <h3>🛒 Mis compras</h3>
      <?php if (empty($pedidos)): ?>
        <p style="color:var(--texto-suave); font-size:0.9rem;">Todavía no has comprado ningún producto.</p>
      <?php else: ?>
        <table class="tabla-conv">
          <thead><tr><th>Producto</th><th>Precio</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php foreach ($pedidos as $ped): ?>
              <tr>
                <td><?= htmlspecialchars($ped['nombre']) ?></td>
                <td>$<?= number_format($ped['precio'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($ped['fecha']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="panel" style="max-width:520px; margin-top:24px;">
      <h3>💬 ¿Necesitas ayuda?</h3>
      <p style="color:var(--texto-suave); margin-bottom:14px;">
        Usa el chat de atención al cliente en la página principal.
      </p>
      <a href="index.php" class="btn btn-primary">Ir al chat</a>
    </div>
  </div>
</section>

<footer>
  <p><strong>FarmaVida</strong> · Mi cuenta</p>
</footer>

</body>
</html>
