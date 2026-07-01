CREATE DATABASE apitech;
USE apitech;

-- 1. TABLA: EMPRESAS
CREATE TABLE empresas (
id_empresa INT AUTO_INCREMENT PRIMARY KEY,
nit VARCHAR(20) UNIQUE NOT NULL,
nombre_empresa VARCHAR(150) NOT NULL,
estado VARCHAR(20) NOT NULL DEFAULT 'activa',
fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
INDEX idx_nit (nit)
);


-- 2. TABLA: USUARIOS
CREATE TABLE usuarios (
id_usuario INT AUTO_INCREMENT PRIMARY KEY,
id_empresa INT NOT NULL,
nombre VARCHAR(100) NOT NULL,
correo VARCHAR(100) UNIQUE NOT NULL,
password VARCHAR(255) NOT NULL,
rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE CASCADE,

INDEX idx_correo (correo),
INDEX idx_empresa (id_empresa)
);


-- 3. TABLA: COLMENAS
CREATE TABLE colmenas (
id_colmena INT AUTO_INCREMENT PRIMARY KEY,
id_usuario INT NOT NULL,
nombre VARCHAR(100) NOT NULL,
ubicacion VARCHAR(150),
estado VARCHAR(50) DEFAULT 'Estable',
fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,

INDEX idx_usuario (id_usuario)
);


-- 4. TABLA: SENSORES
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


-- 5. TABLA: PRODUCCIÓN
CREATE TABLE produccion (
id_produccion INT AUTO_INCREMENT PRIMARY KEY,
id_colmena INT NOT NULL,
cantidad_miel FLOAT NOT NULL,
fecha DATE NOT NULL,

FOREIGN KEY (id_colmena) REFERENCES colmenas(id_colmena) ON DELETE CASCADE,

INDEX idx_colmena (id_colmena),
INDEX idx_fecha (fecha)
);

-- 6. TABLA: ALERTAS
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


-- 7. TABLA: ACTIVIDAD
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


-- DATOS DE PRUEBA


-- Empresa
INSERT INTO empresas (nit, nombre_empresa) VALUES
('900123456-7', 'Apícola El Panal S.A.S');

-- Obtener ID empresa
SET @id_empresa = LAST_INSERT_ID();

-- Usuarios (clave: 123456 en bcrypt)
INSERT INTO usuarios (id_empresa, nombre, correo, password, rol) VALUES
(@id_empresa, 'Administrador Principal', '[admin@apitech.com](mailto:admin@apitech.com)',
'$2y$10$Te6Zz2ySDmoka3q.YY7MHO/C8GSzdtAvyp0Al/GA9ikVmacdShh3', 'admin'),

(@id_empresa, 'Usuario Operativo', '[usuario@apitech.com](mailto:usuario@apitech.com)',
'$2y$10$Te6Zz2ySDmoka3q.YY7MHO/C8GSzdtAvyp0Al/GA9ikVmacdShh3', 'usuario');


-- CONSULTAS ÚTILES

-- Últimas colmenas de un usuario
SELECT * FROM colmenas
WHERE id_usuario = 1
ORDER BY fecha_creacion DESC
LIMIT 10;

-- Producción con nombre de colmena
SELECT p.*, c.nombre
FROM produccion p
INNER JOIN colmenas c ON p.id_colmena = c.id_colmena
WHERE c.id_usuario = 1
ORDER BY p.fecha DESC
LIMIT 10;

-- Total producción
SELECT COUNT(*) as total
FROM produccion
WHERE id_colmena IN (
SELECT id_colmena
FROM colmenas
WHERE id_usuario = 1
);
