DROP DATABASE IF EXISTS UO300245_DB;
CREATE DATABASE UO300245_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE UO300245_DB;
CREATE USER IF NOT EXISTS 'DBUSER2026'@'localhost' IDENTIFIED BY 'DBPWD2026';
GRANT ALL PRIVILEGES ON UO300245_DB.* TO 'DBUSER2026'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email     VARCHAR(100) NOT NULL UNIQUE,
    password  VARCHAR(255) NOT NULL
);

CREATE TABLE tipos_recurso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

CREATE TABLE recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    plazas_totales INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    FOREIGN KEY (tipo_id) REFERENCES tipos_recurso(id)
);

CREATE TABLE presupuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    recurso_id INT NOT NULL,
    num_personas     INT           NOT NULL DEFAULT 1,
    precio_unitario  DECIMAL(10,2) NOT NULL,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    precio_total     DECIMAL(10,2) NOT NULL,
    precio_estimado DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (recurso_id) REFERENCES recursos(id)
);

CREATE TABLE reservas (
    id             INT  AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT  NOT NULL,
    recurso_id     INT  NOT NULL,
    presupuesto_id INT  NOT NULL,
    num_personas   INT  NOT NULL DEFAULT 1,
    fecha_reserva  DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado         ENUM('Confirmada','Anulada') DEFAULT 'Confirmada',
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(id),
    FOREIGN KEY (recurso_id)     REFERENCES recursos(id),
    FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id)

);

INSERT INTO tipos_recurso (nombre) VALUES
    ('Museo y patrimonio'),
    ('Ruta turistica'),
    ('Restaurante'),
    ('Hotel'),
    ('Actividad de naturaleza');

INSERT INTO recursos (tipo_id, nombre, descripcion, plazas_totales, precio, fecha_inicio, fecha_fin) VALUES
(
    1,
    'Visita guiada a la Catedral y el Santo Grial',
    'Recorre la impresionante Catedral de Valencia con un guia experto. Incluye el acceso a la capilla del Santo Caliz, donde se custodia el caliz que la tradicion identifica con el Santo Grial, la subida al Miguelete y la visita al Museo Catedralicio.',
    20,
    15.00,
    '2026-06-01 10:00:00',
    '2026-08-31 12:00:00'
),
(
    5,
    'Excursion en barca tradicional por La Albufera',
    'Paseo en barca de madera por el lago de La Albufera, parque natural declarado Reserva de la Biosfera. Avistamiento de aves, visita a los canales entre arrozales y contemplacion del famoso atardecer sobre el lago. Incluye guia naturalista.',
    12,
    28.00,
    '2026-05-15 16:00:00',
    '2026-10-15 20:00:00'
),
(
    2,
    'Ruta a pie por el Casco Historico: Barrios de la Ciudad',
    'Paseo guiado de 3 horas por los barrios historicos de Valencia: El Carmen, La Xerea y el Mercado. Visita exterior de la Lonja de la Seda (Patrimonio de la Humanidad), el Mercado Central y las Torres de Quart.',
    25,
    12.00,
    '2026-05-01 09:30:00',
    '2026-10-31 12:30:00'
),
(
    3,
    'Cena de paella valenciana en barraca tradicional',
    'Cena autentica en una barraca del siglo XIX a orillas de La Albufera. Menu completo con paella valenciana cocinada al fuego de lena de naranjo, all i pebre de anguilas, bunuelos de calabaza y agua de Valencia. Espectaculo de musica tradicional valenciana en vivo.',
    30,
    38.00,
    '2026-06-01 20:30:00',
    '2026-09-30 23:30:00'
),
(
    4,
    'Noche en hotel boutique 4* en la Ciudad de las Artes',
    'Estancia de una noche en habitacion doble con vistas al complejo de la Ciudad de las Artes y las Ciencias. Incluye desayuno buffet, acceso al spa, late check-out hasta las 14h y entrada para el Oceanografic (el mayor acuario de Europa).',
    10,
    125.00,
    '2026-05-01 14:00:00',
    '2026-12-31 12:00:00'
);
