<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function ensure_usuarios_schema($conn) {
    $sqlTabla = "CREATE TABLE IF NOT EXISTS usuarios_auth (
        id_usuario INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        must_change_password TINYINT(1) NOT NULL DEFAULT 1,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$conn->query($sqlTabla)) {
        return false;
    }

    $columna = $conn->query("SHOW COLUMNS FROM usuarios_auth LIKE 'must_change_password'");
    if (!$columna) {
        return false;
    }

    if ($columna->num_rows === 0) {
        if (!$conn->query("ALTER TABLE usuarios_auth ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1")) {
            return false;
        }
    }

    return true;
}

function redirect($ruta) {
    header('Location: ' . $ruta);
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_regenerate_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_validate_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']);
}

function is_authenticated() {
    return !empty($_SESSION['auth_user_id']);
}

function must_change_password() {
    return !empty($_SESSION['must_change_password']);
}

function enforce_password_change() {
    if (!is_authenticated() || !must_change_password()) {
        return;
    }

    $actual = basename($_SERVER['PHP_SELF'] ?? '');
    $permitidas = ['change_password.php', 'logout.php'];

    if (!in_array($actual, $permitidas, true)) {
        redirect('change_password.php');
    }
}

function require_auth() {
    if (!is_authenticated()) {
        redirect('login.php');
    }

    enforce_password_change();
}

function has_admin_access($conn) {
    if (!is_authenticated()) {
        return false;
    }

    $authUsername = (string)($_SESSION['auth_username'] ?? '');

    // Allow the bootstrap admin from usuarios_auth.
    if ($authUsername === 'admin') {
        return true;
    }

    // Legacy users use email as username in usuarios_auth.
    $stmt = $conn->prepare("SELECT 1
                           FROM usuarios u
                           INNER JOIN roles r ON u.id_rol = r.id_rol
                           WHERE u.correo = ?
                             AND LOWER(r.nombre_rol) IN ('admin', 'administrador')
                           LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $authUsername);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = $res && $res->num_rows > 0;
    $stmt->close();

    return $ok;
}

function require_admin_access($conn) {
    require_auth();

    if (!has_admin_access($conn)) {
        redirect('equipos.php');
    }
}
