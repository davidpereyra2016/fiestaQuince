-- Script SQL para crear la base de datos de Fiesta de Quince Años
-- Ejecutar este script en phpMyAdmin o MySQL Workbench

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS fiesta_quince 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE fiesta_quince;

-- Tabla para las fotos de la galería
CREATE TABLE IF NOT EXISTS fotos_galeria (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único de la foto',
    nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo de imagen',
    url_imagen VARCHAR(500) NOT NULL COMMENT 'URL completa de la imagen',
    nombre_invitado VARCHAR(100) DEFAULT 'Anónimo' COMMENT 'Nombre del invitado que subió la foto',
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de subida',
    activa BOOLEAN DEFAULT TRUE COMMENT 'Si la foto está activa o eliminada',
    INDEX idx_fecha (fecha_subida),
    INDEX idx_activa (activa),
    INDEX idx_nombre_invitado (nombre_invitado)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci 
COMMENT='Tabla para almacenar las fotos de la galería de la fiesta';

-- Tabla para los códigos QR generados
CREATE TABLE IF NOT EXISTS codigos_qr (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del código QR',
    codigo_qr TEXT NOT NULL COMMENT 'Código QR generado',
    url_generada VARCHAR(500) NOT NULL COMMENT 'URL para la cual se generó el QR',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de generación del QR',
    usado BOOLEAN DEFAULT FALSE COMMENT 'Si el QR ya fue usado/mostrado',
    fecha_uso TIMESTAMP NULL COMMENT 'Fecha en que se marcó como usado',
    INDEX idx_usado (usado),
    INDEX idx_url (url_generada(255)),
    INDEX idx_fecha_generacion (fecha_generacion)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci 
COMMENT='Tabla para controlar los códigos QR generados';

-- Insertar algunos datos de ejemplo (opcional)
-- INSERT INTO fotos_galeria (nombre_archivo, url_imagen, nombre_invitado) VALUES
-- ('ejemplo1.jpg', 'imagenes/ejemplo1.jpg', 'María García'),
-- ('ejemplo2.jpg', 'imagenes/ejemplo2.jpg', 'Juan Pérez');

-- Mostrar las tablas creadas
SHOW TABLES;

-- Mostrar la estructura de las tablas
DESCRIBE fotos_galeria;
DESCRIBE codigos_qr;
