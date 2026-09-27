-- Corrige la codificacion de caracteres de varias columnas ENUM que quedaron
-- con doble codificacion UTF-8 -> Latin1 -> UTF-8 (mojibake), por ejemplo
-- 'TÃ©cnico' en lugar de 'Técnico'. El origen real de la corrupcion esta en
-- la propia DEFINICION del ENUM (metadata de la tabla), no en la conexion
-- de la aplicacion ni en el HTML/JS: mysqli_set_charset('utf8mb4') ya se
-- aplica correctamente en 00-config/db.php para toda conexion via conectDB().
--
-- Seguridad: se verifico (solo lectura, entorno local) que NINGUNA fila
-- existente tiene actualmente asignada la etiqueta corrupta (ninguna fila
-- de usuarios tiene perfil='Técnico' con esos bytes, ninguna de materiales
-- tiene unidad_venta/medida/rendimiento con esos valores) porque ENUM no
-- puede asignar una etiqueta que la aplicacion nunca pudo escribir
-- correctamente. Por lo tanto, corregir solo el TEXTO de cada etiqueta,
-- preservando su POSICION dentro del ENUM, no reasigna ni pierde ningun
-- dato de fila existente.
--
-- Esta migracion NO reasigna los registros que hoy tienen el valor vacio
-- como consecuencia historica de este bug (10 usuarios con perfil='',
-- 1 con estado_civil='', 4 materiales con unidad_venta='', 4 con
-- unidad_medida='', 15 con unidad_rendimiento=''): no hay evidencia
-- suficiente para inferir con certeza cual era el valor pretendido de
-- cada registro individual, y esa es una decision de negocio, no tecnica.

ALTER TABLE usuarios
  MODIFY COLUMN perfil ENUM('Super Administrador','Administrador','Administrativo','Técnico','Operario','Tecnico Administrativo') DEFAULT NULL;

ALTER TABLE usuarios
  MODIFY COLUMN tipo_documento ENUM('DNI','LE','Cédula','Pasaporte','DNI Extranjero') DEFAULT NULL;

ALTER TABLE usuarios
  MODIFY COLUMN estado_civil ENUM('Casado','Soltero','Unión de hecho','Viudo') DEFAULT NULL;

ALTER TABLE materiales
  MODIFY COLUMN unidad_venta ENUM('Tubo','Chapa','Balde','Tira','Rollo','Bolsa','Pack','Unidad','Kilogramo','Metro','Caja','Día','Metro','Metro²','Hora') DEFAULT NULL;

ALTER TABLE materiales
  MODIFY COLUMN unidad_medida ENUM('Metros','Kilogramos','Unidades','Gramos','Unidad','Hora','Día') DEFAULT NULL;

ALTER TABLE materiales
  MODIFY COLUMN unidad_rendimiento ENUM('Metro','Metro²','Día','Hora') DEFAULT NULL;
