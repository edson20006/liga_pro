<?php
include 'conexion.php';
include 'funciones.php';

if (is_authenticated()) {
    if (must_change_password()) {
        redirect('change_password.php');
    }
    redirect('equipos.php');
}

$mensaje = '';

if (!ensure_usuarios_schema($conn)) {
    $mensaje = "<div class='alert alert-danger'>No se pudo preparar la tabla de usuarios.</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mensaje !== '') {
        // Evita continuar si la estructura base de usuarios no esta lista.
    } elseif (!csrf_validate_request()) {
        $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta otra vez.</div>";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $mensaje = "<div class='alert alert-warning'>Completa usuario y clave.</div>";
        } else {
            $stmt = $conn->prepare('SELECT id_usuario, username, password_hash, must_change_password FROM usuarios_auth WHERE username = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();
                $user = $res ? $res->fetch_assoc() : null;
                $stmt->close();

                if ($user && password_verify($password, $user['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['auth_user_id'] = (int)$user['id_usuario'];
                    $_SESSION['auth_username'] = $user['username'];
                    $_SESSION['must_change_password'] = !empty($user['must_change_password']);
                    csrf_regenerate_token();

                    if ($_SESSION['must_change_password']) {
                        redirect('change_password.php');
                    }
                    redirect('equipos.php');
                } else {
                    $mensaje = "<div class='alert alert-danger'>Credenciales invalidas.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo validar el acceso.</div>";
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
    <title>Login - Liga Pro</title>
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
                <a class="btn btn-outline-light btn-sm" href="index.php">Volver al inicio</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header bg-dark text-white text-center">Login</div>
                    <div class="card-body p-4">
                        <?php echo $mensaje; ?>
                        <form method="POST" action="login.php">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="username" class="form-control" required autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Clave</label>
                                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
