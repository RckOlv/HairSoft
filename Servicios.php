<?php
session_start();
require_once 'config/conexion.php';
require_once 'permissions.php';

$pdo = getConnection();

// Validar sesión y permiso para gestionar servicios
if (!isset($_SESSION['user_id']) || !hasPermission($pdo, $_SESSION['user_id'], 'Gestionar Servicios')) {
    header('Location: index.php');
    exit();
}

// Obtener servicios desde BD
$stmt = $pdo->query("SELECT * FROM servicios ORDER BY id_servicio ASC");
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HairSoft - Gestión de Servicios</title>
    <link rel="stylesheet" href="css/styles_Servicios.css" />
</head>
<body>
<!-- Header -->
<div class="header">
    <a href="index.php" class="logo">
        HairSoft<span class="icon-barber">✂️</span>
    </a>
    <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="Reg_Usuarios_Lista_Admin.php">Usuarios</a>
        <a href="Servicios.php" class="active">Servicios</a>
        <a href="#">Productos</a>
        <a href="#">Turnos</a>
        <a href="Permiso_Rol.php">Seguridad</a>
    </nav>
    <div class="user-info">
        <div class="user-avatar"></div>
        <span>Admin</span>
        <div class="online-indicator"></div>
        <button id="logoutBtn" class="logout-btn">Cerrar Sesión</button>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Servicios</h2>
            <button id="registerServiceBtn" class="create-btn">Registrar Servicio</button>
        </div>
        
        <div class="search-box">
            <input type="text" class="search-input" placeholder="Buscar servicios...">
            <span class="search-icon">🔍</span>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Duración (hs)</th>
                        <th>Precio ($)</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="servicesTableBody">
                    <!-- Aquí JS cargará los servicios desde la variable PHP -->
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <button class="page-btn">‹</button>
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
            <button class="page-btn">›</button>
        </div>
    </div>
</div>

<!-- Modal para registro de servicios -->
<div class="modal-overlay" id="serviceModalOverlay">
    <div class="modal">
        <button id="closeServiceModal" class="close-btn">×</button>
        <div class="modal-header">
            <h2 class="modal-title" id="serviceModalTitle">Registrar Nuevo Servicio</h2>
        </div>
        
        <form id="serviceRegistrationForm">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="service-name">Nombre: <span class="required">*</span></label>
                        <input type="text" id="service-name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="service-category">Categoría: <span class="required">*</span></label>
                        <select id="service-category" class="form-select" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="Corte">Corte</option>
                            <option value="Color">Color</option>
                            <option value="Tratamiento">Tratamiento</option>
                            <option value="Manicura">Manicura</option>
                            <option value="Pedicura">Pedicura</option>
                            <option value="Peinado">Peinado</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="service-description">Descripción: <span class="required">*</span></label>
                        <textarea id="service-description" class="form-textarea" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="service-duration">Duración (horas): <span class="required">*</span></label>
                        <input type="number" id="service-duration" class="form-input time-input" step="0.5" min="0.5" max="8" required>
                    </div>
                    <div class="form-group price-input">
                        <label for="service-price">Precio ($): <span class="required">*</span></label>
                        <input type="number" id="service-price" class="form-input" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="service-status">Estado: <span class="required">*</span></label>
                        <select id="service-status" class="form-select" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="required-note">Campos obligatorios (*)</div>
            </div>
            <button type="submit" id="submitServiceBtn" class="submit-btn">Registrar Servicio</button>
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

<script>
    // Pasamos la variable PHP servicios al JS
    const servicios = <?php echo json_encode($servicios, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="js/script_Servicios.js"></script>

</body>
</html>
