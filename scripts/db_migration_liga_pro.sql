-- Liga Pro migration script (idempotent)
-- Target database: liga_futbol_pro

USE liga_futbol_pro;

-- 1) Ensure dedicated auth table exists and has required structure.
CREATE TABLE IF NOT EXISTS usuarios_auth (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure must_change_password exists in usuarios_auth.
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'liga_futbol_pro'
      AND TABLE_NAME = 'usuarios_auth'
      AND COLUMN_NAME = 'must_change_password'
);
SET @sql_col := IF(
    @col_exists = 0,
    'ALTER TABLE usuarios_auth ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT ''Column must_change_password already exists in usuarios_auth'' AS info'
);
PREPARE stmt_col FROM @sql_col;
EXECUTE stmt_col;
DEALLOCATE PREPARE stmt_col;

-- Ensure username unique index exists.
SET @idx_user_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'liga_futbol_pro'
      AND TABLE_NAME = 'usuarios_auth'
      AND INDEX_NAME = 'username'
);
SET @sql_idx_user := IF(
    @idx_user_exists = 0,
    'ALTER TABLE usuarios_auth ADD UNIQUE KEY username (username)',
    'SELECT ''Unique index username already exists in usuarios_auth'' AS info'
);
PREPARE stmt_idx_user FROM @sql_idx_user;
EXECUTE stmt_idx_user;
DEALLOCATE PREPARE stmt_idx_user;

-- Standardize collation/charset for usuarios_auth.
ALTER TABLE usuarios_auth CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE usuarios_auth DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 2) Ensure player shirt numbers are unique per team.
SET @idx_jug_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'liga_futbol_pro'
      AND TABLE_NAME = 'jugadores'
      AND INDEX_NAME = 'uq_jugadores_equipo_dorsal'
);
SET @sql_idx_jug := IF(
    @idx_jug_exists = 0,
    'ALTER TABLE jugadores ADD UNIQUE KEY uq_jugadores_equipo_dorsal (id_equipo, dorsal)',
    'SELECT ''Unique index uq_jugadores_equipo_dorsal already exists'' AS info'
);
PREPARE stmt_idx_jug FROM @sql_idx_jug;
EXECUTE stmt_idx_jug;
DEALLOCATE PREPARE stmt_idx_jug;

-- 3) Ensure local and away teams are different.
SET @chk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = 'liga_futbol_pro'
      AND TABLE_NAME = 'partidos'
      AND CONSTRAINT_NAME = 'chk_partidos_equipos_distintos'
      AND CONSTRAINT_TYPE = 'CHECK'
);
SET @sql_chk := IF(
    @chk_exists = 0,
    'ALTER TABLE partidos ADD CONSTRAINT chk_partidos_equipos_distintos CHECK (id_equipo_local <> id_equipo_visitante)',
    'SELECT ''CHECK chk_partidos_equipos_distintos already exists'' AS info'
);
PREPARE stmt_chk FROM @sql_chk;
EXECUTE stmt_chk;
DEALLOCATE PREPARE stmt_chk;

-- 4) Optional seed admin user if missing.
SET @admin_exists := (
    SELECT COUNT(*)
    FROM usuarios_auth
    WHERE username = 'admin'
);
SET @admin_hash := '$2y$10$5J4Rzkx7zNhl0aONh5do5.8M5YqvW2xGNO8Q0P8eb9fJj5z54C1QK';
SET @sql_admin := IF(
    @admin_exists = 0,
    CONCAT(
      'INSERT INTO usuarios_auth (username, password_hash, must_change_password) VALUES (''admin'', ''',
      @admin_hash,
      ''', 1)'
    ),
    'SELECT ''Admin user already exists in usuarios_auth'' AS info'
);
PREPARE stmt_admin FROM @sql_admin;
EXECUTE stmt_admin;
DEALLOCATE PREPARE stmt_admin;

-- Notes:
-- - Temporary admin password for this hash is: Admin123!
-- - The app forces password change on first login.
