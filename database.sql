CREATE DATABASE preguntados;

CREATE TABLE usuario (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         nombre_completo VARCHAR(255) NOT NULL,
                         anio_nacimiento YEAR NOT NULL,
                         sexo ENUM('Masculino', 'Femenino', 'Prefiero no cargarlo') DEFAULT 'Prefiero no cargarlo',
                         pais VARCHAR(100) NOT NULL,
                         ciudad VARCHAR(100) NOT NULL,
                         email VARCHAR(180) NOT NULL UNIQUE,
                         password VARCHAR(255) NOT NULL,
                         username VARCHAR(50) NOT NULL UNIQUE,
                         foto_perfil VARCHAR(255) DEFAULT NULL,
                         codigo_validacion VARCHAR(64) DEFAULT NULL,
                         cuenta_activa BOOLEAN DEFAULT FALSE,
                         rol ENUM('usuario', 'editor', 'administrador') DEFAULT 'usuario',
                         fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            nombre VARCHAR(100) NOT NULL
);

CREATE TABLE preguntas (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           texto VARCHAR(255) NOT NULL,
                           categoria_id INT,
                           FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

CREATE TABLE respuestas (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            pregunta_id INT,
                            texto VARCHAR(255) NOT NULL,
                            es_correcta BOOLEAN DEFAULT FALSE,
                            FOREIGN KEY (pregunta_id) REFERENCES preguntas(id)
);

INSERT INTO categorias (nombre)
VALUES
    ('Historia'),
    ('Deporte'),
    ('Ciencia'),
    ('Geografía');

INSERT INTO preguntas (texto, categoria_id) VALUES
-- HISTORIA (id_categoria = 1)
('¿En qué año comenzó la Segunda Guerra Mundial?', 1),
('¿Quién fue el primer presidente de Estados Unidos?', 1),
('¿Qué civilización construyó las pirámides de Egipto?', 1),
('¿En qué año llegó Cristóbal Colón a América?', 1),

-- DEPORTE (id_categoria = 2)
('¿Cuántos jugadores tiene un equipo de fútbol en el campo?', 2),
('¿En qué deporte se utiliza una raqueta y una pelota amarilla?', 2),
('¿Qué país ganó el Mundial de Fútbol 2018?', 2),
('¿Qué atleta ganó más medallas olímpicas?', 2),

-- CIENCIA (id_categoria = 3)
('¿Cuál es el planeta más grande del sistema solar?', 3),
('¿Qué gas respiran los humanos para vivir?', 3),
('¿Quién formuló la teoría de la relatividad?', 3),
('¿Cuál es el símbolo químico del agua?', 3),

-- GEOGRAFÍA (id_categoria = 4)
('¿Cuál es el río más largo del mundo?', 4),
('¿Cuál es el país más grande del planeta?', 4),
('¿En qué continente se encuentra Egipto?', 4),
('¿Cuál es la capital de Australia?', 4);

INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
-- HISTORIA
(1, '1939', 1),
(1, '1914', 0),
(1, '1945', 0),

(2, 'George Washington', 1),
(2, 'Abraham Lincoln', 0),
(2, 'Thomas Jefferson', 0),

(3, 'Egipcia', 1),
(3, 'Romana', 0),
(3, 'Griega', 0),

(4, '1492', 1),
(4, '1500', 0),
(4, '1450', 0),

-- DEPORTE
(5, '11', 1),
(5, '10', 0),
(5, '9', 0),

(6, 'Tenis', 1),
(6, 'Golf', 0),
(6, 'Bádminton', 0),

(7, 'Francia', 1),
(7, 'Brasil', 0),
(7, 'Alemania', 0),

(8, 'Michael Phelps', 1),
(8, 'Usain Bolt', 0),
(8, 'Simone Biles', 0),

-- CIENCIA
(9, 'Júpiter', 1),
(9, 'Saturno', 0),
(9, 'Marte', 0),

(10, 'Oxígeno', 1),
(10, 'Dióxido de carbono', 0),
(10, 'Hidrógeno', 0),

(11, 'Albert Einstein', 1),
(11, 'Isaac Newton', 0),
(11, 'Nikola Tesla', 0),

(12, 'H₂O', 1),
(12, 'O₂', 0),
(12, 'CO₂', 0),

-- GEOGRAFÍA
(13, 'Nilo', 1),
(13, 'Amazonas', 0),
(13, 'Yangtsé', 0),

(14, 'Rusia', 1),
(14, 'Canadá', 0),
(14, 'China', 0),

(15, 'África', 1),
(15, 'Asia', 0),
(15, 'Europa', 0),

(16, 'Canberra', 1),
(16, 'Sídney', 0),
(16, 'Melbourne', 0);