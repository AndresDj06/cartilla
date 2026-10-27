-- Cartilla · esquema MySQL/MariaDB (utf8mb4)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS repasos, actividad, progreso, respuestas, intentos, inscripciones, ejercicios, lecciones, unidades, cursos, perfiles, usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- Personas. Quien llega sin cuenta es un usuario 'invitado' con token de cookie;
-- al registrarse se completa la misma fila y conserva todo su avance.
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NULL,
  usuario VARCHAR(40) NULL UNIQUE,
  correo VARCHAR(160) NULL UNIQUE,
  clave_hash VARCHAR(255) NULL,
  rol ENUM('invitado','estudiante','docente','superadmin') NOT NULL DEFAULT 'invitado',
  token_invitado CHAR(48) NULL UNIQUE,
  es_demo TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_ingreso DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respuestas del onboarding: deciden curso, nivel de partida, duración, meta y tipos de juego.
CREATE TABLE perfiles (
  usuario_id INT UNSIGNED PRIMARY KEY,
  rol_elegido ENUM('estudiante','docente') NULL,
  objetivo VARCHAR(40) NULL,
  curso_id INT UNSIGNED NULL,
  nivel VARCHAR(20) NULL,
  grado VARCHAR(20) NULL,
  minutos TINYINT UNSIGNED NULL,
  tipos VARCHAR(200) NULL,
  titulo_tema VARCHAR(120) NULL,
  texto_fuente MEDIUMTEXT NULL,
  completado TINYINT(1) NOT NULL DEFAULT 0,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_perfil_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cursos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  titulo VARCHAR(140) NOT NULL,
  materia VARCHAR(60) NOT NULL,
  descripcion VARCHAR(400) NOT NULL DEFAULT '',
  autor_id INT UNSIGNED NULL,
  codigo CHAR(6) NULL UNIQUE,
  grado VARCHAR(20) NULL,
  texto_fuente MEDIUMTEXT NULL,
  estado ENUM('borrador','publicado') NOT NULL DEFAULT 'publicado',
  catalogo TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_curso_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE unidades (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curso_id INT UNSIGNED NOT NULL,
  orden TINYINT UNSIGNED NOT NULL,
  titulo VARCHAR(140) NOT NULL,
  descripcion VARCHAR(300) NOT NULL DEFAULT '',
  CONSTRAINT fk_unidad_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
  INDEX (curso_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lecciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unidad_id INT UNSIGNED NOT NULL,
  orden TINYINT UNSIGNED NOT NULL,
  titulo VARCHAR(140) NOT NULL,
  CONSTRAINT fk_leccion_unidad FOREIGN KEY (unidad_id) REFERENCES unidades(id) ON DELETE CASCADE,
  INDEX (unidad_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tipos inspirados en Educaplay: test, si_no, completar, ordenar_palabras, ordenar_letras, relacionar.
CREATE TABLE ejercicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  leccion_id INT UNSIGNED NOT NULL,
  orden TINYINT UNSIGNED NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  enunciado TEXT NOT NULL,
  datos JSON NOT NULL,
  solucion JSON NOT NULL,
  explicacion TEXT NULL,
  fuente TEXT NULL,
  CONSTRAINT fk_ejercicio_leccion FOREIGN KEY (leccion_id) REFERENCES lecciones(id) ON DELETE CASCADE,
  INDEX (leccion_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inscripciones (
  usuario_id INT UNSIGNED NOT NULL,
  curso_id INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, curso_id),
  CONSTRAINT fk_insc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_insc_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE intentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  leccion_id INT UNSIGNED NULL,
  curso_id INT UNSIGNED NOT NULL,
  modo ENUM('leccion','repaso') NOT NULL DEFAULT 'leccion',
  ejercicios VARCHAR(400) NOT NULL,
  vidas TINYINT UNSIGNED NOT NULL DEFAULT 3,
  aciertos TINYINT UNSIGNED NOT NULL DEFAULT 0,
  total TINYINT UNSIGNED NOT NULL DEFAULT 0,
  xp SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  estado ENUM('en_curso','completado','sin_vidas') NOT NULL DEFAULT 'en_curso',
  iniciado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  terminado_en DATETIME NULL,
  CONSTRAINT fk_intento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_intento_leccion FOREIGN KEY (leccion_id) REFERENCES lecciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_intento_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
  INDEX (usuario_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE respuestas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  intento_id INT UNSIGNED NOT NULL,
  ejercicio_id INT UNSIGNED NOT NULL,
  respuesta TEXT NOT NULL,
  correcta TINYINT(1) NOT NULL,
  primera_vez TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resp_intento FOREIGN KEY (intento_id) REFERENCES intentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_resp_ejercicio FOREIGN KEY (ejercicio_id) REFERENCES ejercicios(id) ON DELETE CASCADE,
  INDEX (ejercicio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE progreso (
  usuario_id INT UNSIGNED NOT NULL,
  leccion_id INT UNSIGNED NOT NULL,
  veces SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  mejor_precision TINYINT UNSIGNED NOT NULL DEFAULT 0,
  completada_en DATETIME NULL,
  PRIMARY KEY (usuario_id, leccion_id),
  CONSTRAINT fk_prog_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_prog_leccion FOREIGN KEY (leccion_id) REFERENCES lecciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- XP por día: de aquí salen la racha y la meta diaria.
CREATE TABLE actividad (
  usuario_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  xp SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (usuario_id, fecha),
  CONSTRAINT fk_act_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Repaso espaciado (cajas de Leitner): lo que se falla vuelve al día siguiente.
CREATE TABLE repasos (
  usuario_id INT UNSIGNED NOT NULL,
  ejercicio_id INT UNSIGNED NOT NULL,
  caja TINYINT UNSIGNED NOT NULL DEFAULT 1,
  proxima DATE NOT NULL,
  PRIMARY KEY (usuario_id, ejercicio_id),
  CONSTRAINT fk_rep_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_rep_ejercicio FOREIGN KEY (ejercicio_id) REFERENCES ejercicios(id) ON DELETE CASCADE,
  INDEX (usuario_id, proxima)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
