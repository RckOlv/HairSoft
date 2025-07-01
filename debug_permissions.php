<?php
session_start();
require_once 'config/conexion.php'; 
require_once 'permissions.php';

$pdo = getConnection();

echo "<h2>Debug de Permisos - HairSoft</h2>";
echo "<pre>";

// 1. Verificar sesión
echo "=== INFORMACIÓN DE SESIÓN ===\n";
if (isset($_SESSION['user_id'])) {
    echo "✓ Usuario logueado - ID: " . $_SESSION['user_id'] . "\n";
    echo "✓ Nombre: " . ($_SESSION['user_name'] ?? 'No definido') . "\n";
    echo "✓ Email: " . ($_SESSION['user_email'] ?? 'No definido') . "\n";
    echo "✓ Rol: " . ($_SESSION['user_role'] ?? 'No definido') . "\n";
} else {
    echo "✗ No hay sesión activa\n";
    echo "Contenido de \$_SESSION:\n";
    print_r($_SESSION);
    echo "\n=== FIN DEBUG ===";
    exit();
}

// 2. Verificar información del usuario en la base de datos
echo "\n=== INFORMACIÓN DEL USUARIO EN BD ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT u.*, r.nombre_rol, r.permisos 
        FROM usuarios u 
        LEFT JOIN roles r ON u.id_rol = r.id_rol 
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_info) {
        echo "✓ Usuario encontrado en BD\n";
        echo "- ID: " . $user_info['id_usuario'] . "\n";
        echo "- Nombre: " . $user_info['nombre_usuario'] . "\n";
        echo "- Email: " . $user_info['email'] . "\n";
        echo "- ID Rol: " . $user_info['id_rol'] . "\n";
        echo "- Nombre Rol: " . $user_info['nombre_rol'] . "\n";
        echo "- Permisos (JSON): " . $user_info['permisos'] . "\n";
        
        // Decodificar permisos
        if (!empty($user_info['permisos'])) {
            $permisos_decoded = json_decode($user_info['permisos'], true);
            echo "- Permisos (decodificados):\n";
            print_r($permisos_decoded);
        } else {
            echo "- No tiene permisos asignados\n";
        }
    } else {
        echo "✗ Usuario NO encontrado en BD\n";
    }
} catch (Exception $e) {
    echo "✗ Error al consultar usuario: " . $e->getMessage() . "\n";
}

// 3. Probar función hasPermission
echo "\n=== PRUEBA DE PERMISOS ===\n";
$permisos_a_probar = [
    'Gestionar Seguridad',
    'Gestionar Usuarios',
    'Gestionar Servicios',
    'Gestionar Productos',
    'Gestionar Turnos'
];

foreach ($permisos_a_probar as $permiso) {
    $tiene_permiso = hasPermission($pdo, $_SESSION['user_id'], $permiso);
    echo "- $permiso: " . ($tiene_permiso ? '✓ SÍ' : '✗ NO') . "\n";
}

// 4. Mostrar todos los roles disponibles
echo "\n=== ROLES DISPONIBLES ===\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY id_rol");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $rol) {
        echo "- ID: " . $rol['id_rol'] . " | Nombre: " . $rol['nombre_rol'] . "\n";
        echo "  Permisos: " . $rol['permisos'] . "\n\n";
    }
} catch (Exception $e) {
    echo "Error al obtener roles: " . $e->getMessage() . "\n";
}

// 5. Mostrar todos los permisos disponibles
echo "\n=== PERMISOS DISPONIBLES ===\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM permisos ORDER BY id");
    $stmt->execute();
    $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($permisos as $permiso) {
        echo "- ID: " . $permiso['id'] . " | Nombre: " . $permiso['nombre'] . "\n";
        echo "  Descripción: " . $permiso['descripcion'] . "\n\n";
    }
} catch (Exception $e) {
    echo "Error al obtener permisos: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<p><a href='Permiso_Rol.php'>Ir a Permiso_Rol.php</a></p>";
echo "<p><a href='index.php'>Ir a index.php</a></p>";
?>