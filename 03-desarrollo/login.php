<?php
/* ===========================================================
   FARMAVIDA - login.php
   Un solo archivo para TODO lo de acceso:
   - GET  ?salir=1         -> cierra sesión (antes: logout.php)
   - POST (formulario)     -> valida credenciales (antes: login_procesar.php)
   - GET  (normal)         -> muestra el formulario de login
   =========================================================== */

require 'config.php';

// ---- Cerrar sesión (antes vivía en logout.php) ----
if (isset($_GET['salir'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// Si ya inició sesión, lo mandamos directo a su panel correspondiente
if (isset($_SESSION['cliente_id'])) { header('Location: dashboard_cliente.php'); exit; }
if (isset($_SESSION['admin_id']))   { header('Location: reporte.php'); exit; }

// ---- Procesar el formulario (antes vivía en login_procesar.php) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoPost = ($_POST['tipo'] ?? 'cliente') === 'admin' ? 'admin' : 'cliente';

    if ($tipoPost === 'admin') {
        $usuarioForm = trim($_POST['usuario'] ?? '');
        $passwordForm = $_POST['password'] ?? '';

        $stmt = $conexion->prepare("SELECT id, password, nombre FROM administradores WHERE usuario = ?");
        $stmt->bind_param('s', $usuarioForm);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($passwordForm, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            header('Location: reporte.php');
            exit;
        }

        header('Location: login.php?tipo=admin&error=1');
        exit;

    } else {
        $correoForm = trim($_POST['correo'] ?? '');
        $passwordForm = $_POST['password'] ?? '';

        $stmt = $conexion->prepare("SELECT id, password, nombre FROM clientes WHERE correo = ?");
        $stmt->bind_param('s', $correoForm);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cliente && $cliente['password'] && password_verify($passwordForm, $cliente['password'])) {
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            header('Location: dashboard_cliente.php');
            exit;
        }

        header('Location: login.php?error=1');
        exit;
    }
}

// ---- Mostrar el formulario (GET normal) ----
$tipo = ($_GET['tipo'] ?? 'cliente') === 'admin' ? 'admin' : 'cliente';
$error = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión - FarmaVida</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <div class="nav-container">
    <div class="logo">Farma<span>Vida</span></div>
    <nav class="nav-links">
      <a href="index.php">← Volver al sitio</a>
    </nav>
  </div>
</header>

<section>
  <div class="section-inner login-wrap">
    <div class="login-card">
      <h2 class="section-title">Iniciar sesión</h2>

      <div class="login-tabs">
        <a href="login.php?tipo=cliente" class="<?= $tipo === 'cliente' ? 'activa' : '' ?>">Soy cliente</a>
        <a href="login.php?tipo=admin" class="<?= $tipo === 'admin' ? 'activa' : '' ?>">Soy administrador</a>
      </div>

      <?php if ($error): ?>
        <div class="login-error">⚠️ Credenciales incorrectas. Intenta de nuevo.</div>
      <?php endif; ?>

      <?php if ($tipo === 'admin'): ?>
        <form method="POST" action="login.php">
          <input type="hidden" name="tipo" value="admin">
          <div class="campo">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required autofocus>
          </div>
          <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;">Entrar como administrador</button>
        </form>
      <?php else: ?>
        <form method="POST" action="login.php">
          <input type="hidden" name="tipo" value="cliente">
          <div class="campo">
            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" required autofocus>
          </div>
          <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;">Entrar</button>
        </form>
        <p style="font-size:0.85rem; color:var(--texto-suave); margin-top:14px;">
          ¿No tienes cuenta? <a href="index.php#registro" style="color:var(--verde-oscuro); font-weight:600;">Regístrate aquí</a>.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<footer>
  <p><strong>FarmaVida</strong> · Acceso de usuarios</p>
</footer>

</body>
</html>
