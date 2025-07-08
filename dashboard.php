<?php
session_start();

require_once 'config/conexion.php';
require_once 'permissions.php'; 

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_role = $_SESSION['user_role'] ?? 'Usuario';

// Verificar si el usuario tiene permiso para acceder al dashboard
if (!hasPermission($pdo, $user_id, 'dashboard_access')) {
    header('Location: sin_permisos.php');
    exit;
}

// Obtener estadísticas desde la BD
try {
    // Contar usuarios registrados
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $stmt->execute();
    $total_usuarios = $stmt->fetchColumn();

    // Contar servicios activos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicios WHERE activo = 1");
    $stmt->execute();
    $total_servicios = $stmt->fetchColumn();

    // Contar turnos de hoy
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM turnos WHERE DATE(fecha_turno) = CURDATE()");
    $stmt->execute();
    $turnos_hoy = $stmt->fetchColumn();

    // Calcular ingresos mensuales (si tienes tabla de pagos o similar)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0) as total 
        FROM pagos 
        WHERE YEAR(fecha_pago) = YEAR(CURDATE()) 
        AND MONTH(fecha_pago) = MONTH(CURDATE())
    ");
    $stmt->execute();
    $ingresos_mensuales = $stmt->fetchColumn();

    // Obtener actividad reciente
    $stmt = $pdo->prepare("
        SELECT 
            'usuario_nuevo' as tipo,
            CONCAT('Nuevo usuario registrado: ', nombre, ' ', apellido) as mensaje,
            fecha_registro as fecha
        FROM usuarios 
        WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            'servicio_actualizado' as tipo,
            CONCAT('Servicio actualizado: \"', nombre_servicio, '\"') as mensaje,
            fecha_actualizacion as fecha
        FROM servicios 
        WHERE fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        ORDER BY fecha DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $actividad_reciente = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Valores por defecto en caso de error
    $total_usuarios = 0;
    $total_servicios = 0;
    $turnos_hoy = 0;
    $ingresos_mensuales = 0;
    $actividad_reciente = [];
}

// Función para formatear tiempo transcurrido
function tiempoTranscurrido($fecha) {
    $tiempo = time() - strtotime($fecha);
    
    if ($tiempo < 60) return 'Hace menos de 1 minuto';
    if ($tiempo < 3600) return 'Hace ' . floor($tiempo/60) . ' minutos';
    if ($tiempo < 86400) return 'Hace ' . floor($tiempo/3600) . ' horas';
    if ($tiempo < 604800) return 'Hace ' . floor($tiempo/86400) . ' días';
    
    return date('d/m/Y H:i', strtotime($fecha));
}

// Función para obtener icono según tipo de actividad
function obtenerIconoActividad($tipo) {
    switch($tipo) {
        case 'usuario_nuevo': return '👤';
        case 'servicio_actualizado': return '✂️';
        case 'permiso_modificado': return '🔒';
        case 'producto_agregado': return '💄';
        default: return '📋';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HairSoft - Dashboard</title>
    <link rel="stylesheet" href="css/styles_dashboard.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">HairSoft<span class="icon-barber">✂️</span></div>
        <nav class="nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            
            <?php if (hasPermission($pdo, $user_id, 'usuarios_ver')): ?>
                <a href="Reg_Usuarios_Lista_Admin.php">Usuarios</a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $user_id, 'servicios_ver')): ?>
                <a href="Servicios.php">Servicios</a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $user_id, 'turnos_ver')): ?>
                <a href="turnos.php">Turnos</a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $user_id, 'permisos_ver')): ?>
                <a href="Permiso_Rol.php">Seguridad</a>
            <?php endif; ?>
            
            <?php if (hasPermission($pdo, $user_id, 'productos_ver')): ?>
                <a href="#">Productos</a>
            <?php endif; ?>
        </nav>
        <div class="user-info">
            <div class="user-avatar"></div>
            <span><?php echo htmlspecialchars($user_name); ?></span>
            <small><?php echo htmlspecialchars($user_role); ?></small>
            <div class="online-indicator"></div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
            <h1 class="welcome-title">Bienvenido, <?php echo htmlspecialchars($user_name); ?></h1>
            <p class="welcome-message">
                Aquí puedes gestionar todos los aspectos de tu peluquería. Revisa las estadísticas, 
                accede rápidamente a las secciones importantes y mantente al tanto de la actividad reciente.
            </p>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">👥</span>
                <div class="stat-value"><?php echo number_format($total_usuarios); ?></div>
                <div class="stat-label">Usuarios registrados</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">✂️</span>
                <div class="stat-value"><?php echo number_format($total_servicios); ?></div>
                <div class="stat-label">Servicios activos</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📅</span>
                <div class="stat-value"><?php echo number_format($turnos_hoy); ?></div>
                <div class="stat-label">Turnos hoy</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">💰</span>
                <div class="stat-value">$<?php echo number_format($ingresos_mensuales, 0, ',', '.'); ?></div>
                <div class="stat-label">Ingresos mensuales</div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="quick-actions">
            <?php if (hasPermission($pdo, $user_id, 'usuarios_ver')): ?>
                <a href="Reg_Usuarios_Lista_Admin.php" class="action-card">
                    <span class="action-icon">👥</span>
                    <h3 class="action-title">Gestión de Usuarios</h3>
                    <p class="action-description">Administra clientes, peluqueros y administradores</p>
                </a>
            <?php endif; ?>

            <?php if (hasPermission($pdo, $user_id, 'servicios_ver')): ?>
                <a href="Servicios.php" class="action-card">
                    <span class="action-icon">✂️</span>
                    <h3 class="action-title">Gestión de Servicios</h3>
                    <p class="action-description">Crea y edita servicios, precios y categorías</p>
                </a>
            <?php endif; ?>

            <?php if (hasPermission($pdo, $user_id, 'permisos_ver')): ?>
                <a href="Permiso_Rol.php" class="action-card">
                    <span class="action-icon">🔒</span>
                    <h3 class="action-title">Gestión de Seguridad</h3>
                    <p class="action-description">Configura roles, permisos y accesos</p>
                </a>
            <?php endif; ?>

            <?php if (hasPermission($pdo, $user_id, 'productos_ver')): ?>
                <a href="#" class="action-card">
                    <span class="action-icon">💄</span>
                    <h3 class="action-title">Gestión de Productos</h3>
                    <p class="action-description">Controla inventario y productos de venta</p>
                </a>
            <?php endif; ?>
        </div>

        <!-- Actividad reciente -->
        <div class="recent-activity">
            <h2 class="section-title"><span class="icon">📋</span> Actividad Reciente</h2>
            <ul class="activity-list">
                <?php if (empty($actividad_reciente)): ?>
                    <li class="activity-item">
                        <span class="activity-icon">ℹ️</span>
                        <div class="activity-content">
                            <p class="activity-message">No hay actividad reciente para mostrar</p>
                            <p class="activity-time">-</p>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($actividad_reciente as $actividad): ?>
                        <li class="activity-item">
                            <span class="activity-icon"><?php echo obtenerIconoActividad($actividad['tipo']); ?></span>
                            <div class="activity-content">
                                <p class="activity-message"><?php echo htmlspecialchars($actividad['mensaje']); ?></p>
                                <p class="activity-time"><?php echo tiempoTranscurrido($actividad['fecha']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-item">
                <div class="footer-icon">
                    <img src="https://img.icons8.com/?size=100&id=118557&format=png&color=000000" alt="Facebook" width="45" height="45">
                </div>
                <span>Los Últimos Serán Los Primeros</span>
            </div>
            <div class="footer-item">
                <img src="https://img.icons8.com/?size=100&id=32323&format=png&color=000000" alt="Instagram" width="45" height="45">
                <span>@hairsoft_oficial</span>
            </div>
            <div class="footer-item">
                <div class="footer-icon">
                    <img src="https://img.icons8.com/?size=100&id=16713&format=png&color=000000" alt="Teléfono" width="45" height="45">
                </div>
                <span>3755-713031</span>
            </div>
        </div>
        <div class="address">
            Avenida Libertador 928 - San Vicente - Misiones
        </div>
    </footer>
    
    <script src="js/script_dashboard.js"></script>
</body>
</html>