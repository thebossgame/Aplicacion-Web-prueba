CREATE DATABASE sistema_usuarios;
USE sistema_usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    numero_casa VARCHAR(20) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE informacion_hogar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    numero_personas INT NOT NULL,
    tipo_gas VARCHAR(20),
    informacion_gas TEXT,
    comunidad VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE personas_hogar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hogar_id INT NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    correo VARCHAR(100),
    numero_telefono VARCHAR(20) NOT NULL,
    toma_medicinas TINYINT(1) DEFAULT 0,
    medicinas TEXT,
    problemas_medicos TINYINT(1) DEFAULT 0,
    problemas_detalle TEXT,
    FOREIGN KEY (hogar_id) REFERENCES informacion_hogar(id),
    INDEX idx_hogar_id (hogar_id)
);

CREATE TABLE