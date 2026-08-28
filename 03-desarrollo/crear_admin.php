<?php
/* ===========================================================
   FARMAVIDA - crear_admin.php
   Script de UN SOLO USO para crear el primer administrador.
   No tiene ninguna protección (nadie ha iniciado sesión todavía
   la primera vez que se usa), así que BÓRRALO del servidor en
   cuanto crees tu usuario administrador.
   =========================================================== */

require 'config.php';

/* -----------------------------------------------------------
   CANDADO DE SEGURIDAD:
   Si ya existe al menos un administrador en la base de datos,
   este script se autobloquea. Así, aunque alguien encuentre la
   URL después de que ya creaste tu cuenta, no podrá usarla para
   crear administradores nuevos sin autenticarse.
   Sigue siendo buena práctica borrar el archivo del servidor,
   pero esto cubre el caso de que se te olvide.
------------------------------------------------------------ */
$totalAdmins = $conexion->query("SELECT COUNT(*) AS total FROM administradores")->fetch_assoc()['total'];

if ($totalAdmins > 0) {
    http_response_code(403);
    die('Este script ya cumplió su función (ya existe un administrador registrado) y quedó deshabilitado por seguridad. Elimina el archivo crear_admin.php del servidor.');
}

$mensaje = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');

    if ($usuario === '' || $password === '' || $nombre === '') {
        $mensaje = 'Completa todos los campos.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO administradores (usuario, password, nombre) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $usuario, $hash, $nombre);

        if ($stmt->execute()) {
            $ok = true;
            $mensaje = "✅ Administrador '$usuario' creado correctamente. Ya puedes ir a login.php e iniciar sesión. Por seguridad, borra este archivo (crear_admin.php) del servidor.";
        } else {
            $mensaje = '⚠️ Error: ' . $stmt->error . ' (¿ese usuario ya existe?)';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear administrador - FarmaVida</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<section>
  <div class="section-inner login-wrap">
    <div class="login-card">
      <h2 class="section-title">Crear el primer administrador</h2>
      <p style="font-size:0.85rem; color:var(--texto-suave); margin-bottom:20px;">
        Usa este formulario una sola vez para crear tu cuenta de administrador.
        Después bórralo del servidor.
      </p>

      <?php if ($mensaje): ?>
        <div class="<?= $ok ? 'login-ok' : 'login-error' ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <?php if (!$ok): ?>
      <form method="POST">
        <div class="campo">
          <label for="nombre">Nombre a mostrar</label>
          <input type="text" id="nombre" name="nombre" required placeholder="Ej: Administrador FarmaVida">
        </div>
        <div class="campo">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" required placeholder="Ej: admin">
        </div>
        <div class="campo">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Crear administrador</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>

</body>
</html>