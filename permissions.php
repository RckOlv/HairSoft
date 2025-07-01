<?php
function hasPermission($pdo, $user_id, $permission_name) {
    // ✅ El usuario con ID 1 (admin) tiene todos los permisos automáticamente
    if ($user_id == 1) {
        return true;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            JOIN rol_permiso rp ON r.id_rol = rp.id_rol
            JOIN permisos p ON rp.id_permiso = p.id
            WHERE u.id_usuario = ? AND p.nombre = ?
        ");
        $stmt->execute([$user_id, $permission_name]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Error checking permission: " . $e->getMessage());
        return false;
    }
}

function getRolesWithPermissions($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY nombre_rol");
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($roles as &$rol) {
            $stmt2 = $pdo->prepare("
                SELECT p.id, p.nombre FROM rol_permiso rp
                JOIN permisos p ON rp.id_permiso = p.id
                WHERE rp.id_rol = ?
            ");
            $stmt2->execute([$rol['id_rol']]);
            $permisos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            $rol['permisos'] = [];
            foreach ($permisos as $permiso) {
                $rol['permisos'][] = $permiso['nombre'];
            }
        }

        return $roles;
    } catch (Exception $e) {
        return [];
    }
}

function getAllPermisos($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, descripcion FROM permisos ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getAllPermisosList($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, nombre FROM permisos ORDER BY nombre");
        $stmt->execute();
        $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($permisos as $permiso) {
            $result[$permiso['id']] = $permiso['nombre'];
        }

        return $result;
    } catch (Exception $e) {
        return [];
    }
}

function createRole($pdo, $nombre, $descripcion, $permisos = []) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO roles (nombre_rol, descripcion) VALUES (?, ?)");
        $stmt->execute([$nombre, $descripcion]);
        $id_rol = $pdo->lastInsertId();

        $stmtPermiso = $pdo->prepare("INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)");
        foreach ($permisos as $id_permiso) {
            $stmtPermiso->execute([$id_rol, $id_permiso]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function updateRole($pdo, $id, $nombre, $descripcion, $permisos = []) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE roles SET nombre_rol = ?, descripcion = ? WHERE id_rol = ?");
        $stmt->execute([$nombre, $descripcion, $id]);

        $stmtDel = $pdo->prepare("DELETE FROM rol_permiso WHERE id_rol = ?");
        $stmtDel->execute([$id]);

        $stmtIns = $pdo->prepare("INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)");
        foreach ($permisos as $id_permiso) {
            $stmtIns->execute([$id, $id_permiso]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function deleteRole($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id_rol = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }

        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM rol_permiso WHERE id_rol = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM roles WHERE id_rol = ?")->execute([$id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function createPermiso($pdo, $nombre, $descripcion) {
    try {
        $stmt = $pdo->prepare("INSERT INTO permisos (nombre, descripcion) VALUES (?, ?)");
        return $stmt->execute([$nombre, $descripcion]);
    } catch (Exception $e) {
        return false;
    }
}

function updatePermiso($pdo, $id, $nombre, $descripcion) {
    try {
        $stmt = $pdo->prepare("UPDATE permisos SET nombre = ?, descripcion = ? WHERE id = ?");
        return $stmt->execute([$nombre, $descripcion, $id]);
    } catch (Exception $e) {
        return false;
    }
}

function deletePermiso($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM permisos WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}

function getRoleById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id_rol = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role) {
            $stmt2 = $pdo->prepare("SELECT id_permiso FROM rol_permiso WHERE id_rol = ?");
            $stmt2->execute([$id]);
            $permisos = $stmt2->fetchAll(PDO::FETCH_COLUMN);
            $role['permisos'] = $permisos;
        }

        return $role;
    } catch (Exception $e) {
        return false;
    }
}

function getPermisoById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM permisos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}
?>
