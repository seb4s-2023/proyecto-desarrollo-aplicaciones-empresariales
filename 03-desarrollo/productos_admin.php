<?php
/* ===========================================================
   FARMAVIDA - productos_admin.php
   Panel de administración de productos (CRUD).
   Solo accesible para administradores logueados.

   Toda la lógica de datos (crear/editar/eliminar/listar) vive
   en api.php (acciones producto_crear, producto_editar,
   producto_eliminar, producto_listar). Esta página solo pinta
   la tabla y el formulario, y habla con api.php vía fetch()
   desde productos_admin.js.
   =========================================================== */
require 'config.php';
requerirAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de productos - FarmaVida</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <div class="nav-container">
    <div class="logo">Farma<span>Vida</span></div>
    <nav class="nav-links">
      <span style="font-size:0.85rem; color:var(--texto-suave);">Hola, <?= htmlspecialchars($_SESSION['admin_nombre']) ?></span>
      <a href="reporte.php">Reporte</a>
      <a href="index.php">← Volver al sitio</a>
      <a href="login.php?salir=1">Salir</a>
    </nav>
  </div>
</header>

<div class="reporte-header">
  <div class="section-inner">
    <h1>📦 Gestión de productos</h1>
    <p>Crea, edita y elimina los productos del catálogo de FarmaVida.</p>
  </div>
</div>

<section style="padding-top:30px;">
  <div class="section-inner">

    <div id="prod-mensaje" class="compra-mensaje" style="display:none;"></div>

    <!-- ===================== FORMULARIO CREAR/EDITAR ===================== -->
    <div class="panel" style="margin-bottom:28px;">
      <h3 id="form-titulo">➕ Nuevo producto</h3>

      <form id="form-producto">
        <input type="hidden" id="prod-id" value="">

        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
          <div class="form-bloque">
            <div class="campo">
              <label for="prod-nombre">Nombre</label>
              <input type="text" id="prod-nombre" required placeholder="Ej: Acetaminofén 500mg">
            </div>
            <div class="campo">
              <label for="prod-descripcion">Descripción</label>
              <input type="text" id="prod-descripcion" required placeholder="Ej: Caja x 20 tabletas, para dolor y fiebre.">
            </div>
            <div class="campo">
              <label for="prod-categoria">Categoría</label>
              <input type="text" id="prod-categoria" required placeholder="Ej: Analgésicos y antiinflamatorios">
            </div>
          </div>

          <div class="form-bloque">
            <div class="campo">
              <label for="prod-precio">Precio (COP)</label>
              <input type="number" id="prod-precio" required min="0" step="1" placeholder="Ej: 8900">
            </div>
            <div class="campo">
              <label for="prod-cantidad">Cantidad disponible</label>
              <input type="number" id="prod-cantidad" required min="0" step="1" placeholder="Ej: 50">
            </div>
            <div class="campo">
              <label for="prod-estado">Estado</label>
              <select id="prod-estado">
                <option value="activo">Activo (visible en el catálogo)</option>
                <option value="inactivo">Inactivo (oculto del catálogo)</option>
              </select>
            </div>
          </div>

          <div class="form-bloque">
            <div class="campo">
              <label for="prod-icono">Icono (emoji, respaldo si no hay imagen)</label>
              <input type="text" id="prod-icono" placeholder="Ej: 💊" maxlength="4" value="💊">
            </div>
            <div class="campo">
              <label for="prod-imagen">Imagen (ruta o URL, opcional)</label>
              <input type="text" id="prod-imagen" placeholder="Ej: img/productos/acetaminofen.jpg">
            </div>
          </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:10px;">
          <button type="submit" class="btn btn-primary" id="btn-guardar-producto">Guardar producto</button>
          <button type="button" class="btn-reset" id="btn-cancelar-edicion" style="display:none;">Cancelar edición</button>
        </div>
      </form>
    </div>

    <!-- ===================== TABLA DE PRODUCTOS ===================== -->
    <div class="panel">
      <h3>Catálogo completo</h3>
      <p style="font-size:0.82rem; color:var(--texto-suave); margin-bottom:14px;">
        Los productos "inactivos" no se muestran en la página principal, pero se conservan
        para no afectar el historial de compras ya realizadas.
      </p>

      <table class="tabla-conv" id="tabla-productos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-productos-body">
          <!-- se llena con JS -->
        </tbody>
      </table>
      <div class="vacio" id="productos-vacio" style="display:none;">
        Todavía no hay productos registrados.
      </div>
    </div>

  </div>
</section>

<footer>
  <p><strong>FarmaVida</strong> · Panel de administración de productos</p>
</footer>

<script src="productos_admin.js"></script>
</body>
</html>
