<?php
include 'conexion.php';
include 'funciones.php';

require_auth();

$mensaje = '';

if (!ensure_usuarios_schema($conn)) {
    $mensaje = "<div class='alert alert-danger'>No se pudo preparar la tabla de usuarios.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mensaje !== '') {
        // Evita continuar si la estructura base de usuarios no esta lista.
    } elseif (!csrf_validate_request()) {
        $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta de nuevo.</div>";
    } else {
        $actual = $_POST['password_actual'] ?? '';
        $nueva = $_POST['password_nueva'] ?? '';
        $confirmacion = $_POST['password_confirmacion'] ?? '';

        if ($actual === '' || $nueva === '' || $confirmacion === '') {
            $mensaje = "<div class='alert alert-warning'>Completa todos los campos.</div>";
        } elseif (strlen($nueva) < 8) {
            $mensaje = "<div class='alert alert-warning'>La nueva clave debe tener al menos 8 caracteres.</div>";
        } elseif ($nueva !== $confirmacion) {
            $mensaje = "<div class='alert alert-warning'>La confirmacion no coincide con la nueva clave.</div>";
        } else {
            $idUsuario = (int)($_SESSION['auth_user_id'] ?? 0);
            $stmtUser = $conn->prepare('SELECT password_hash FROM usuarios_auth WHERE id_usuario = ? LIMIT 1');

            if ($stmtUser) {
                $stmtUser->bind_param('i', $idUsuario);
                $stmtUser->execute();
                $resUser = $stmtUser->get_result();
                $usuario = $resUser ? $resUser->fetch_assoc() : null;
                $stmtUser->close();

                if (!$usuario || !password_verify($actual, $usuario['password_hash'])) {
                    $mensaje = "<div class='alert alert-danger'>La clave actual es incorrecta.</div>";
                } else {
                    $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
                    $stmtUpd = $conn->prepare('UPDATE usuarios_auth SET password_hash = ?, must_change_password = 0 WHERE id_usuario = ?');

                    if ($stmtUpd) {
                        $stmtUpd->bind_param('si', $nuevoHash, $idUsuario);
                        if ($stmtUpd->execute()) {
                            $_SESSION['must_change_password'] = false;
                            csrf_regenerate_token();
                            $mensaje = "<div class='alert alert-success'>Clave actualizada con exito. Ya puedes continuar.</div>";
                            header('Refresh: 1; URL=equipos.php');
                        } else {
                            $mensaje = "<div class='alert alert-danger'>No se pudo actualizar la clave.</div>";
                        }
                        $stmtUpd->close();
                    } else {
                        $mensaje = "<div class='alert alert-danger'>No se pudo preparar la actualizacion.</div>";
                    }
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo validar tu cuenta.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Clave - Liga Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/site.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Liga Pro</a>
            <div class="ms-auto">
                <a class="btn btn-outline-light btn-sm" href="logout.php">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 auth-hero">
                <div class="card">
                    <div class="card-header bg-dark text-white text-center">Cambio de Clave</div>
                    <div class="card-body p-4">
                        <p class="text-muted small">Por seguridad, debes cambiar la clave temporal antes de continuar.</p>
                        <?php echo $mensaje; ?>
                        <form method="POST" action="change_password.php">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <label class="form-label">Clave actual</label>
                                <input type="password" name="password_actual" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nueva clave</label>
                                <input type="password" name="password_nueva" class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar nueva clave</label>
                                <input type="password" name="password_confirmacion" class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Actualizar clave</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
