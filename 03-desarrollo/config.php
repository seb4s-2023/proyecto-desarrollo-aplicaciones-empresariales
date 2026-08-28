<?php
/* ===========================================================
   FARMAVIDA - config.php
   Conexión a MySQL + sesión + funciones de autenticación.

   Antes estas funciones vivían en un archivo aparte (auth.php)
   que había que incluir en cada página con `require 'auth.php';`.
   Como config.php YA se incluye en absolutamente todas las
   páginas, fusionamos auth.php aquí: menos archivos, y ya no
   hay que acordarse de incluir dos archivos en vez de uno.
   =========================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$usuario = 'root';
$contrasena = '';
$basedatos = 'farmavida';

$conexion = new mysqli($host, $usuario, $contrasena, $basedatos);

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode([
        'error' => true,
        'mensaje' => 'No se pudo conectar a la base de datos: ' . $conexion->connect_error
    ]));
}

$conexion->set_charset('utf8mb4');

/* -----------------------------------------------------------
   Funciones de sesión (antes en auth.php)
------------------------------------------------------------ */

function clienteLogueado() {
    return isset($_SESSION['cliente_id']);
}

function adminLogueado() {
    return isset($_SESSION['admin_id']);
}

// Usar al inicio de páginas exclusivas para clientes (ej. dashboard_cliente.php)
function requerirCliente() {
    if (!clienteLogueado()) {
        header('Location: login.php');
        exit;
    }
}

// Usar al inicio de páginas exclusivas para administradores (ej. reporte.php)
function requerirAdmin() {
    if (!adminLogueado()) {
        header('Location: login.php?tipo=admin');
        exit;
    }
}