-- ===========================================================
-- FARMAVIDA - database.sql
-- Script completo para recrear la base de datos desde cero.
-- Incluye las 6 tablas que usan reporte.php, index.php,
-- dashboard_cliente.php, login.php, api.php y config.php:
--   clientes, administradores, productos, pedidos,
--   conversaciones, mensajes
--
-- CÓMO USARLO:
-- 1) Abre phpMyAdmin (http://localhost/phpmyadmin)
-- 2) Ve a la pestaña "SQL" (en la vista general, no dentro de
--    una base de datos específica)
-- 3) Pega TODO este archivo y dale "Continuar / Ejecutar"
-- 4) Después de esto, ve a crear_admin.php en el navegador para
--    crear tu primer usuario administrador, y luego BÓRRALO.
-- ===========================================================

CREATE DATABASE IF NOT EXISTS farmavida
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE farmavida;

-- ===========================================================
-- CLIENTES
-- Usada en: index.php, login.php, dashboard_cliente.php,
-- api.php (registrar_cliente), reporte.php
-- ===========================================================
CREATE TABLE clientes (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(150) NOT NULL,
    ciudad           VARCHAR(100) NOT NULL,
    telefono         VARCHAR(30)  NOT NULL,
    correo           VARCHAR(150) NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    direccion        VARCHAR(255) NOT NULL,
    documento        VARCHAR(30)  NOT NULL,
    condicion_salud  VARCHAR(255) DEFAULT NULL,
    eps              VARCHAR(100) NOT NULL,
    fecha_registro   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===========================================================
-- ADMINISTRADORES
-- Usada en: login.php, crear_admin.php, reporte.php
-- ===========================================================
CREATE TABLE administradores (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    usuario  VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre   VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

-- ===========================================================
-- PRODUCTOS
-- Usada en: index.php (catálogo), api.php (carrito), reporte.php
-- ===========================================================
CREATE TABLE productos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(150) NOT NULL,
    descripcion  VARCHAR(255) NOT NULL,
    precio       DECIMAL(10,2) NOT NULL DEFAULT 0,
    icono        VARCHAR(10) NOT NULL DEFAULT '💊'
) ENGINE=InnoDB;

-- ===========================================================
-- PEDIDOS
-- Usada en: api.php (carrito_confirmar), dashboard_cliente.php,
-- reporte.php
-- ===========================================================
CREATE TABLE pedidos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id   INT NOT NULL,
    producto_id  INT NOT NULL,
    precio       DECIMAL(10,2) NOT NULL,
    fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_pedidos_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===========================================================
-- CONVERSACIONES (chatbot)
-- Usada en: api.php (guardar_conversacion), reporte.php,
-- limpiar_datos_ejemplo.sql
-- ===========================================================
CREATE TABLE conversaciones (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio   DATETIME NOT NULL,
    fecha_fin      DATETIME NOT NULL,
    calificacion   TINYINT DEFAULT NULL
) ENGINE=InnoDB;

-- ===========================================================
-- MENSAJES (chatbot)
-- Usada en: api.php (guardar_conversacion), reporte.php,
-- limpiar_datos_ejemplo.sql
-- ===========================================================
CREATE TABLE mensajes (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id  INT NOT NULL,
    tipo             ENUM('user','bot') NOT NULL,
    texto            TEXT NOT NULL,
    hora             DATETIME NOT NULL,
    CONSTRAINT fk_mensajes_conversacion
        FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===========================================================
-- DATOS DE EJEMPLO: PRODUCTOS
-- Necesarios para que el catálogo de index.php no se vea vacío.
-- Ajusta nombres/precios a los reales de tu farmacia si quieres.
-- ===========================================================
INSERT INTO productos (nombre, descripcion, precio, icono) VALUES
('Acetaminofén 500mg', 'Caja x 20 tabletas, para dolor y fiebre.', 8900, '💊'),
('Ibuprofeno 400mg', 'Caja x 10 tabletas, antiinflamatorio.', 9500, '💊'),
('Vitamina C 1g', 'Tubo x 10 tabletas efervescentes.', 12500, '🍊'),
('Alcohol antiséptico 350ml', 'Frasco para desinfección de heridas.', 7200, '🧴'),
('Gel antibacterial 500ml', 'Con dispensador, 70% alcohol.', 15900, '🧴'),
('Termómetro digital', 'Medición rápida en 10 segundos.', 24900, '🌡️'),
('Tapabocas quirúrgico x50', 'Caja de 50 unidades, 3 capas.', 18900, '😷'),
('Multivitamínico adultos', 'Frasco x 30 cápsulas.', 34900, '🌿'),
('Medicamento formulado', 'Sujeto a validación con fórmula médica.', 0, '📋');

-- ===========================================================
-- DATOS DE EJEMPLO: CONVERSACIONES Y MENSAJES
-- Solo para que el reporte del admin no arranque vacío.
-- Si quieres empezar en cero, ejecuta después
-- limpiar_datos_ejemplo.sql (borra SOLO estas 2 tablas).
-- ===========================================================
INSERT INTO conversaciones (fecha_inicio, fecha_fin, calificacion) VALUES
(NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY + INTERVAL 4 MINUTE, 5),
(NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY + INTERVAL 6 MINUTE, 4),
(NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 3 MINUTE, NULL),
(NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 5 MINUTE, 3),
(NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY + INTERVAL 2 MINUTE, 5);

INSERT INTO mensajes (conversacion_id, tipo, texto, hora) VALUES
(1, 'user', 'Hola, ¿cuál es el horario de atención?', NOW() - INTERVAL 5 DAY),
(1, 'bot',  'Nuestro horario es de lunes a sábado de 7:00 a.m. a 9:00 p.m.', NOW() - INTERVAL 5 DAY + INTERVAL 1 MINUTE),
(1, 'user', 'Gracias', NOW() - INTERVAL 5 DAY + INTERVAL 2 MINUTE),

(2, 'user', 'Hola, ¿hacen envíos a domicilio?', NOW() - INTERVAL 4 DAY),
(2, 'bot',  'Sí, hacemos envíos en Bucaramanga y su área metropolitana.', NOW() - INTERVAL 4 DAY + INTERVAL 1 MINUTE),
(2, 'user', '¿Cuánto cuesta el envío?', NOW() - INTERVAL 4 DAY + INTERVAL 2 MINUTE),
(2, 'bot',  'El costo desde $4.500, según la zona.', NOW() - INTERVAL 4 DAY + INTERVAL 3 MINUTE),

(3, 'user', '¿Necesito fórmula médica para comprar?', NOW() - INTERVAL 3 DAY),
(3, 'bot',  'Solo para medicamentos que la requieran; puedes subir la foto al pedir.', NOW() - INTERVAL 3 DAY + INTERVAL 1 MINUTE),

(4, 'user', 'Quiero hablar con un asesor humano', NOW() - INTERVAL 2 DAY),
(4, 'bot',  'Claro, te comunico con un asesor en horario de atención.', NOW() - INTERVAL 2 DAY + INTERVAL 1 MINUTE),

(5, 'user', '¿Cuánto vale la vitamina C?', NOW() - INTERVAL 1 DAY),
(5, 'bot',  'Puedes revisarlo en la sección Productos del catálogo.', NOW() - INTERVAL 1 DAY + INTERVAL 1 MINUTE);
