<?php
header('Content-Type: application/json');

try {
    // Supongo que ya tenés la conexión PDO en $pdo
    // $pdo = new PDO(...);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

$data = $_POST;

$action = $data['action'] ?? '';

switch ($action) {

    // --- Crear usuario ---
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

    // --- Actualizar usuario ---
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

        // Verificar duplicados en otros usuarios
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE (email = ? OR documento = ?) AND id_usuario != ?");
        $stmtCheck->execute([$email, $documento, $id_usuario]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email o documento ya registrado en otro usuario.']);
            break;
        }

        try {
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

    // --- Crear o actualizar rol ---
    case 'create_role':
    case 'update_role':
        $id_rol = $data['id'] ?? null;
        $nombre_rol = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $permisos = $data['permisos'] ?? [];

        // Validaciones
        if (!$nombre_rol || strlen($nombre_rol) < 3 || strlen($nombre_rol) > 50) {
            echo json_encode(['success' => false, 'message' => 'El nombre del rol debe tener entre 3 y 50 caracteres.']);
            break;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre_rol)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del rol solo puede contener letras y espacios.']);
            break;
        }
        if (strlen($descripcion) > 255) {
            echo json_encode(['success' => false, 'message' => 'La descripción no puede superar los 255 caracteres.']);
            break;
        }
        if (!is_array($permisos) || count($permisos) == 0) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar al menos un permiso para el rol.']);
            break;
        }

        // Verificar duplicado nombre rol (cuando es creación o actualización)
        if ($action == 'create_role') {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE nombre_rol = ?");
            $stmtCheck->execute([$nombre_rol]);
        } else {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE nombre_rol = ? AND id_rol != ?");
            $stmtCheck->execute([$nombre_rol, $id_rol]);
        }
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'El nombre del rol ya está registrado.']);
            break;
        }

        try {
            if ($action == 'create_role') {
                $stmt = $pdo->prepare("INSERT INTO roles (nombre_rol, descripcion) VALUES (?, ?)");
                $stmt->execute([$nombre_rol, $descripcion]);
                $newRoleId = $pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare("UPDATE roles SET nombre_rol = ?, descripcion = ? WHERE id_rol = ?");
                $stmt->execute([$nombre_rol, $descripcion, $id_rol]);
                $newRoleId = $id_rol;
            }

            // Actualizar permisos del rol: Primero eliminar los permisos actuales
            $stmtDel = $pdo->prepare("DELETE FROM rol_permiso WHERE id_rol = ?");
            $stmtDel->execute([$newRoleId]);

            // Insertar los permisos nuevos
            $stmtInsert = $pdo->prepare("INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)");
            foreach ($permisos as $permisoId) {
                $stmtInsert->execute([$newRoleId, $permisoId]);
            }

            echo json_encode(['success' => true, 'message' => ($action == 'create_role' ? 'Rol creado exitosamente.' : 'Rol actualizado exitosamente.')]);

        } catch (PDOException $e) {
            error_log("Error en roles: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al guardar el rol.']);
        }
        break;

    // --- Obtener rol con permisos para editar ---
    case 'get_role':
        $id_rol = $data['id'] ?? '';
        if (!$id_rol) {
            echo json_encode(['success' => false, 'message' => 'ID de rol no especificado.']);
            break;
        }
        try {
            $stmt = $pdo->prepare("SELECT id_rol, nombre_rol, descripcion FROM roles WHERE id_rol = ?");
            $stmt->execute([$id_rol]);
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$rol) {
                echo json_encode(['success' => false, 'message' => 'Rol no encontrado.']);
                break;
            }
            $stmtPermisos = $pdo->prepare("SELECT id_permiso FROM rol_permiso WHERE id_rol = ?");
            $stmtPermisos->execute([$id_rol]);
            $permisos = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['success' => true, 'data' => [
                'id_rol' => $rol['id_rol'],
                'nombre_rol' => $rol['nombre_rol'],
                'descripcion' => $rol['descripcion'],
                'permisos' => $permisos
            ]]);
        } catch (PDOException $e) {
            error_log("Error get_role: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener datos del rol.']);
        }
        break;

    // --- Eliminar rol ---
    case 'delete_role':
        $id_rol = $data['id'] ?? '';
        if (!$id_rol) {
            echo json_encode(['success' => false, 'message' => 'ID de rol no especificado.']);
            break;
        }
        try {
            // Eliminar permisos vinculados primero
            $stmtDel = $pdo->prepare("DELETE FROM rol_permiso WHERE id_rol = ?");
            $stmtDel->execute([$id_rol]);
            // Eliminar rol
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id_rol = ?");
            $stmt->execute([$id_rol]);
            echo json_encode(['success' => true, 'message' => 'Rol eliminado exitosamente.']);
        } catch (PDOException $e) {
            error_log("Error delete_role: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el rol.']);
        }
        break;

    // --- Crear o actualizar permiso ---
    case 'create_permiso':
    case 'update_permiso':
        $id_permiso = $data['id'] ?? null;
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        // Validaciones
        if (!$nombre || strlen($nombre) < 3 || strlen($nombre) > 50) {
            echo json_encode(['success' => false, 'message' => 'El nombre del permiso debe tener entre 3 y 50 caracteres.']);
            break;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del permiso solo puede contener letras y espacios.']);
            break;
        }
        if (strlen($descripcion) > 255) {
            echo json_encode(['success' => false, 'message' => 'La descripción no puede superar los 255 caracteres.']);
            break;
        }

        // Verificar duplicado nombre permiso
        if ($action == 'create_permiso') {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM permisos WHERE nombre = ?");
            $stmtCheck->execute([$nombre]);
        } else {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM permisos WHERE nombre = ? AND id != ?");
            $stmtCheck->execute([$nombre, $id_permiso]);
        }
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'El nombre del permiso ya está registrado.']);
            break;
        }

        try {
            if ($action == 'create_permiso') {
                $stmt = $pdo->prepare("INSERT INTO permisos (nombre, descripcion) VALUES (?, ?)");
                $stmt->execute([$nombre, $descripcion]);
            } else {
                $stmt = $pdo->prepare("UPDATE permisos SET nombre = ?, descripcion = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $id_permiso]);
            }
            echo json_encode(['success' => true, 'message' => ($action == 'create_permiso' ? 'Permiso creado exitosamente.' : 'Permiso actualizado exitosamente.')]);
        } catch (PDOException $e) {
            error_log("Error en permisos: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al guardar el permiso.']);
        }
        break;

    // --- Obtener permiso para editar ---
    case 'get_permiso':
        $id_permiso = $data['id'] ?? '';
        if (!$id_permiso) {
            echo json_encode(['success' => false, 'message' => 'ID de permiso no especificado.']);
            break;
        }
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, descripcion FROM permisos WHERE id = ?");
            $stmt->execute([$id_permiso]);
            $permiso = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$permiso) {
                echo json_encode(['success' => false, 'message' => 'Permiso no encontrado.']);
                break;
            }
            echo json_encode(['success' => true, 'data' => $permiso]);
        } catch (PDOException $e) {
            error_log("Error get_permiso: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener datos del permiso.']);
        }
        break;

    // --- Eliminar permiso ---
    case 'delete_permiso':
        $id_permiso = $data['id'] ?? '';
        if (!$id_permiso) {
            echo json_encode(['success' => false, 'message' => 'ID de permiso no especificado.']);
            break;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM permisos WHERE id = ?");
            $stmt->execute([$id_permiso]);
            echo json_encode(['success' => true, 'message' => 'Permiso eliminado exitosamente.']);
        } catch (PDOException $e) {
            error_log("Error delete_permiso: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el permiso.']);
        }
        break;

    // --- Buscar roles ---
    case 'search_roles':
        $search = trim($data['search'] ?? '');
        try {
            $sql = "SELECT r.id_rol, r.nombre_rol, r.descripcion, 
                    JSON_OBJECTAGG(p.nombre, 'activo') AS permisos_json
                    FROM roles r
                    LEFT JOIN rol_permiso rp ON r.id_rol = rp.id_rol
                    LEFT JOIN permisos p ON rp.id_permiso = p.id
                    WHERE r.nombre_rol LIKE ?
                    GROUP BY r.id_rol
                    ORDER BY r.nombre_rol ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$search%"]);
            $roles = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permisos = [];
                if ($row['permisos_json']) {
                    $permisos = json_decode($row['permisos_json'], true);
                }
                $roles[] = [
                    'id_rol' => $row['id_rol'],
                    'nombre_rol' => $row['nombre_rol'],
                    'descripcion' => $row['descripcion'],
                    'permisos' => $permisos
                ];
            }
            echo json_encode(['success' => true, 'data' => $roles]);
        } catch (PDOException $e) {
            error_log("Error search_roles: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al buscar roles.']);
        }
        break;

    // --- Buscar permisos ---
    case 'search_permisos':
        $search = trim($data['search'] ?? '');
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, descripcion FROM permisos WHERE nombre LIKE ? ORDER BY nombre ASC");
            $stmt->execute(["%$search%"]);
            $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $permisos]);
        } catch (PDOException $e) {
            error_log("Error search_permisos: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al buscar permisos.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
