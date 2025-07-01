<?php
session_start();
require_once 'config/conexion.php'; 
require_once 'permissions.php';

$pdo = getConnection();

// Validar sesión y permiso
if (!isset($_SESSION['user_id']) || !hasPermission($pdo, $_SESSION['user_id'], 'Gestionar Seguridad')) {
    header('Location: index.php');
    exit();
}

// Obtener roles con permisos correctamente decodificados
$roles = getRolesWithPermissions($pdo);

// Obtener permisos - acá asumimos que tienes una función definida para esto, si no la adaptamos
$permisos = getAllPermisos($pdo);
$all_permisos_list = getAllPermisosList($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HairSoft - Gestionar Seguridad</title>
    <link rel="stylesheet" href="css/styles_Permiso_Rol.css" />
</head>
<body>
    <div class="header">
        <div class="logo">
            HairSoft
            <span class="icon-barber">✂️</span>
        </div>
        <div class="nav">
            <a href="Reg_Usuarios_Lista_Admin.php">Usuarios</a>
            <a href="Servicios.php">Servicios</a>
            <a href="#">Productos</a>
            <a href="#">Turnos</a>
            <a href="#" class="active">Seguridad</a>
        </div>
        <div class="user-info">
            <div class="user-avatar"></div>
            <span>Admin</span>
            <div class="online-indicator"></div>
            <button id="logoutBtn" class="logout-btn">Cerrar Sesión</button>
        </div>
    </div>

    <div class="main-content">
        <!-- Sección de Roles -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Roles</h2>
                <button id="createRoleBtn" class="create-btn">Crear Rol</button>
            </div>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Buscar roles..." id="searchRoles" />
                <span class="search-icon">🔍</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Rol</th>
                            <th>Descripción</th>
                            <th>Permisos Asignados</th>
                            <th class="Acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        <?php foreach ($roles as $rol): ?>
                        <tr data-id="<?php echo htmlspecialchars($rol['id_rol']); ?>">
                            <td><?php echo htmlspecialchars($rol['id_rol']); ?></td>
                            <td data-field="name"><?php echo htmlspecialchars($rol['nombre_rol']); ?></td>
                            <td data-field="description"><?php echo htmlspecialchars($rol['descripcion']); ?></td>
                            <td data-field="permissions">
                                <?php 
                                if (!empty($rol['permisos']) && is_array($rol['permisos'])) {
                                    $permisosTexto = [];
                                    foreach ($rol['permisos'] as $clave => $acciones) {
                                        if (is_array($acciones)) {
                                            $permisosTexto[] = $clave . ': ' . implode(', ', $acciones);
                                        } else {
                                            $permisosTexto[] = $clave . ': ' . $acciones;
                                        }
                                    }
                                    echo htmlspecialchars(implode(' | ', $permisosTexto));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="action-btn edit-role-btn" onclick="editRole(<?php echo htmlspecialchars($rol['id_rol']); ?>)">Editar</button>
                                    <button class="action-btn delete-role-btn" onclick="deleteRole(<?php echo htmlspecialchars($rol['id_rol']); ?>)">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección de Permisos -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Permisos</h2>
                <button id="createPermisoBtn" class="create-btn">Crear Permiso</button>
            </div>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Buscar permisos..." id="searchPermisos" />
                <span class="search-icon">🔍</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Permiso</th>
                            <th>Descripción</th>
                            <th class="acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="permisosTableBody">
                        <?php foreach ($permisos as $permiso): ?>
                        <tr data-id="<?php echo htmlspecialchars($permiso['id']); ?>">
                            <td><?php echo htmlspecialchars($permiso['id']); ?></td>
                            <td data-field="name"><?php echo htmlspecialchars($permiso['nombre']); ?></td>
                            <td data-field="description"><?php echo htmlspecialchars($permiso['descripcion']); ?></td>
                            <td>
                                <div class="actions">
                                    <button class="action-btn edit-permiso-btn" onclick="editPermiso(<?php echo htmlspecialchars($permiso['id']); ?>)">Editar</button>
                                    <button class="action-btn delete-permiso-btn" onclick="deletePermiso(<?php echo htmlspecialchars($permiso['id']); ?>)">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const allPermisos = <?php echo json_encode($all_permisos_list); ?>;
        const currentRoles = <?php echo json_encode($roles); ?>;
    </script>
    <script src="js/script_permisos_rol.js"></script>
</body>
</html>
