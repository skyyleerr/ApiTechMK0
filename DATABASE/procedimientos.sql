-- PROCEDIMIENTOS ALMACENADOS CORREGIDOS - BASE DE DATOS APITECH
USE apitech;

-- PROCEDIMIENTOS PARA TABLA: USUARIOS
-- 1. Crear usuario
DELIMITER //
CREATE PROCEDURE sp_crear_usuario(
  IN p_nombre VARCHAR(100),
  IN p_correo VARCHAR(100),
  IN p_password VARCHAR(255),
  OUT p_id_usuario INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al crear usuario';
    SET p_id_usuario = 0;
  END;
  
  INSERT INTO usuarios (nombre, correo, password)
  VALUES (p_nombre, p_correo, p_password);
  
  SET p_id_usuario = LAST_INSERT_ID();
  SET p_resultado = 'Usuario creado exitosamente';
END//
DELIMITER ;

-- 2. Obtener usuario por ID
DELIMITER //
CREATE PROCEDURE sp_obtener_usuario(
  IN p_id_usuario INT
)
BEGIN
  SELECT id_usuario, nombre, correo, fecha_registro
  FROM usuarios
  WHERE id_usuario = p_id_usuario;
END//
DELIMITER ;

-- 3. Obtener usuario por correo
DELIMITER //
CREATE PROCEDURE sp_obtener_usuario_por_correo(
  IN p_correo VARCHAR(100)
)
BEGIN
  SELECT id_usuario, nombre, correo, fecha_registro
  FROM usuarios
  WHERE correo = p_correo;
END//
DELIMITER ;

-- 4. Actualizar usuario
DELIMITER //
CREATE PROCEDURE sp_actualizar_usuario(
  IN p_id_usuario INT,
  IN p_nombre VARCHAR(100),
  IN p_correo VARCHAR(100),
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al actualizar usuario';
  END;
  
  UPDATE usuarios
  SET nombre = p_nombre, correo = p_correo
  WHERE id_usuario = p_id_usuario;
  
  SET p_resultado = 'Usuario actualizado exitosamente';
END//
DELIMITER ;

-- 5. Eliminar usuario
DELIMITER //
CREATE PROCEDURE sp_eliminar_usuario(
  IN p_id_usuario INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al eliminar usuario';
  END;
  
  DELETE FROM usuarios
  WHERE id_usuario = p_id_usuario;
  
  SET p_resultado = 'Usuario eliminado exitosamente';
END//
DELIMITER ;

-- PROCEDIMIENTOS PARA TABLA: COLMENAS
-- 6. Crear colmena (CORREGIDO: Faltaba id_usuario)
DELIMITER //
CREATE PROCEDURE sp_crear_colmena(
  IN p_id_usuario INT,
  IN p_nombre VARCHAR(100),
  IN p_ubicacion VARCHAR(150),
  IN p_estado VARCHAR(50),
  OUT p_id_colmena INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al crear colmena';
    SET p_id_colmena = 0;
  END;
  
  INSERT INTO colmenas (id_usuario, nombre, ubicacion, estado)
  VALUES (p_id_usuario, p_nombre, p_ubicacion, p_estado);
  
  SET p_id_colmena = LAST_INSERT_ID();
  SET p_resultado = 'Colmena creada exitosamente';
END//
DELIMITER ;

-- 7. Obtener colmena por ID
DELIMITER //
CREATE PROCEDURE sp_obtener_colmena(
  IN p_id_colmena INT
)
BEGIN
  SELECT id_colmena, id_usuario, nombre, ubicacion, estado, fecha_creacion
  FROM colmenas
  WHERE id_colmena = p_id_colmena;
END//
DELIMITER ;

-- 8. Listar todas las colmenas (CORREGIDO: Agregado id_usuario)
DELIMITER //
CREATE PROCEDURE sp_listar_colmenas()
BEGIN
  SELECT id_colmena, id_usuario, nombre, ubicacion, estado, fecha_creacion
  FROM colmenas
  ORDER BY fecha_creacion DESC;
END//
DELIMITER ;

-- 9. Actualizar colmena
DELIMITER //
CREATE PROCEDURE sp_actualizar_colmena(
  IN p_id_colmena INT,
  IN p_nombre VARCHAR(100),
  IN p_ubicacion VARCHAR(150),
  IN p_estado VARCHAR(50),
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al actualizar colmena';
  END;
  
  UPDATE colmenas
  SET nombre = p_nombre, ubicacion = p_ubicacion, estado = p_estado
  WHERE id_colmena = p_id_colmena;
  
  SET p_resultado = 'Colmena actualizada exitosamente';
END//
DELIMITER ;

-- 10. Eliminar colmena
DELIMITER //
CREATE PROCEDURE sp_eliminar_colmena(
  IN p_id_colmena INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al eliminar colmena';
  END;
  
  DELETE FROM colmenas
  WHERE id_colmena = p_id_colmena;
  
  SET p_resultado = 'Colmena eliminada exitosamente';
END//
DELIMITER ;

-- PROCEDIMIENTOS PARA TABLA: COLMENAS DE USUARIO (SIMPLIFICADO)
-- 11. Obtener colmenas de un usuario (CORREGIDO: Sin tabla usuario_colmena)
DELIMITER //
CREATE PROCEDURE sp_obtener_colmenas_usuario(
  IN p_id_usuario INT
)
BEGIN
  SELECT id_colmena, nombre, ubicacion, estado, fecha_creacion
  FROM colmenas
  WHERE id_usuario = p_id_usuario
  ORDER BY fecha_creacion DESC;
END//
DELIMITER ;

-- PROCEDIMIENTOS PARA TABLA: SENSORES
-- 12. Registrar lectura de sensor
DELIMITER //
CREATE PROCEDURE sp_registrar_sensor(
  IN p_id_colmena INT,
  IN p_tipo VARCHAR(50),
  IN p_valor FLOAT,
  OUT p_id_sensor INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al registrar sensor';
    SET p_id_sensor = 0;
  END;
  
  INSERT INTO sensores (id_colmena, tipo, valor)
  VALUES (p_id_colmena, p_tipo, p_valor);
  
  SET p_id_sensor = LAST_INSERT_ID();
  SET p_resultado = 'Sensor registrado exitosamente';
END//
DELIMITER ;

-- 13. Obtener lecturas de sensor por colmena
DELIMITER //
CREATE PROCEDURE sp_obtener_sensores_colmena(
  IN p_id_colmena INT,
  IN p_limite INT
)
BEGIN
  SELECT id_sensor, tipo, valor, fecha
  FROM sensores
  WHERE id_colmena = p_id_colmena
  ORDER BY fecha DESC
  LIMIT p_limite;
END//
DELIMITER ;

-- 14. Obtener última lectura de sensor por tipo
DELIMITER //
CREATE PROCEDURE sp_obtener_ultimo_sensor(
  IN p_id_colmena INT,
  IN p_tipo VARCHAR(50)
)
BEGIN
  SELECT id_sensor, tipo, valor, fecha
  FROM sensores
  WHERE id_colmena = p_id_colmena AND tipo = p_tipo
  ORDER BY fecha DESC
  LIMIT 1;
END//
DELIMITER ;

-- 15. Obtener promedio de sensores
DELIMITER //
CREATE PROCEDURE sp_obtener_promedio_sensores(
  IN p_id_colmena INT,
  IN p_tipo VARCHAR(50),
  IN p_horas INT
)
BEGIN
  SELECT 
    tipo,
    AVG(valor) AS valor_promedio,
    MIN(valor) AS valor_minimo,
    MAX(valor) AS valor_maximo,
    COUNT(*) AS cantidad_lecturas
  FROM sensores
  WHERE id_colmena = p_id_colmena 
    AND tipo = p_tipo
    AND fecha >= DATE_SUB(NOW(), INTERVAL p_horas HOUR)
  GROUP BY tipo;
END//
DELIMITER ;

-- PROCEDIMIENTOS PARA TABLA: PRODUCCION
-- 16. Registrar producción
DELIMITER //
CREATE PROCEDURE sp_registrar_produccion(
  IN p_id_colmena INT,
  IN p_cantidad_miel FLOAT,
  IN p_fecha DATE,
  OUT p_id_produccion INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al registrar producción';
    SET p_id_produccion = 0;
  END;
  
  INSERT INTO produccion (id_colmena, cantidad_miel, fecha)
  VALUES (p_id_colmena, p_cantidad_miel, p_fecha);
  
  SET p_id_produccion = LAST_INSERT_ID();
  SET p_resultado = 'Producción registrada exitosamente';
END//
DELIMITER ;

-- 17. Obtener producción por colmena
DELIMITER //
CREATE PROCEDURE sp_obtener_produccion_colmena(
  IN p_id_colmena INT
)
BEGIN
  SELECT id_produccion, cantidad_miel, fecha
  FROM produccion
  WHERE id_colmena = p_id_colmena
  ORDER BY fecha DESC;
END//
DELIMITER ;

-- 18. Obtener total de producción por periodo
DELIMITER //
CREATE PROCEDURE sp_obtener_produccion_periodo(
  IN p_id_colmena INT,
  IN p_fecha_inicio DATE,
  IN p_fecha_fin DATE
)
BEGIN
  SELECT 
    SUM(cantidad_miel) AS total_produccion,
    AVG(cantidad_miel) AS promedio_diario,
    COUNT(*) AS dias_registrados
  FROM produccion
  WHERE id_colmena = p_id_colmena
    AND fecha BETWEEN p_fecha_inicio AND p_fecha_fin;
END//
DELIMITER ;

-- 19. Obtener ranking de colmenas por producción
DELIMITER //
CREATE PROCEDURE sp_ranking_colmenas_produccion(
  IN p_fecha_inicio DATE,
  IN p_fecha_fin DATE
)
BEGIN
  SELECT 
    c.id_colmena,
    c.nombre,
    c.ubicacion,
    SUM(p.cantidad_miel) AS total_produccion,
    COUNT(p.id_produccion) AS registros
  FROM colmenas c
  LEFT JOIN produccion p ON c.id_colmena = p.id_colmena
    AND p.fecha BETWEEN p_fecha_inicio AND p_fecha_fin
  GROUP BY c.id_colmena, c.nombre, c.ubicacion
  ORDER BY total_produccion DESC;
END//
DELIMITER ;

-- PROCEDIMIENTOS PARA TABLA: ALERTAS
-- 20. Crear alerta
DELIMITER //
CREATE PROCEDURE sp_crear_alerta(
  IN p_id_colmena INT,
  IN p_tipo VARCHAR(100),
  IN p_descripcion TEXT,
  IN p_estado VARCHAR(50),
  OUT p_id_alerta INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al crear alerta';
    SET p_id_alerta = 0;
  END;
  
  INSERT INTO alertas (id_colmena, tipo, descripcion, estado)
  VALUES (p_id_colmena, p_tipo, p_descripcion, p_estado);
  
  SET p_id_alerta = LAST_INSERT_ID();
  SET p_resultado = 'Alerta creada exitosamente';
END//
DELIMITER ;

-- 21. Obtener alertas activas
DELIMITER //
CREATE PROCEDURE sp_obtener_alertas_activas()
BEGIN
  SELECT 
    a.id_alerta,
    a.id_colmena,
    a.tipo,
    a.descripcion,
    a.estado,
    a.fecha,
    c.nombre AS nombre_colmena
  FROM alertas a
  INNER JOIN colmenas c ON a.id_colmena = c.id_colmena
  WHERE a.estado = 'activa'
  ORDER BY a.fecha DESC;
END//
DELIMITER ;

-- 22. Obtener alertas de una colmena
DELIMITER //
CREATE PROCEDURE sp_obtener_alertas_colmena(
  IN p_id_colmena INT
)
BEGIN
  SELECT id_alerta, tipo, descripcion, estado, fecha
  FROM alertas
  WHERE id_colmena = p_id_colmena
  ORDER BY fecha DESC;
END//
DELIMITER ;

-- 23. Actualizar estado de alerta
DELIMITER //
CREATE PROCEDURE sp_actualizar_estado_alerta(
  IN p_id_alerta INT,
  IN p_estado VARCHAR(50),
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al actualizar alerta';
  END;
  
  UPDATE alertas
  SET estado = p_estado
  WHERE id_alerta = p_id_alerta;
  
  SET p_resultado = 'Alerta actualizada exitosamente';
END//
DELIMITER ;

-- 24. Eliminar alerta
DELIMITER //
CREATE PROCEDURE sp_eliminar_alerta(
  IN p_id_alerta INT,
  OUT p_resultado VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET p_resultado = 'Error al eliminar alerta';
  END;
  
  DELETE FROM alertas
  WHERE id_alerta = p_id_alerta;
  
  SET p_resultado = 'Alerta eliminada exitosamente';
END//
DELIMITER ;

-- PROCEDIMIENTOS PARA REPORTES Y ANÁLISIS
-- 25. Reporte general de colmena (CORREGIDO: Sin tabla usuario_colmena)
DELIMITER //
CREATE PROCEDURE sp_reporte_colmena(
  IN p_id_colmena INT
)
BEGIN
  SELECT 
    c.id_colmena,
    c.id_usuario,
    c.nombre,
    c.ubicacion,
    c.estado,
    COUNT(DISTINCT s.id_sensor) AS lecturas_sensores,
    COUNT(DISTINCT p.id_produccion) AS registros_produccion,
    COUNT(DISTINCT a.id_alerta) AS alertas_totales,
    SUM(p.cantidad_miel) AS total_miel_producida
  FROM colmenas c
  LEFT JOIN sensores s ON c.id_colmena = s.id_colmena
  LEFT JOIN produccion p ON c.id_colmena = p.id_colmena
  LEFT JOIN alertas a ON c.id_colmena = a.id_colmena
  WHERE c.id_colmena = p_id_colmena
  GROUP BY c.id_colmena, c.id_usuario, c.nombre, c.ubicacion, c.estado;
END//
DELIMITER ;

-- 26. Salud del sistema (CORREGIDO: Sin tabla usuario_colmena, mejorado)
DELIMITER //
CREATE PROCEDURE sp_salud_sistema()
BEGIN
  SELECT 
    COUNT(DISTINCT u.id_usuario) AS total_usuarios,
    COUNT(DISTINCT c.id_colmena) AS total_colmenas,
    COUNT(DISTINCT s.id_sensor) AS total_lecturas_sensores,
    COUNT(DISTINCT p.id_produccion) AS total_producciones,
    (SELECT COUNT(*) FROM alertas WHERE estado = 'activa') AS alertas_activas,
    COALESCE(SUM(p.cantidad_miel), 0) AS total_miel_producida_sistema
  FROM usuarios u
  LEFT JOIN colmenas c ON u.id_usuario = c.id_usuario
  LEFT JOIN sensores s ON c.id_colmena = s.id_colmena
  LEFT JOIN produccion p ON c.id_colmena = p.id_colmena;
END//
DELIMITER ;

-- 27. Alertas sin resolver en las últimas 24 horas (CORREGIDO: Mejorado)
DELIMITER //
CREATE PROCEDURE sp_alertas_sin_resolver()
BEGIN
  SELECT 
    a.id_alerta,
    a.id_colmena,
    a.tipo,
    a.descripcion,
    a.fecha,
    c.nombre AS nombre_colmena,
    TIMESTAMPDIFF(HOUR, a.fecha, NOW()) AS horas_sin_resolver
  FROM alertas a
  INNER JOIN colmenas c ON a.id_colmena = c.id_colmena
  WHERE a.estado != 'resuelta'
    AND a.fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
  ORDER BY a.fecha ASC;
END//
DELIMITER ;
