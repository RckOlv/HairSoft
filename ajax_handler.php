<?php
session_start();
$rawInput = file_get_contents('php://input');
file_put_contents('debug.json', $rawInput);
require_once 'config/conexion.php';
require_once 'permissions.php';

header('Content-Type: application/json');

$pdo = getConnection();

// Leer JSON recibido
$data = json_decode($rawInput, true);

// Si no hay datos JSON, intentar con $_POST o $_GET
if (!$data) {
    if (!empty($_POST)) {
        $data = $_POST;
    } elseif (!empty($_GET)) {
        $data = $_GET;
    }
}

file_put_contents('php://stderr', "Datos recibidos: " . print_r($data, true) . "\n");

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o no enviados en formato JSON']);
    exit();
}

$action = $data['action'] ?? '';
// Acciones públicas que no requieren sesión ni permisos
$acciones_publicas = ['register_client_user'];

if (!in_array($action, $acciones_publicas)) {
    if (!isset($_SESSION['user_id']) || !hasPermission($pdo, $_SESSION['user_id'], 'Gestionar Seguridad')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
        exit();
    }
}

$action = $data['action'] ?? '';

switch ($action) {
   // ----------- ESTADÍSTICAS DASHBOARD ----------------
    case 'get_dashboard_stats':
        try {
            // Contar usuarios activos (estado en minúsculas 'activo')
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'");
            $stmt->execute();
            $total_usuarios = $stmt->fetchColumn();

            // Contar servicios activos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE estado = 'activo'");
            $stmt->execute();
            $total_servicios = $stmt->fetchColumn();

            // Como no existe la tabla 'turnos', ponemos 0
            $turnos_hoy = 0;

            // Como no existe la tabla 'pagos', ponemos 0
            $ingresos_mensuales = 0;

            // Obtener actividad reciente
            $actividad_reciente = [];

            // Usuarios registrados en los últimos 7 días
            $stmt = $pdo->prepare("
                SELECT 
                    'usuario_nuevo' as tipo,
                    CONCAT('Nuevo usuario registrado: ', nombre, ' ', apellido) as mensaje,
                    created_at as fecha
                FROM usuarios 
                WHERE created_at >= CURRENT_DATE - INTERVAL 7 DAY
                ORDER BY created_at DESC
                LIMIT 3
            ");
            $stmt->execute();
            $usuarios_nuevos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $actividad_reciente = array_merge($actividad_reciente, $usuarios_nuevos);

           
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        'servicio_actualizado' as tipo,
                        CONCAT('Servicio actualizado: \"', nombre, '\"') as mensaje,
                        created_at as fecha
                    FROM servicios 
                    WHERE created_at >= CURRENT_DATE - INTERVAL 7 DAY
                    ORDER BY created_at DESC
                    LIMIT 2
                ");
                $stmt->execute();
                $servicios_actualizados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $actividad_reciente = array_merge($actividad_reciente, $servicios_actualizados);
            } catch (PDOException $e) {
                // Si no existe el campo fecha_actualizacion, continuar sin error
            }

            // Ordenar actividad por fecha más reciente
            usort($actividad_reciente, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            // Limitar a 5 elementos
            $actividad_reciente = array_slice($actividad_reciente, 0, 5);

            echo json_encode([
                'success' => true,
                'data' => [
                    'usuarios' => $total_usuarios,
                    'servicios' => $total_servicios,
                    'turnos' => $turnos_hoy,
                    'ingresos' => $ingresos_mensuales,
                    'actividad_reciente' => $actividad_reciente
                ]
            ]);
        } catch (PDOException $e) {
            file_put_contents('debug_error.log', "Error obteniendo estadísticas: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode([
                 'success' => false, 
                 'message' => 'Error al obtener estadísticas',
                 'data' => [
                 'usuarios' => 0,
                 'servicios' => 0,
                 'turnos' => 0,
                 'ingresos' => 0,
                 'actividad_reciente' => []
            ]]);
        }
        break;

    // ----------- ROLES ----------------
    case 'create_role':
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $permisos = $data['permisos'] ?? [];
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del rol es obligatorio']);
            break;
        }
        if (createRole($pdo, $nombre, $descripcion, $permisos)) {
            echo json_encode(['success' => true, 'message' => 'Rol creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el rol']);
        }
        break;

    case 'update_role':
        $id = $data['id'] ?? 0;
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $permisos = $data['permisos'] ?? [];
        if (empty($nombre) || $id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            break;
        }
        if (updateRole($pdo, $id, $nombre, $descripcion, $permisos)) {
            echo json_encode(['success' => true, 'message' => 'Rol actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el rol']);
        }
        break;

    case 'delete_role':
        $id = $data['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        // Verificar si el rol tiene usuarios asignados
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id_rol = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar el rol porque tiene usuarios asignados.']);
            break;
        }
        if (deleteRole($pdo, $id)) {
            echo json_encode(['success' => true, 'message' => 'Rol eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el rol.']);
        }
        break;

    case 'get_role':
        $id = $data['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        $role = getRoleById($pdo, $id);
        if ($role) {
            echo json_encode(['success' => true, 'data' => $role]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Rol no encontrado']);
        }
        break;

    case 'search_roles':
        $search = trim($data['search'] ?? '');
        $roles = getRolesWithPermissions($pdo);
        if (!empty($search)) {
            $search_lower = mb_strtolower($search);
            $roles = array_filter($roles, function($role) use ($search_lower) {
                $nombre_rol = isset($role['nombre_rol']) ? mb_strtolower($role['nombre_rol']) : '';
                $descripcion = isset($role['descripcion']) ? mb_strtolower($role['descripcion']) : '';
                return strpos($nombre_rol, $search_lower) !== false ||
                       strpos($descripcion, $search_lower) !== false;
            });
        }
        echo json_encode(['success' => true, 'data' => array_values($roles)]);
        break;

    // ----------- PERMISOS ---------------
    case 'create_permiso':
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del permiso es obligatorio']);
            break;
        }
        if (createPermiso($pdo, $nombre, $descripcion)) {
            echo json_encode(['success' => true, 'message' => 'Permiso creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el permiso']);
        }
        break;

    case 'update_permiso':
        $id = $data['id'] ?? 0;
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        if (empty($nombre) || $id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            break;
        }
        if (updatePermiso($pdo, $id, $nombre, $descripcion)) {
            echo json_encode(['success' => true, 'message' => 'Permiso actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el permiso']);
        }
        break;

    case 'delete_permiso':
        $id = $data['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        if (deletePermiso($pdo, $id)) {
            echo json_encode(['success' => true, 'message' => 'Permiso eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el permiso']);
        }
        break;

    case 'get_permiso':
        $id = $data['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        $permiso = getPermisoById($pdo, $id);
        if ($permiso) {
            echo json_encode(['success' => true, 'data' => $permiso]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Permiso no encontrado']);
        }
        break;

    case 'search_permisos':
        $search = trim($data['search'] ?? '');
        $permisos = getAllPermisos($pdo);
        if (!empty($search)) {
            $permisos = array_filter($permisos, function($permiso) use ($search) {
                return stripos($permiso['nombre'], $search) !== false ||
                       stripos($permiso['descripcion'], $search) !== false;
            });
        }
        echo json_encode(['success' => true, 'data' => array_values($permisos)]);
        break;

    // ----------- USUARIOS ---------------
    case 'create_user':
    $nombre = trim($data['nombre'] ?? '');
    $apellido = trim($data['apellido'] ?? '');
    $email = trim($data['email'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $tipo_documento = $data['tipo_documento'] ?? '';
    $documento = trim($data['documento'] ?? '');
    $id_rol = $data['id_rol'] ?? '';
    $password = $data['password'] ?? '';

    if (!$nombre) { echo json_encode(['success' => false, 'message' => 'Falta nombre']); break; }
    if (!$email) { echo json_encode(['success' => false, 'message' => 'Falta email']); break; }
    if (!$tipo_documento) { echo json_encode(['success' => false, 'message' => 'Falta tipo de documento']); break; }
    if (!$documento) { echo json_encode(['success' => false, 'message' => 'Falta número de documento']); break; }
    if (!$id_rol) { echo json_encode(['success' => false, 'message' => 'Falta rol']); break; }
    if (!$password) { echo json_encode(['success' => false, 'message' => 'Falta password']); break; }
    if (!$apellido) { echo json_encode(['success' => false, 'message' => 'Falta apellido']); break; }

    // Validaciones de formato
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'El email no tiene un formato válido.']); break;
    }
    if (!preg_match('/^\d{6,12}$/', $documento)) {
        echo json_encode(['success' => false, 'message' => 'Número de documento inválido.']); break;
    }
    if (!empty($telefono) && !preg_match('/^\d{6,15}$/', $telefono)) {
        echo json_encode(['success' => false, 'message' => 'Número de teléfono inválido.']); break;
    }
    if (!empty($password) && strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']); break;
    }

    $estado = 'activo'; // Fuerza el estado a 'activo' al crear

    // Verificar duplicados
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? OR documento = ?");
    $stmtCheck->execute([$email, $documento]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email o documento ya registrado.']);
        break;
    }

    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, telefono, tipo_documento, documento, id_rol, estado, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $apellido, $email, $telefono, $tipo_documento, $documento, $id_rol, $estado, $hashedPassword]);
        echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.']);
    } catch (PDOException $e) {
        error_log("Error creando usuario: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario.']);
    }
    break;

case 'update_user':
    $id_usuario = $data['id_usuario'] ?? '';
    $nombre = trim($data['nombre'] ?? '');
    $apellido = trim($data['apellido'] ?? '');
    $email = trim($data['email'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $tipo_documento = $data['tipo_documento'] ?? '';
    $documento = trim($data['documento'] ?? '');
    $id_rol = $data['id_rol'] ?? '';
    $estado = $data['estado'] ?? '';
    $password = $data['password'] ?? '';

    if (!$nombre) { echo json_encode(['success' => false, 'message' => 'Falta nombre']); break; }
    if (!$email) { echo json_encode(['success' => false, 'message' => 'Falta email']); break; }
    if (!$tipo_documento) { echo json_encode(['success' => false, 'message' => 'Falta tipo de documento']); break; }
    if (!$documento) { echo json_encode(['success' => false, 'message' => 'Falta número de documento']); break; }
    if (!$id_rol) { echo json_encode(['success' => false, 'message' => 'Falta rol']); break; }
    if (!$estado) { echo json_encode(['success' => false, 'message' => 'Falta estado']); break; }
    if (!$apellido) { echo json_encode(['success' => false, 'message' => 'Falta apellido']); break; }

    // Validaciones de formato
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'El email no tiene un formato válido.']); break;
    }
    if (!preg_match('/^\d{6,12}$/', $documento)) {
        echo json_encode(['success' => false, 'message' => 'Número de documento inválido.']); break;
    }
    if (!empty($telefono) && !preg_match('/^\d{6,15}$/', $telefono)) {
        echo json_encode(['success' => false, 'message' => 'Número de teléfono inválido.']); break;
    }
    if (!empty($password) && strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']); break;
    }

    // Verificar duplicados en otros usuarios
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE (email = ? OR documento = ?) AND id_usuario != ?");
    $stmtCheck->execute([$email, $documento, $id_usuario]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email o documento ya registrado en otro usuario.']);
        break;
    }

    try {
        error_log("Datos recibidos: nombre='$nombre', apellido='$apellido', correo='$email', tipo_documento='$tipo_documento', documento='$documento', id_rol='$id_rol', estado='$estado', password='$password'");
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, email=?, telefono=?, tipo_documento=?, documento=?, id_rol=?, estado=?, password=? WHERE id_usuario=?");
            $stmt->execute([$nombre, $apellido, $email, $telefono, $tipo_documento, $documento, $id_rol, $estado, $hashedPassword, $id_usuario]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, email=?, telefono=?, tipo_documento=?, documento=?, id_rol=?, estado=? WHERE id_usuario=?");
            $stmt->execute([$nombre, $apellido, $email, $telefono, $tipo_documento, $documento, $id_rol, $estado, $id_usuario]);
        }
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente.']);
    } catch (PDOException $e) {
        error_log("Error actualizando usuario: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario.', 'error' => $e->getMessage()]);
    }
    break;

    case 'delete_user':
        $id_usuario = $data['id_usuario'] ?? '';

        if (!$id_usuario) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario no especificado.']);
            break;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente.']);
        } catch (PDOException $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario.']);
        }
        break;

    case 'list_users':
        $search = $data['search'] ?? '';
        $searchType = $data['searchType'] ?? ''; // Nuevo: tipo de búsqueda ('nombre', 'documento' o vacío)
        $page = max(1, intval($data['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $params = [];
        $where = "";

        if ($search) {
            if ($searchType === 'nombre') {
                $where = "WHERE nombre LIKE ?";
                $params = ["%$search%"];
            } elseif ($searchType === 'documento') {
                $where = "WHERE documento LIKE ?";
                $params = ["%$search%"];
            } else {
                // Si no se especifica tipo, buscar en ambos
                $where = "WHERE nombre LIKE ? OR documento LIKE ?";
                $params = ["%$search%", "%$search%"];
            }
        }

        $query = "SELECT * FROM usuarios $where ORDER BY id_usuario DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM usuarios $where");
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => $usuarios,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        break;

    //Registrar usuario cliente desde formulario en el index.
    case 'register_client_user':
    $nombre = trim($data['nombre'] ?? '');
    $apellido = trim($data['apellido'] ?? '');
    $email = trim($data['email'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $tipo_documento = $data['tipo_documento'] ?? '';
    $documento = trim($data['documento'] ?? '');
    $password = $data['password'] ?? '';
    $estado = 'activo';  // Default para clientes registrados desde formulario

    if (!$nombre || !$apellido || !$email || !$tipo_documento || !$documento || !$password) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        break;
    }

    try {
        // Obtener el id del rol Cliente
        $stmtRol = $pdo->prepare("SELECT id_rol FROM roles WHERE nombre_rol = 'Cliente' LIMIT 1");
        $stmtRol->execute();
        $id_rol_cliente = $stmtRol->fetchColumn();

        if (!$id_rol_cliente) {
            echo json_encode(['success' => false, 'message' => 'No existe el rol Cliente en la base de datos']);
            break;
        }

        // Verificar duplicados
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? OR documento = ?");
        $stmtCheck->execute([$email, $documento]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email o documento ya registrado']);
            break;
        }

        // Hash de la contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insertar nuevo usuario con rol Cliente
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, telefono, tipo_documento, documento, id_rol, estado, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $apellido, $email, $telefono, $tipo_documento, $documento, $id_rol_cliente, $estado, $hashedPassword]);

        echo json_encode(['success' => true, 'message' => 'Registro exitoso. Ahora puedes iniciar sesión.']);

    } catch (PDOException $e) {
        error_log("Error registrando usuario cliente: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al registrar usuario']);
    }
        break;


    // ----------- SERVICIOS ---------------
    case 'create_service':
    $nombre = trim($data['nombre'] ?? '');
        if (!preg_match('/^[a-zA-Z\s]+$/', $nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del servicio no debe contener números ni caracteres especiales.']);
        break;
        }
    $descripcion = trim($data['descripcion'] ?? '');
    $precio = floatval($data['precio'] ?? 0);
    $duracion = floatval($data['duracion'] ?? 0);
    $categoria = trim($data['categoria'] ?? '');
    $estado = 'activo'; // Forzar que el estado sea 'activo' al crear

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del servicio es obligatorio']);
        break;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO servicios (nombre, descripcion, precio, duracion, categoria, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $precio, $duracion, $categoria, $estado]);
        echo json_encode(['success' => true, 'message' => 'Servicio creado exitosamente']);
    } catch (PDOException $e) {
        error_log("Error creando servicio: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al crear servicio']);
    }
    break;


    case 'update_service':
        $id = $data['id'] ?? 0;
        $nombre = trim($data['nombre'] ?? '');
        if (!preg_match('/^[a-zA-Z\s]+$/', $nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del servicio no debe contener números ni caracteres especiales.']);
        break;
        }
        $descripcion = trim($data['descripcion'] ?? '');
        $precio = floatval($data['precio'] ?? 0);
        $duracion = floatval($data['duracion'] ?? 0);
        $categoria = trim($data['categoria'] ?? '');
        $estado = $data['estado'] ?? 'activo';
        
        if ($id <= 0 || empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            break;
        }
        try {
            $stmt = $pdo->prepare("UPDATE servicios SET nombre=?, descripcion=?, precio=?, duracion=?, categoria=?, estado=? WHERE id_servicio=?");
            $stmt->execute([$nombre, $descripcion, $precio, $duracion, $categoria, $estado, $id]);
            echo json_encode(['success' => true, 'message' => 'Servicio actualizado exitosamente']);
        } catch (PDOException $e) {
            error_log("Error actualizando servicio: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar servicio']);
        }
        break;

    case 'delete_service':
        $id = $data['id'] ?? 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM servicios WHERE id_servicio=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Servicio eliminado exitosamente']);
        } catch (PDOException $e) {
            error_log("Error eliminando servicio: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar servicio']);
        }
        break;

    case 'list_services':
    $search = trim($data['search'] ?? '');
    $page = max(1, intval($data['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $params = [];
    $where = "";
    if ($search !== '') {
        $where = "WHERE nombre LIKE ?";
        $params = ["%$search%"];
    }

    $query = "SELECT * FROM servicios $where ORDER BY id_servicio DESC LIMIT $limit OFFSET $offset";

    error_log("Consulta servicios: $query con params: " . json_encode($params));

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM servicios $where");
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => $servicios,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
    break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>