<?php
include 'conexion.php';
include 'funciones.php';

require_admin_access($conn);

$mensaje = '';

function ejecutar_con_mensaje($stmt, $ok, $errorDefault) {
    global $mensaje;
    if ($stmt->execute()) {
        csrf_regenerate_token();
        $mensaje = "<div class='alert alert-success'>" . e($ok) . "</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>" . e($errorDefault) . ': ' . e($stmt->error) . "</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate_request()) {
        $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta de nuevo.</div>";
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear_rol') {
            $nombre_rol = trim($_POST['nombre_rol'] ?? '');
            if ($nombre_rol === '') {
                $mensaje = "<div class='alert alert-warning'>El nombre del rol es obligatorio.</div>";
            } else {
                $stmt = $conn->prepare('INSERT INTO roles (nombre_rol) VALUES (?)');
                if ($stmt) {
                    $stmt->bind_param('s', $nombre_rol);
                    ejecutar_con_mensaje($stmt, 'Rol creado correctamente.', 'No se pudo crear el rol');
                    $stmt->close();
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar la creacion del rol.</div>";
                }
            }
        }

        if ($accion === 'crear_usuario_legacy') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $password = $_POST['password'] ?? '';
            $id_rol = (int)($_POST['id_rol'] ?? 0);

            if ($nombre === '' || $correo === '' || $password === '') {
                $mensaje = "<div class='alert alert-warning'>Completa nombre, correo y clave.</div>";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $rol = $id_rol > 0 ? $id_rol : null;
                $must_change_password = 1;
                $conn->begin_transaction();

                $stmt = $conn->prepare('INSERT INTO usuarios (nombre, correo, password, id_rol, must_change_password) VALUES (?, ?, ?, ?, ?)');
                $stmtAuth = $conn->prepare('INSERT INTO usuarios_auth (username, password_hash, must_change_password) VALUES (?, ?, ?)');

                if ($stmt && $stmtAuth) {
                    $stmt->bind_param('sssii', $nombre, $correo, $hash, $rol, $must_change_password);
                    $stmtAuth->bind_param('ssi', $correo, $hash, $must_change_password);

                    $okLegacy = $stmt->execute();
                    $okAuth = $stmtAuth->execute();

                    if ($okLegacy && $okAuth) {
                        $conn->commit();
                        csrf_regenerate_token();
                        $mensaje = "<div class='alert alert-success'>Usuario legacy creado y habilitado para login (username = correo).</div>";
                    } else {
                        $conn->rollback();
                        $detalle = $stmt->error ?: $stmtAuth->error;
                        $mensaje = "<div class='alert alert-danger'>No se pudo crear el usuario en ambas tablas: " . e($detalle) . "</div>";
                    }

                    $stmt->close();
                    $stmtAuth->close();
                } else {
                    $conn->rollback();
                    if ($stmt) {
                        $stmt->close();
                    }
                    if ($stmtAuth) {
                        $stmtAuth->close();
                    }
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar la creacion del usuario.</div>";
                }
            }
        }

        if ($accion === 'crear_patrocinador') {
            $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
            $rubro = trim($_POST['rubro'] ?? '');

            if ($nombre_empresa === '') {
                $mensaje = "<div class='alert alert-warning'>El nombre de empresa es obligatorio.</div>";
            } else {
                $stmt = $conn->prepare('INSERT INTO patrocinadores (nombre_empresa, rubro) VALUES (?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ss', $nombre_empresa, $rubro);
                    ejecutar_con_mensaje($stmt, 'Patrocinador creado correctamente.', 'No se pudo crear el patrocinador');
                    $stmt->close();
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar la creacion del patrocinador.</div>";
                }
            }
        }

        if ($accion === 'vincular_patrocinador') {
            $id_equipo = (int)($_POST['id_equipo'] ?? 0);
            $id_patrocinador = (int)($_POST['id_patrocinador'] ?? 0);
            $monto_contrato = (float)($_POST['monto_contrato'] ?? 0);

            if ($id_equipo <= 0 || $id_patrocinador <= 0) {
                $mensaje = "<div class='alert alert-warning'>Selecciona equipo y patrocinador validos.</div>";
            } else {
                $stmt = $conn->prepare('INSERT INTO equipo_patrocinador (id_equipo, id_patrocinador, monto_contrato) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('iid', $id_equipo, $id_patrocinador, $monto_contrato);
                    ejecutar_con_mensaje($stmt, 'Vinculo equipo-patrocinador creado.', 'No se pudo crear el vinculo');
                    $stmt->close();
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar el vinculo.</div>";
                }
            }
        }


        if ($accion === 'eliminar_rol') {
            $id = (int)($_POST['id_rol'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM roles WHERE id_rol = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                ejecutar_con_mensaje($stmt, 'Rol eliminado.', 'No se pudo eliminar el rol');
                $stmt->close();
            }
        }

        if ($accion === 'eliminar_usuario_legacy') {
            $id = (int)($_POST['id_usuario'] ?? 0);
            if ($id <= 0) {
                $mensaje = "<div class='alert alert-warning'>Usuario no valido para eliminar.</div>";
            } else {
                $conn->begin_transaction();

                $stmtCorreo = $conn->prepare('SELECT correo FROM usuarios WHERE id_usuario = ? LIMIT 1');
                $stmtDelLegacy = $conn->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
                $stmtDelAuth = $conn->prepare('DELETE FROM usuarios_auth WHERE username = ?');

                if ($stmtCorreo && $stmtDelLegacy && $stmtDelAuth) {
                    $stmtCorreo->bind_param('i', $id);
                    $stmtCorreo->execute();
                    $resCorreo = $stmtCorreo->get_result();
                    $legacy = $resCorreo ? $resCorreo->fetch_assoc() : null;

                    if (!$legacy || empty($legacy['correo'])) {
                        $conn->rollback();
                        $mensaje = "<div class='alert alert-warning'>No se encontro el usuario legacy seleccionado.</div>";
                    } else {
                        $correo = (string)$legacy['correo'];

                        $stmtDelLegacy->bind_param('i', $id);
                        $okLegacy = $stmtDelLegacy->execute();

                        $stmtDelAuth->bind_param('s', $correo);
                        $okAuth = $stmtDelAuth->execute();

                        if ($okLegacy && $okAuth) {
                            $conn->commit();
                            csrf_regenerate_token();
                            $mensaje = "<div class='alert alert-success'>Usuario legacy y cuenta de acceso eliminados.</div>";
                        } else {
                            $conn->rollback();
                            $detalle = $stmtDelLegacy->error ?: $stmtDelAuth->error;
                            $mensaje = "<div class='alert alert-danger'>No se pudo eliminar el usuario en ambas tablas: " . e($detalle) . "</div>";
                        }
                    }

                    $stmtCorreo->close();
                    $stmtDelLegacy->close();
                    $stmtDelAuth->close();
                } else {
                    $conn->rollback();
                    if ($stmtCorreo) {
                        $stmtCorreo->close();
                    }
                    if ($stmtDelLegacy) {
                        $stmtDelLegacy->close();
                    }
                    if ($stmtDelAuth) {
                        $stmtDelAuth->close();
                    }
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar la eliminacion sincronizada.</div>";
                }
            }
        }

        if ($accion === 'eliminar_patrocinador') {
            $id = (int)($_POST['id_patrocinador'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM patrocinadores WHERE id_patrocinador = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                ejecutar_con_mensaje($stmt, 'Patrocinador eliminado.', 'No se pudo eliminar el patrocinador');
                $stmt->close();
            }
        }

        if ($accion === 'eliminar_vinculo') {
            $id_equipo = (int)($_POST['id_equipo'] ?? 0);
            $id_patrocinador = (int)($_POST['id_patrocinador'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM equipo_patrocinador WHERE id_equipo = ? AND id_patrocinador = ?');
            if ($stmt) {
                $stmt->bind_param('ii', $id_equipo, $id_patrocinador);
                ejecutar_con_mensaje($stmt, 'Vinculo eliminado.', 'No se pudo eliminar el vinculo');
                $stmt->close();
            }
        }

    }
}

$roles = $conn->query('SELECT id_rol, nombre_rol FROM roles ORDER BY id_rol DESC');
$usuarios = $conn->query("SELECT u.id_usuario, u.nombre, u.correo, u.id_rol, r.nombre_rol,
                          CASE WHEN ua.id_usuario IS NULL THEN 'NO' ELSE 'SI' END AS acceso_login
                          FROM usuarios u
                          LEFT JOIN roles r ON u.id_rol = r.id_rol
                          LEFT JOIN usuarios_auth ua ON ua.username = u.correo
                          ORDER BY u.id_usuario DESC");
$equipos = $conn->query('SELECT id_equipo, nombre FROM equipos ORDER BY nombre ASC');
$patrocinadores = $conn->query('SELECT id_patrocinador, nombre_empresa, rubro FROM patrocinadores ORDER BY id_patrocinador DESC');
$vinculos = $conn->query('SELECT ep.id_equipo, ep.id_patrocinador, ep.monto_contrato, e.nombre AS equipo, p.nombre_empresa AS patrocinador FROM equipo_patrocinador ep JOIN equipos e ON ep.id_equipo = e.id_equipo JOIN patrocinadores p ON ep.id_patrocinador = p.id_patrocinador ORDER BY e.nombre ASC');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administracion - Liga Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/site.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">⚽ Liga Pro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="jugadores.php">Jugadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="partidos.php">Partidos</a></li>
                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                    <li class="nav-item"><a class="nav-link active" href="administracion.php">Administracion</a></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Salir</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <h2 class="mb-3">Administracion Extendida</h2>
        <?php echo $mensaje; ?>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">Roles</div>
                    <div class="card-body">
                        <form method="POST" action="administracion.php" class="row g-2 mb-3">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="accion" value="crear_rol">
                            <div class="col-8"><input type="text" name="nombre_rol" class="form-control" placeholder="Ej: Operador" required></div>
                            <div class="col-4"><button class="btn btn-primary w-100" type="submit">Guardar</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead><tr><th>ID</th><th>Rol</th><th class="text-end">Accion</th></tr></thead>
                                <tbody>
                                <?php if ($roles && $roles->num_rows > 0): while ($r = $roles->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo e($r['id_rol']); ?></td>
                                        <td><?php echo e($r['nombre_rol']); ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="administracion.php" class="d-inline" onsubmit="return confirm('Eliminar rol?');">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="accion" value="eliminar_rol">
                                                <input type="hidden" name="id_rol" value="<?php echo e($r['id_rol']); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" class="text-center">Sin roles registrados.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">Usuarios Legacy (tabla usuarios)</div>
                    <div class="card-body">
                        <form method="POST" action="administracion.php" class="row g-2 mb-3">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="accion" value="crear_usuario_legacy">
                            <div class="col-md-6"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div>
                            <div class="col-md-6"><input type="email" name="correo" class="form-control" placeholder="Correo" required></div>
                            <div class="col-md-6"><input type="password" name="password" class="form-control" placeholder="Clave" required></div>
                            <div class="col-md-6">
                                <select name="id_rol" class="form-select">
                                    <option value="">Sin rol</option>
                                    <?php
                                    $roles_select = $conn->query('SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol ASC');
                                    while ($rs = $roles_select->fetch_assoc()) {
                                        echo "<option value='" . e($rs['id_rol']) . "'>" . e($rs['nombre_rol']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12"><small class="text-muted">El login se habilita automaticamente en usuarios_auth usando el correo como username.</small></div>
                            <div class="col-12"><button class="btn btn-success w-100" type="submit">Crear Usuario Legacy</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Login</th><th class="text-end">Accion</th></tr></thead>
                                <tbody>
                                <?php if ($usuarios && $usuarios->num_rows > 0): while ($u = $usuarios->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo e($u['nombre']); ?></td>
                                        <td><?php echo e($u['correo']); ?></td>
                                        <td><?php echo e($u['nombre_rol'] ?? 'Sin rol'); ?></td>
                                        <td>
                                            <?php if (($u['acceso_login'] ?? 'NO') === 'SI'): ?>
                                                <span class="badge bg-success">SI</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NO</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="administracion.php" class="d-inline" onsubmit="return confirm('Eliminar usuario legacy?');">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="accion" value="eliminar_usuario_legacy">
                                                <input type="hidden" name="id_usuario" value="<?php echo e($u['id_usuario']); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center">Sin usuarios legacy registrados.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-warning">Patrocinadores</div>
                    <div class="card-body">
                        <form method="POST" action="administracion.php" class="row g-2 mb-3">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="accion" value="crear_patrocinador">
                            <div class="col-md-7"><input type="text" name="nombre_empresa" class="form-control" placeholder="Empresa" required></div>
                            <div class="col-md-5"><input type="text" name="rubro" class="form-control" placeholder="Rubro"></div>
                            <div class="col-12"><button class="btn btn-warning w-100" type="submit">Guardar Patrocinador</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead><tr><th>Empresa</th><th>Rubro</th><th class="text-end">Accion</th></tr></thead>
                                <tbody>
                                <?php if ($patrocinadores && $patrocinadores->num_rows > 0): while ($p = $patrocinadores->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo e($p['nombre_empresa']); ?></td>
                                        <td><?php echo e($p['rubro']); ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="administracion.php" class="d-inline" onsubmit="return confirm('Eliminar patrocinador?');">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="accion" value="eliminar_patrocinador">
                                                <input type="hidden" name="id_patrocinador" value="<?php echo e($p['id_patrocinador']); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" class="text-center">Sin patrocinadores.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">Vinculo Equipo - Patrocinador</div>
                    <div class="card-body">
                        <form method="POST" action="administracion.php" class="row g-2 mb-3">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="accion" value="vincular_patrocinador">
                            <div class="col-md-5">
                                <select name="id_equipo" class="form-select" required>
                                    <option value="">Equipo</option>
                                    <?php
                                    $eq_select = $conn->query('SELECT id_equipo, nombre FROM equipos ORDER BY nombre ASC');
                                    while ($eq = $eq_select->fetch_assoc()) {
                                        echo "<option value='" . e($eq['id_equipo']) . "'>" . e($eq['nombre']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <select name="id_patrocinador" class="form-select" required>
                                    <option value="">Patrocinador</option>
                                    <?php
                                    $pat_select = $conn->query('SELECT id_patrocinador, nombre_empresa FROM patrocinadores ORDER BY nombre_empresa ASC');
                                    while ($pt = $pat_select->fetch_assoc()) {
                                        echo "<option value='" . e($pt['id_patrocinador']) . "'>" . e($pt['nombre_empresa']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2"><input type="number" step="0.01" name="monto_contrato" class="form-control" placeholder="Monto"></div>
                            <div class="col-12"><button class="btn btn-info w-100 text-white" type="submit">Vincular</button></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead><tr><th>Equipo</th><th>Patrocinador</th><th>Monto</th><th class="text-end">Accion</th></tr></thead>
                                <tbody>
                                <?php if ($vinculos && $vinculos->num_rows > 0): while ($v = $vinculos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo e($v['equipo']); ?></td>
                                        <td><?php echo e($v['patrocinador']); ?></td>
                                        <td><?php echo e($v['monto_contrato']); ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="administracion.php" class="d-inline" onsubmit="return confirm('Eliminar vinculo?');">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="accion" value="eliminar_vinculo">
                                                <input type="hidden" name="id_equipo" value="<?php echo e($v['id_equipo']); ?>">
                                                <input type="hidden" name="id_patrocinador" value="<?php echo e($v['id_patrocinador']); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" class="text-center">Sin vinculos registrados.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
