<?php
session_start();
require_once 'config/conexion.php';

$pdo = getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$stmtRoles = $pdo->prepare("SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol");
$stmtRoles->execute();
$roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.tipo_documento, u.documento, u.telefono, u.estado, r.nombre_rol
    FROM usuarios u
    LEFT JOIN roles r ON u.id_rol = r.id_rol
    ORDER BY u.id_usuario
");

$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HairSoft - Gestión de Usuarios</title>
    <link rel="stylesheet" href="css/styles_Reg_U_L_Admin.css" />
</head>
<body>
    <div class="header">
        <div class="logo">HairSoft <span class="icon-barber">✂️</span></div>
        <div class="nav">
            <a href="usuarios.php" class="active">Usuarios</a>
            <a href="Servicios.php">Servicios</a>
            <a href="#">Productos</a>
            <a href="#">Turnos</a>
            <a href="Permiso_Rol.php">Seguridad</a>
        </div>
        <div class="user-info">
            <div class="user-avatar"></div>
            <span>Admin</span>
            <div class="online-indicator"></div>
            <button id="btnCerrarSesion" class="logout-btn">Cerrar Sesión</button>
        </div>
    </div>

    <div class="main-content">
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Usuarios Registrados</h2>
                <button id="btnRegistrarUsuario" class="create-btn">Registrar Usuario</button>
            </div>
            
            <div class="search-box">
                <input type="text" id="inputBuscarUsuario" class="search-input" placeholder="Buscar usuarios..." />
                <span class="search-icon">🔍</span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Tipo Doc.</th><th>Documento</th><th>Teléfono</th><th>Rol</th><th>Estado</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaUsuarios">
                        <?php foreach ($usuarios as $u): ?>
                        <tr data-id="<?= htmlspecialchars($u['id_usuario']) ?>"
                            data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                            data-apellido="<?= htmlspecialchars($u['apellido']) ?>"
                            data-email="<?= htmlspecialchars($u['email']) ?>"
                            data-tipo-doc="<?= htmlspecialchars($u['tipo_documento']) ?>"
                            data-documento="<?= htmlspecialchars($u['documento']) ?>"
                            data-telefono="<?= htmlspecialchars($u['telefono']) ?>"
                            data-rol="<?= htmlspecialchars($u['nombre_rol']) ?>"
                            data-estado="<?= htmlspecialchars($u['estado']) ?>"
                        >
                            <td><?= htmlspecialchars($u['id_usuario']) ?></td>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['apellido']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['tipo_documento']) ?></td>
                            <td><?= htmlspecialchars($u['documento']) ?></td>
                            <td><?= htmlspecialchars($u['telefono']) ?></td>
                            <td><?= htmlspecialchars($u['nombre_rol']) ?></td>
                            <td>
                                <?php if($u['estado'] === 'activo'): ?>
                                    <span class="status-active">Activo</span>
                                <?php else: ?>
                                    <span class="status-inactive">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="action-btn btnEditar" title="Editar Usuario">&#9998;</button>
                                    <button class="action-btn btnEliminar" title="Eliminar Usuario">&#10006;</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="paginacion">
                <button class="btnPagina">‹</button>
                <button class="btnPagina activo">1</button>
                <button class="btnPagina">2</button>
                <button class="btnPagina">3</button>
                <button class="btnPagina">4</button>
                <button class="btnPagina">›</button>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <div class="modal-overlay" id="overlayModalUsuario" style="display:none;">
        <div class="modal">
            <button id="btnCerrarModalUsuario" class="close-btn">×</button>
            <div class="modal-header">
                <h2 class="modal-title" id="tituloModalUsuario">Registrar Nuevo Usuario</h2>
            </div>
            
            <form id="formUsuario">
                <input type="hidden" id="idUsuario" />
                <!-- botón para mostrar inputs contraseña -->
                <button type="button" id="btnCambiarPassword" style="margin-bottom: 15px;">Cambiar contraseña</button>
                
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="inputNombre">Nombre: <span class="required">*</span></label>
                            <input type="text" id="inputNombre" class="form-input" required />
                        </div>
                        <div class="form-group">
                            <label for="inputApellido">Apellido: <span class="required">*</span></label>
                            <input type="text" id="inputApellido" class="form-input" required />
                        </div>
                        <div class="form-group">
                            <label for="inputEmail">Email: <span class="required">*</span></label>
                            <input type="email" id="inputEmail" class="form-input" required />
                        </div>
                        <div class="form-group">
                            <label for="inputTelefono">Teléfono:</label>
                            <input type="tel" id="inputTelefono" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label for="selectTipoDoc">Tipo Documento: <span class="required">*</span></label>
                            <select id="selectTipoDoc" class="form-select" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="DNI">DNI</option>
                                <option value="LE">LE</option>
                                <option value="LC">LC</option>
                                <option value="Pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="inputDocumento">Número Documento: <span class="required">*</span></label>
                            <input type="text" id="inputDocumento" class="form-input" required />
                        </div>
                        <div class="form-group">
                            <label for="selectRol">Rol: <span class="required">*</span></label>
                            <select id="selectRol" class="form-select" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?= htmlspecialchars($rol['id_rol']) ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="selectEstado">Estado: <span class="required">*</span></label>
                            <select id="selectEstado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-group grupo-password">
                            <label for="inputPassword">Contraseña: <span class="required">*</span></label>
                            <input type="password" id="inputPassword" class="form-input" />
                        </div>
                        <div class="form-group grupo-password">
                            <label for="inputConfirmarPassword">Confirmar Contraseña: <span class="required">*</span></label>
                            <input type="password" id="inputConfirmarPassword" class="form-input" />
                        </div>
                    </div>
                    <div class="required-note">Campos obligatorios (*)</div>
                </div>
                <button type="submit" id="btnEnviarUsuario" class="submit-btn">Guardar</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-item">
            <span class="footer-icon">📞</span>
            <span>+54 3755 60-0000</span>
        </div>
        <div class="footer-item address">
            HairSoft &copy; 2025 - Todos los derechos reservados.
        </div>
        <div class="footer-item">
            <span class="footer-icon">📧</span>
            <span>HairSoft@info.com</span>
        </div>
    </footer>

    <script src="js/script_RegUsAdmin.js"></script>
</body>
</html>
