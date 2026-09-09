-- =========================================================
-- ApiTech MK5 - schema.sql
-- =========================================================
-- FUENTE DE VERDAD del modelo de persistencia del proyecto.
-- Este script debe ejecutarse en MySQL ANTES de arrancar la aplicacion
-- Spring Boot (spring.jpa.hibernate.ddl-auto=validate NO crea ni
-- modifica el esquema, solo lo valida contra las entidades @Entity).
--
-- No modificar la estructura de este script sin acuerdo del equipo:
-- las entidades JPA del proyecto se mapean exactamente contra estas
-- tablas, columnas y restricciones.
-- =========================================================

DROP DATABASE IF EXISTS apitech;
CREATE DATABASE apitech
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apitech;
-- =========================================================
-- 1. EMPRESAS
-- =========================================================
CREATE TABLE empresas (
    id_empresa INT AUTO_INCREMENT PRIMARY KEY,
    nit VARCHAR(20) UNIQUE NOT NULL,
    nombre_empresa VARCHAR(150) NOT NULL,
    correo VARCHAR(150),
    telefono VARCHAR(30),
    direccion VARCHAR(200),
    estado VARCHAR(20) NOT NULL DEFAULT 'activa',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nit (nit)
) ENGINE=InnoDB;
-- =========================================================
-- 2. ROLES, PERMISOS Y ROLES_PERMISOS (RBAC normalizado)
-- =========================================================
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(200)
) ENGINE=InnoDB;
-- NOTA: "Sistema" NO es una fila aqui. Es un actor automatico (ver tabla
-- auditoria.actor_tipo), nunca un rol asignable a un usuario que inicia sesion.
CREATE TABLE permisos (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    codigo_permiso VARCHAR(60) UNIQUE NOT NULL,
    descripcion VARCHAR(200)
) ENGINE=InnoDB;
CREATE TABLE roles_permisos (
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB;
-- =========================================================
-- 3. USUARIOS
-- =========================================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NULL,              -- NULL SOLO para Admin_ApiTech / Empleado_ApiTech
    id_rol INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    correo VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,   -- hash (bcrypt) generado por la aplicacion
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE RESTRICT,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
    INDEX idx_correo (correo),
    INDEX idx_empresa (id_empresa)
) ENGINE=InnoDB;
-- Regla de coherencia rol<->empresa aplicada con triggers (ver seccion de TRIGGERS).
-- =========================================================
-- 4. PLANES DE SUSCRIPCION, SUSCRIPCIONES Y PAGOS
-- =========================================================
CREATE TABLE planes_suscripcion (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    nombre_plan VARCHAR(100) NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    duracion_dias INT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;
CREATE TABLE suscripciones (
    id_suscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_plan INT NOT NULL,
    estado ENUM('PENDIENTE','ACTIVA','SUSPENDIDA','VENCIDA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    activo_uniq TINYINT GENERATED ALWAYS AS (IF(estado='ACTIVA',1,NULL)) STORED,
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    fecha_cancelacion DATE NULL,
    renovacion_automatica BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE RESTRICT,
    FOREIGN KEY (id_plan) REFERENCES planes_suscripcion(id_plan) ON DELETE RESTRICT,
    UNIQUE KEY uq_suscripcion_activa (id_empresa, activo_uniq),
    UNIQUE KEY uq_suscripcion_empresa (id_suscripcion, id_empresa),
    INDEX idx_empresa (id_empresa),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;
-- El historial se conserva: cada renovacion/cambio de plan es una fila nueva,
-- nunca se sobreescribe una suscripcion anterior.
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_suscripcion INT NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50),
    referencia VARCHAR(100),
    estado ENUM('PENDIENTE','VERIFICADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
    fecha_verificacion TIMESTAMP NULL,
    id_usuario_verifico INT NULL,     -- debe ser Admin_ApiTech o Empleado_ApiTech (regla de aplicacion)
    observaciones TEXT,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE RESTRICT,
    FOREIGN KEY (id_suscripcion) REFERENCES suscripciones(id_suscripcion) ON DELETE RESTRICT,
    CONSTRAINT fk_pago_suscripcion_empresa FOREIGN KEY (id_suscripcion, id_empresa) REFERENCES suscripciones(id_suscripcion, id_empresa),
    FOREIGN KEY (id_usuario_verifico) REFERENCES usuarios(id_usuario),
    INDEX idx_empresa (id_empresa),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;
-- =========================================================
-- 5. COLMENAS
-- =========================================================
CREATE TABLE colmenas (
    id_colmena INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_usuario_registro INT NULL,     -- quien la registro (informativo)
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(150),
    estado VARCHAR(50) NOT NULL DEFAULT 'Estable',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario_registro) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    -- clave unica compuesta: soporte para la FK compuesta de sensor_colmena
    UNIQUE KEY uq_colmena_empresa (id_colmena, id_empresa),
    INDEX idx_empresa (id_empresa)
) ENGINE=InnoDB;
-- =========================================================
-- 6. SENSORES (dispositivos, simulados o reales)
-- =========================================================
CREATE TABLE sensores (
    id_sensor INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    tipo ENUM('TEMPERATURA','HUMEDAD','PESO') NOT NULL,
    modelo VARCHAR(100),
    fabricante VARCHAR(100),
    es_simulado BOOLEAN NOT NULL DEFAULT TRUE,   -- FALSE = sensor fisico IoT (ESP32, etc.)
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_activacion TIMESTAMP NULL,
    fecha_desactivacion TIMESTAMP NULL,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE RESTRICT,
    -- clave unica compuesta: soporte para la FK compuesta de sensor_colmena
    UNIQUE KEY uq_sensor_empresa (id_sensor, id_empresa),
    INDEX idx_empresa (id_empresa),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB;
-- =========================================================
-- 7. ASOCIACION SENSOR-COLMENA (con historial + integridad empresa)
-- =========================================================
CREATE TABLE sensor_colmena (
    id_asociacion INT AUTO_INCREMENT PRIMARY KEY,
    id_sensor INT NOT NULL,
    id_colmena INT NOT NULL,
    id_empresa INT NOT NULL,          -- denormalizado a proposito: habilita las FK compuestas de abajo
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    activo_uniq TINYINT GENERATED ALWAYS AS (IF(activo = 1, 1, NULL)) STORED,
    FOREIGN KEY (id_sensor) REFERENCES sensores(id_sensor) ON DELETE RESTRICT,
    FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE RESTRICT,
    CONSTRAINT fk_sc_sensor_empresa
        FOREIGN KEY (id_sensor, id_empresa) REFERENCES sensores(id_sensor, id_empresa),
    CONSTRAINT fk_sc_colmena_empresa
        FOREIGN KEY (id_colmena, id_empresa) REFERENCES colmenas(id_colmena, id_empresa),
    UNIQUE KEY uq_sensor_activo (id_sensor, activo_uniq),
    INDEX idx_sensor (id_sensor),
    INDEX idx_colmena (id_colmena),
    INDEX idx_empresa (id_empresa)
) ENGINE=InnoDB;
-- =========================================================
-- 8. MONITOREO (activacion sobre una asociacion sensor-colmena)
-- =========================================================
CREATE TABLE monitoreo (
    id_monitoreo INT AUTO_INCREMENT PRIMARY KEY,
    id_asociacion INT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,
    activo_uniq TINYINT GENERATED ALWAYS AS (IF(estado='activo',1,NULL)) STORED,
    FOREIGN KEY (id_asociacion) REFERENCES sensor_colmena(id_asociacion) ON DELETE RESTRICT,
    UNIQUE KEY uq_monitoreo_activo (id_asociacion, activo_uniq),
    INDEX idx_asociacion (id_asociacion)
) ENGINE=InnoDB;
-- =========================================================
-- 9. RANGOS Y UMBRALES
-- =========================================================
CREATE TABLE rangos_umbrales (
    id_umbral INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NULL,   -- NULL = umbral global por defecto de ApiTech
    tipo_medicion ENUM('TEMPERATURA','HUMEDAD','PESO') NOT NULL,
    valor_min FLOAT NOT NULL,
    valor_max FLOAT NOT NULL,
    unidad VARCHAR(10) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    fecha_configuracion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_empresa_norm INT GENERATED ALWAYS AS (IFNULL(id_empresa, 0)) STORED,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE RESTRICT,
    CONSTRAINT chk_umbral_rango CHECK (valor_min < valor_max),
    UNIQUE KEY uq_umbral_empresa_tipo (id_empresa_norm, tipo_medicion)
) ENGINE=InnoDB;
-- =========================================================
-- 10. MEDICIONES (historial de lecturas)
-- =========================================================
CREATE TABLE mediciones (
    id_medicion BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_asociacion INT NOT NULL,
    tipo_medicion ENUM('TEMPERATURA','HUMEDAD','PESO') NOT NULL,
    valor FLOAT NOT NULL,
    unidad VARCHAR(10) NOT NULL,
    origen ENUM('SIMULADO','REAL') NOT NULL DEFAULT 'SIMULADO',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_asociacion) REFERENCES sensor_colmena(id_asociacion) ON DELETE RESTRICT,
    INDEX idx_asociacion (id_asociacion),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo_fecha (tipo_medicion, fecha)
) ENGINE=InnoDB;
-- =========================================================
-- 11. ALERTAS
-- =========================================================
CREATE TABLE alertas (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    id_colmena INT NOT NULL,
    id_empresa INT NOT NULL,
    id_medicion BIGINT NULL,
    id_sensor INT NULL,
    tipo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    valor_detectado FLOAT NULL,
    umbral_min FLOAT NULL,
    umbral_max FLOAT NULL,
    severidad VARCHAR(20) NOT NULL DEFAULT 'media',
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    id_usuario_resolucion INT NULL,
    FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE RESTRICT,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa),
    CONSTRAINT fk_alerta_colmena_empresa FOREIGN KEY (id_colmena, id_empresa) REFERENCES colmenas(id_colmena, id_empresa),
    FOREIGN KEY (id_medicion) REFERENCES mediciones(id_medicion),
    FOREIGN KEY (id_sensor) REFERENCES sensores(id_sensor),
    FOREIGN KEY (id_usuario_resolucion) REFERENCES usuarios(id_usuario),
    INDEX idx_colmena (id_colmena),
    INDEX idx_empresa (id_empresa),
    INDEX idx_estado (estado),
    INDEX idx_severidad_estado (severidad, estado)
) ENGINE=InnoDB;
-- =========================================================
-- 12. NOTIFICACIONES
-- =========================================================
CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
      id_usuario INT NOT NULL,
    id_empresa INT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT,
    leida BOOLEAN NOT NULL DEFAULT FALSE,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_alerta INT NULL,
    id_pago INT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa),
    FOREIGN KEY (id_alerta) REFERENCES alertas(id_alerta) ON DELETE SET NULL,
    FOREIGN KEY (id_pago) REFERENCES pagos(id_pago) ON DELETE SET NULL,
    INDEX idx_usuario (id_usuario),
    INDEX idx_empresa (id_empresa),
    INDEX idx_leida (leida)
) ENGINE=InnoDB;
-- =========================================================
-- 13. PRODUCCION DE MIEL
-- =========================================================
CREATE TABLE produccion_miel (
    id_produccion INT AUTO_INCREMENT PRIMARY KEY,
    id_colmena INT NOT NULL,
    id_empresa INT NOT NULL,
    cantidad FLOAT NOT NULL,
    unidad VARCHAR(10) NOT NULL DEFAULT 'kg',
    fecha DATE NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE RESTRICT,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa),
    CONSTRAINT fk_produccion_colmena_empresa FOREIGN KEY (id_colmena, id_empresa) REFERENCES colmenas(id_colmena, id_empresa),
    INDEX idx_colmena (id_colmena),
    INDEX idx_empresa_fecha (id_empresa, fecha),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;
-- =========================================================
-- 14. AUDITORIA (usuario o actor automatico "Sistema")
-- =========================================================
CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    actor_tipo ENUM('USUARIO','SISTEMA') NOT NULL DEFAULT 'USUARIO',
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50),
    entidad_tipo VARCHAR(50) NULL,
    id_entidad BIGINT NULL,
    registro_afectado VARCHAR(100),
    descripcion TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    CONSTRAINT chk_auditoria_actor CHECK ((actor_tipo='SISTEMA' AND id_usuario IS NULL) OR (actor_tipo='USUARIO' AND id_usuario IS NOT NULL)),
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha),
    INDEX idx_entidad (entidad_tipo, id_entidad)
) ENGINE=InnoDB;
-- =========================================================
-- TRIGGERS: reglas de negocio que cruzan varias tablas
-- =========================================================
DELIMITER $$
CREATE TRIGGER trg_usuarios_empresa_ins BEFORE INSERT ON usuarios
FOR EACH ROW
BEGIN
  DECLARE v_nombre_rol VARCHAR(50);
  SELECT nombre_rol INTO v_nombre_rol FROM roles WHERE id_rol = NEW.id_rol;
  IF v_nombre_rol IN ('Admin_ApiTech','Empleado_ApiTech') AND NEW.id_empresa IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuarios ApiTech no deben tener id_empresa asignado';
  END IF;
  IF v_nombre_rol IN ('Admin_Cliente','Empleado_Cliente') AND NEW.id_empresa IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuarios cliente requieren id_empresa';
  END IF;
END$$
CREATE TRIGGER trg_usuarios_empresa_upd BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
  DECLARE v_nombre_rol VARCHAR(50);
  SELECT nombre_rol INTO v_nombre_rol FROM roles WHERE id_rol = NEW.id_rol;
  IF v_nombre_rol IN ('Admin_ApiTech','Empleado_ApiTech') AND NEW.id_empresa IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuarios ApiTech no deben tener id_empresa asignado';
  END IF;
  IF v_nombre_rol IN ('Admin_Cliente','Empleado_Cliente') AND NEW.id_empresa IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuarios cliente requieren id_empresa';
  END IF;
END$$
CREATE TRIGGER trg_monitoreo_ins BEFORE INSERT ON monitoreo
FOR EACH ROW
BEGIN
  DECLARE v_asociacion_activa BOOLEAN;
  DECLARE v_sensor_estado VARCHAR(20);
  SELECT sc.activo, s.estado INTO v_asociacion_activa, v_sensor_estado
  FROM sensor_colmena sc JOIN sensores s ON s.id_sensor = sc.id_sensor
  WHERE sc.id_asociacion = NEW.id_asociacion;
  IF NEW.estado = 'activo' AND (v_asociacion_activa = FALSE OR v_sensor_estado <> 'activo') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede activar monitoreo: asociacion o sensor inactivos';
  END IF;
END$$
CREATE TRIGGER trg_monitoreo_upd BEFORE UPDATE ON monitoreo
FOR EACH ROW
BEGIN
  DECLARE v_asociacion_activa BOOLEAN;
  DECLARE v_sensor_estado VARCHAR(20);
  SELECT sc.activo, s.estado INTO v_asociacion_activa, v_sensor_estado
  FROM sensor_colmena sc JOIN sensores s ON s.id_sensor = sc.id_sensor
  WHERE sc.id_asociacion = NEW.id_asociacion;
  IF NEW.estado = 'activo' AND (v_asociacion_activa = FALSE OR v_sensor_estado <> 'activo') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede activar monitoreo: asociacion o sensor inactivos';
  END IF;
END$$
CREATE TRIGGER trg_alertas_empresa_bi BEFORE INSERT ON alertas
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  SELECT id_empresa INTO v_empresa FROM colmenas WHERE id_colmena = NEW.id_colmena;
  IF NEW.id_empresa IS NULL THEN SET NEW.id_empresa = v_empresa; END IF;
  IF NEW.id_empresa <> v_empresa THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La alerta debe pertenecer a la empresa de su colmena';
  END IF;
END$$
CREATE TRIGGER trg_alertas_empresa_bu BEFORE UPDATE ON alertas
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  SELECT id_empresa INTO v_empresa FROM colmenas WHERE id_colmena = NEW.id_colmena;
  IF NEW.id_empresa IS NULL OR NEW.id_empresa <> v_empresa THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La alerta debe pertenecer a la empresa de su colmena';
  END IF;
END$$
CREATE TRIGGER trg_prod_empresa_bi BEFORE INSERT ON produccion_miel
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  SELECT id_empresa INTO v_empresa FROM colmenas WHERE id_colmena = NEW.id_colmena;
  IF NEW.id_empresa IS NULL THEN SET NEW.id_empresa = v_empresa; END IF;
  IF NEW.id_empresa <> v_empresa THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La produccion debe pertenecer a la empresa de su colmena';
  END IF;
END$$
CREATE TRIGGER trg_prod_empresa_bu BEFORE UPDATE ON produccion_miel
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  SELECT id_empresa INTO v_empresa FROM colmenas WHERE id_colmena = NEW.id_colmena;
  IF NEW.id_empresa IS NULL OR NEW.id_empresa <> v_empresa THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La produccion debe pertenecer a la empresa de su colmena';
  END IF;
END$$
CREATE TRIGGER trg_notif_empresa_bi BEFORE INSERT ON notificaciones
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  SELECT id_empresa INTO v_empresa FROM usuarios WHERE id_usuario = NEW.id_usuario;
  IF v_empresa IS NULL AND NEW.id_empresa IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un usuario ApiTech no puede recibir notificacion de empresa';
  END IF;
  IF v_empresa IS NOT NULL AND NEW.id_empresa IS NULL THEN SET NEW.id_empresa = v_empresa; END IF;
  IF v_empresa IS NOT NULL AND NEW.id_empresa <> v_empresa THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La notificacion debe pertenecer a la empresa del usuario';
  END IF;
END$$
CREATE TRIGGER trg_notif_empresa_bu BEFORE UPDATE ON notificaciones
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  SELECT id_empresa INTO v_empresa FROM usuarios WHERE id_usuario = NEW.id_usuario;
  IF v_empresa IS NULL AND NEW.id_empresa IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un usuario ApiTech no puede recibir notificacion de empresa';
  END IF;
  IF v_empresa IS NOT NULL AND (NEW.id_empresa IS NULL OR NEW.id_empresa <> v_empresa) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La notificacion debe pertenecer a la empresa del usuario';
  END IF;
END$$
CREATE TRIGGER trg_umbral_bi BEFORE INSERT ON rangos_umbrales
FOR EACH ROW
BEGIN
  IF NEW.valor_min >= NEW.valor_max THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'valor_min debe ser menor que valor_max';
  END IF;
END$$
CREATE TRIGGER trg_umbral_bu BEFORE UPDATE ON rangos_umbrales
FOR EACH ROW
BEGIN
  IF NEW.valor_min >= NEW.valor_max THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'valor_min debe ser menor que valor_max';
  END IF;
END$$
CREATE TRIGGER trg_mediciones_coherencia_bi BEFORE INSERT ON mediciones
FOR EACH ROW
BEGIN
  DECLARE v_tipo ENUM('TEMPERATURA','HUMEDAD','PESO');
  DECLARE v_simulado BOOLEAN;
  DECLARE v_monitoreo INT;
  SELECT COUNT(*) INTO v_monitoreo FROM monitoreo WHERE id_asociacion=NEW.id_asociacion AND estado='activo';
  IF v_monitoreo=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se puede registrar medicion sin monitoreo activo'; END IF;
  SELECT s.tipo, s.es_simulado INTO v_tipo, v_simulado
  FROM sensor_colmena sc JOIN sensores s ON s.id_sensor=sc.id_sensor
  WHERE sc.id_asociacion=NEW.id_asociacion;
  IF v_tipo IS NULL OR v_tipo <> NEW.tipo_medicion THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El tipo de medicion no coincide con el sensor asociado';
  END IF;
  IF (v_simulado=TRUE AND NEW.origen<>'SIMULADO') OR (v_simulado=FALSE AND NEW.origen<>'REAL') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El origen de la medicion no coincide con el sensor';
  END IF;
END$$
CREATE TRIGGER trg_mediciones_coherencia_bu BEFORE UPDATE ON mediciones
FOR EACH ROW
BEGIN
  DECLARE v_tipo ENUM('TEMPERATURA','HUMEDAD','PESO');
  DECLARE v_simulado BOOLEAN;
  DECLARE v_monitoreo INT;
  SELECT COUNT(*) INTO v_monitoreo FROM monitoreo WHERE id_asociacion=NEW.id_asociacion AND estado='activo';
  IF v_monitoreo=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se puede registrar medicion sin monitoreo activo'; END IF;
  SELECT s.tipo, s.es_simulado INTO v_tipo, v_simulado
  FROM sensor_colmena sc JOIN sensores s ON s.id_sensor=sc.id_sensor
  WHERE sc.id_asociacion=NEW.id_asociacion;
  IF v_tipo IS NULL OR v_tipo <> NEW.tipo_medicion THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El tipo de medicion no coincide con el sensor asociado';
  END IF;
  IF (v_simulado=TRUE AND NEW.origen<>'SIMULADO') OR (v_simulado=FALSE AND NEW.origen<>'REAL') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El origen de la medicion no coincide con el sensor';
  END IF;
END$$
CREATE TRIGGER trg_alertas_integridad_bi BEFORE INSERT ON alertas
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT; DECLARE v_sensor INT; DECLARE v_colmena INT;
  SELECT id_empresa INTO v_empresa FROM colmenas WHERE id_colmena=NEW.id_colmena;
  IF NEW.id_empresa<>v_empresa THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Empresa de alerta incompatible con colmena'; END IF;
  IF NEW.id_sensor IS NOT NULL THEN
    SELECT id_sensor, id_colmena INTO v_sensor, v_colmena FROM sensor_colmena WHERE id_sensor=NEW.id_sensor AND id_colmena=NEW.id_colmena AND activo=TRUE LIMIT 1;
    IF v_sensor IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Sensor de alerta incompatible con colmena'; END IF;
  END IF;
  IF NEW.id_medicion IS NOT NULL THEN
    SELECT sc.id_sensor, sc.id_colmena INTO v_sensor, v_colmena
    FROM mediciones m JOIN sensor_colmena sc ON sc.id_asociacion=m.id_asociacion
    WHERE m.id_medicion=NEW.id_medicion;
    IF v_colmena<>NEW.id_colmena OR (NEW.id_sensor IS NOT NULL AND v_sensor<>NEW.id_sensor) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicion de alerta incompatible con sensor o colmena';
    END IF;
  END IF;
END$$
CREATE TRIGGER trg_alertas_integridad_bu BEFORE UPDATE ON alertas
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT; DECLARE v_sensor INT; DECLARE v_colmena INT;
  SELECT id_empresa INTO v_empresa FROM colmenas WHERE id_colmena=NEW.id_colmena;
  IF NEW.id_empresa<>v_empresa THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Empresa de alerta incompatible con colmena'; END IF;
  IF NEW.id_sensor IS NOT NULL THEN
    SELECT id_sensor, id_colmena INTO v_sensor, v_colmena FROM sensor_colmena WHERE id_sensor=NEW.id_sensor AND id_colmena=NEW.id_colmena AND activo=TRUE LIMIT 1;
    IF v_sensor IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Sensor de alerta incompatible con colmena'; END IF;
  END IF;
  IF NEW.id_medicion IS NOT NULL THEN
    SELECT sc.id_sensor, sc.id_colmena INTO v_sensor, v_colmena FROM mediciones m JOIN sensor_colmena sc ON sc.id_asociacion=m.id_asociacion WHERE m.id_medicion=NEW.id_medicion;
    IF v_colmena<>NEW.id_colmena OR (NEW.id_sensor IS NOT NULL AND v_sensor<>NEW.id_sensor) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicion de alerta incompatible con sensor o colmena'; END IF;
  END IF;
END$$
CREATE TRIGGER trg_auditoria_actor_bi BEFORE INSERT ON auditoria
FOR EACH ROW
BEGIN
  IF (NEW.actor_tipo='SISTEMA' AND NEW.id_usuario IS NOT NULL) OR (NEW.actor_tipo='USUARIO' AND NEW.id_usuario IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Actor de auditoria incompatible con id_usuario';
  END IF;
END$$
CREATE TRIGGER trg_notificaciones_referencias_bi BEFORE INSERT ON notificaciones
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  IF NEW.id_alerta IS NOT NULL THEN
    SELECT id_empresa INTO v_empresa FROM alertas WHERE id_alerta=NEW.id_alerta;
    IF NEW.id_empresa IS NULL OR NEW.id_empresa<>v_empresa THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Alerta de notificacion incompatible con su empresa'; END IF;
  END IF;
  IF NEW.id_pago IS NOT NULL AND NEW.id_empresa IS NOT NULL THEN
    SELECT id_empresa INTO v_empresa FROM pagos WHERE id_pago=NEW.id_pago;
    IF v_empresa<>NEW.id_empresa THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pago de notificacion incompatible con su empresa'; END IF;
  END IF;
END$$
CREATE TRIGGER trg_notificaciones_referencias_bu BEFORE UPDATE ON notificaciones
FOR EACH ROW
BEGIN
  DECLARE v_empresa INT;
  IF NEW.id_alerta IS NOT NULL THEN
    SELECT id_empresa INTO v_empresa FROM alertas WHERE id_alerta=NEW.id_alerta;
    IF NEW.id_empresa IS NULL OR NEW.id_empresa<>v_empresa THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Alerta de notificacion incompatible con su empresa'; END IF;
  END IF;
  IF NEW.id_pago IS NOT NULL AND NEW.id_empresa IS NOT NULL THEN
    SELECT id_empresa INTO v_empresa FROM pagos WHERE id_pago=NEW.id_pago;
    IF v_empresa<>NEW.id_empresa THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pago de notificacion incompatible con su empresa'; END IF;
  END IF;
END$$
CREATE TRIGGER trg_sensores_estado_bu BEFORE UPDATE ON sensores
FOR EACH ROW
BEGIN
  IF NEW.estado<>'activo' AND EXISTS (SELECT 1 FROM sensor_colmena sc JOIN monitoreo m ON m.id_asociacion=sc.id_asociacion WHERE sc.id_sensor=NEW.id_sensor AND m.estado='activo') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se puede desactivar un sensor con monitoreo activo';
  END IF;
END$$
CREATE TRIGGER trg_sensor_colmena_estado_bu BEFORE UPDATE ON sensor_colmena
FOR EACH ROW
BEGIN
  IF NEW.activo=FALSE AND EXISTS (SELECT 1 FROM monitoreo WHERE id_asociacion=NEW.id_asociacion AND estado='activo') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se puede desactivar una asociacion con monitoreo activo';
  END IF;
END$$
CREATE TRIGGER trg_pagos_verificador_bi BEFORE INSERT ON pagos
FOR EACH ROW
BEGIN
  DECLARE v_rol VARCHAR(50);
  IF NEW.id_usuario_verifico IS NOT NULL THEN
    SELECT r.nombre_rol INTO v_rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=NEW.id_usuario_verifico;
    IF v_rol NOT IN ('Admin_ApiTech','Empleado_ApiTech') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Solo ApiTech puede verificar pagos'; END IF;
  END IF;
END$$
CREATE TRIGGER trg_pagos_verificador_bu BEFORE UPDATE ON pagos
FOR EACH ROW
BEGIN
  DECLARE v_rol VARCHAR(50);
  IF NEW.id_usuario_verifico IS NOT NULL THEN
    SELECT r.nombre_rol INTO v_rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=NEW.id_usuario_verifico;
    IF v_rol NOT IN ('Admin_ApiTech','Empleado_ApiTech') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Solo ApiTech puede verificar pagos'; END IF;
  END IF;
END$$
CREATE TRIGGER trg_monitoreo_empresa_bi BEFORE INSERT ON monitoreo
FOR EACH ROW
BEGIN
  DECLARE v_activo BOOLEAN; DECLARE v_sensor_estado VARCHAR(20); DECLARE v_colmena_estado VARCHAR(50);
  SELECT sc.activo,s.estado,c.estado INTO v_activo,v_sensor_estado,v_colmena_estado
  FROM sensor_colmena sc JOIN sensores s ON s.id_sensor=sc.id_sensor JOIN colmenas c ON c.id_colmena=sc.id_colmena
  WHERE sc.id_asociacion=NEW.id_asociacion;
  IF NEW.estado='activo' AND (v_activo IS NULL OR v_activo=FALSE OR v_sensor_estado<>'activo' OR v_colmena_estado<>'Estable') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Monitoreo requiere asociacion, sensor y colmena activos'; END IF;
END$$
CREATE TRIGGER trg_monitoreo_empresa_bu BEFORE UPDATE ON monitoreo
FOR EACH ROW
BEGIN
  DECLARE v_activo BOOLEAN; DECLARE v_sensor_estado VARCHAR(20); DECLARE v_colmena_estado VARCHAR(50);
  SELECT sc.activo,s.estado,c.estado INTO v_activo,v_sensor_estado,v_colmena_estado
  FROM sensor_colmena sc JOIN sensores s ON s.id_sensor=sc.id_sensor JOIN colmenas c ON c.id_colmena=sc.id_colmena
  WHERE sc.id_asociacion=NEW.id_asociacion;
  IF NEW.estado='activo' AND (v_activo IS NULL OR v_activo=FALSE OR v_sensor_estado<>'activo' OR v_colmena_estado<>'Estable') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Monitoreo requiere asociacion, sensor y colmena activos'; END IF;
END$$
DELIMITER ;

-- =========================================================
-- DATOS MINIMOS DE ARRANQUE (seed) para poder probar la etapa 1
-- =========================================================
-- Catalogo de roles requerido por las reglas de negocio del sistema.
INSERT INTO roles (nombre_rol, descripcion) VALUES
  ('Admin_ApiTech', 'Administrador de la plataforma ApiTech (sin empresa asociada)'),
  ('Empleado_ApiTech', 'Empleado operativo de ApiTech (sin empresa asociada)'),
  ('Admin_Cliente', 'Administrador de una empresa cliente'),
  ('Empleado_Cliente', 'Empleado operativo de una empresa cliente');

-- Un permiso de ejemplo, para poder probar el repositorio de Permiso.
INSERT INTO permisos (codigo_permiso, descripcion) VALUES
  ('GESTIONAR_USUARIOS', 'Permite crear, editar y eliminar usuarios de la propia empresa');

-- Una empresa de ejemplo, para poder probar el repositorio de Empresa.
INSERT INTO empresas (nit, nombre_empresa, correo, telefono, direccion, estado) VALUES
  ('900123456-7', 'Apicola El Panal S.A.S', 'contacto@elpanal.com', '3001234567', 'Cra 10 # 20-30, Bogota', 'activa');
