CREATE DATABASE apitech;
USE apitech;

-- Primero: crear tabla usuarios
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Segundo: crear tabla colmenas
CREATE TABLE colmenas (
  id_colmena INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  ubicacion VARCHAR(150),
  estado VARCHAR(50) DEFAULT 'Estable',
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Tercero: crear tabla sensores
CREATE TABLE sensores (
  id_sensor INT AUTO_INCREMENT PRIMARY KEY,
  id_colmena INT NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  valor FLOAT,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE CASCADE,
  INDEX idx_colmena (id_colmena),
  INDEX idx_fecha (fecha)
);

-- Cuarto: crear tabla produccion
CREATE TABLE produccion (
  id_produccion INT AUTO_INCREMENT PRIMARY KEY,
  id_colmena INT NOT NULL,
  cantidad_miel FLOAT NOT NULL,
  fecha DATE NOT NULL,
  FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE CASCADE,
  INDEX idx_colmena (id_colmena),
  INDEX idx_fecha (fecha)
);

-- Quinto: crear tabla alertas
CREATE TABLE alertas (
  id_alerta INT AUTO_INCREMENT PRIMARY KEY,
  id_colmena INT NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  descripcion TEXT,
  estado VARCHAR(50) DEFAULT 'activa',
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE CASCADE,
  INDEX idx_colmena (id_colmena),
  INDEX idx_estado (estado)
);

-- Tabla de historial de actividad
CREATE TABLE actividad (
  id_actividad INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  descripcion TEXT,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_usuario (id_usuario),
  INDEX idx_fecha (fecha)
);

-- Agregar índices importantes
ALTER TABLE usuarios ADD INDEX idx_correo (correo);
ALTER TABLE colmenas ADD INDEX idx_usuario (id_usuario);

-- Ver colmenas importadas (últimas 10)
SELECT * FROM colmenas WHERE id_usuario = ? ORDER BY fecha_creacion DESC LIMIT 10;

-- Ver producción importada (últimas 10)
SELECT p.*, c.nombre FROM produccion p 
INNER JOIN colmenas c ON p.id_colmena = c.id_colmena 
WHERE c.id_usuario = ? 
ORDER BY p.fecha DESC LIMIT 10;

-- Contar total de registros
SELECT COUNT(*) as total FROM produccion WHERE id_colmena IN (
    SELECT id_colmena FROM colmenas WHERE id_usuario = ?
);