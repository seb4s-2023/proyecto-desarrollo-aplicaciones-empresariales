-- ===========================================================
-- FARMAVIDA - limpiar_datos_ejemplo.sql
-- Borra las 5 conversaciones/mensajes de EJEMPLO que trae
-- database.sql, para que el reporte del admin arranque en cero
-- y solo muestre lo que tú vayas calificando de verdad en el
-- chatbot del sitio.
--
-- NO toca las tablas clientes, productos ni pedidos: solo limpia
-- conversaciones y mensajes.
--
-- CÓMO USARLO:
-- 1) Abre phpMyAdmin (http://localhost/phpmyadmin)
-- 2) Entra a la base de datos "farmavida"
-- 3) Ve a la pestaña "SQL"
-- 4) Pega esto y dale "Continuar / Ejecutar"
-- ===========================================================

USE farmavida;

-- mensajes se borra en cascada por la FK, pero lo hacemos explícito igual
DELETE FROM mensajes;
DELETE FROM conversaciones;

-- Reiniciamos el autoincremental para que las próximas conversaciones
-- reales empiecen limpias desde el id 1
ALTER TABLE conversaciones AUTO_INCREMENT = 1;
ALTER TABLE mensajes AUTO_INCREMENT = 1;
