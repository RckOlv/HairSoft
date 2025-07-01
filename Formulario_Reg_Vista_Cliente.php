<?php
session_start();
require_once 'config/conexion.php';
require_once 'permissions.php';

// Si el usuario ya está logueado, redirigir a página principal
if (isset($_SESSION['user_id'])) {
    header('Location: index.php'); // O la página que uses para usuarios logueados
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HairSoft - Registrar Usuario</title>
    <link rel="stylesheet" href="css/styles_Formulario_Reg_Vista_Cliente.css" />
</head>
<body>
<header class="header">
    <div class="nav-container">
        <div class="logo"><a href="index.php">HairSoft</a></div>
    </div>
</header>
<br />
<div class="main-content">
    <div class="section">
        <div class="form-title-container">
            <h2 class="form-title" style="text-align: center;">Registrar Cliente</h2>
            <br />
        </div>
        <form id="userRegistrationForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="first-name">Nombre: <span class="required">*</span></label>
                    <input type="text" id="first-name" class="form-input" required />
                </div>
                <div class="form-group">
                    <label for="last-name">Apellido: <span class="required">*</span></label>
                    <input type="text" id="last-name" class="form-input" required />
                </div>
                <div class="form-group">
                    <label for="document-type">Tipo de Documento: <span class="required">*</span></label>
                    <select id="document-type" class="form-select" required>
                        <option value="">Seleccione tipo</option>
                        <option value="DNI">DNI (Documento Nacional de Identidad)</option>
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Cedula">Cédula de Identidad</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="document-number">Número de Documento: <span class="required">*</span></label>
                    <input type="text" id="document-number" class="form-input" required />
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico: <span class="required">*</span></label>
                    <input type="email" id="email" class="form-input" required />
                </div>
                <div class="form-group">
                    <label for="phone">Celular:</label>
                    <input type="tel" id="phone" class="form-input" />
                </div>
                <div class="form-group">
                    <label for="password">Contraseña: <span class="required">*</span></label>
                    <input type="password" id="password" class="form-input" required />
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirmar Contraseña: <span class="required">*</span></label>
                    <input type="password" id="confirm-password" class="form-input" required />
                </div>
            </div>
            <div class="required-note">Campos obligatorios (*)</div>
            <button type="submit" class="submit-btn">Registrar Usuario</button>
        </form>
    </div>
</div>
<footer class="footer">
    <div class="footer-item">
        <span class="footer-icon">📞</span>
        <span>+54 3755 60-0000</span>
    </div>
    <div class="footer-item address">
        HairSoft &copy; 2025 - Todos los derechos reservadillos.
    </div>
    <div class="footer-item">
        <span class="footer-icon">📧</span>
        <span>HairSoft@info.com</span>
    </div>
</footer>
<script src="js/script_Formulario_Reg_Vista_Cliente.js"></script>
</body>
</html>
