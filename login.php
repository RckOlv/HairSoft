<?php
session_start();
require_once 'config/conexion.php'; 
require_once 'permissions.php';

// Redireccionar si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Variables para mostrar mensajes
$error_message = '';
$success_message = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error_message = 'Por favor completa todos los campos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Por favor ingresa un email válido';
    } else {
        // Conectar a la base de datos
        try {
            // Buscar usuario en la base de datos (usando los nombres correctos de tu BD)
            $stmt = $pdo->prepare("
                SELECT u.id_usuario, u.nombre, u.email, u.password, u.id_rol, r.nombre_rol
                FROM usuarios u 
                LEFT JOIN roles r ON u.id_rol = r.id_rol 
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['nombre_rol'];
                $_SESSION['user_role_id'] = $user['id_rol'];
        

                // Actualizar último login (si tienes esta columna)
                try {
                    $update_stmt = $pdo->prepare("UPDATE usuarios SET updated_at = NOW() WHERE id_usuario = ?");
                    $update_stmt->execute([$user['id_usuario']]);
                } catch (PDOException $e) {
                    // Si no existe la columna ultimo_login, ignorar el error
                }
                
                // Manejar "Recordar usuario" (opcional - requiere tabla remember_tokens)
                if ($remember) {
                    try {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Guardar token en BD (solo si tienes la tabla remember_tokens)
                        $token_stmt = $pdo->prepare("
                            INSERT INTO remember_tokens (id_usuario, token, expires_at) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
                        ");
                        $token_stmt->execute([$user['id_usuario'], $token, $expires, $token, $expires]);
                        
                        // Crear cookie
                        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                    } catch (PDOException $e) {
                        // Si no existe la tabla remember_tokens, ignorar
                    }
                }
                
                // Redireccionar al dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Email o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            error_log("Error de login: " . $e->getMessage());
            $error_message = 'Error de conexión. Intenta nuevamente.';
        }
    }
}

// Procesar formulario de recuperación de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['forgot_email']);
    
    if (empty($email)) {
        $error_message = 'Por favor ingresa tu email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Por favor ingresa un email válido';
    } else {
        try {
            // Verificar si el email existe (usando nombre correcto de campo)
            $stmt = $pdo->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generar token de recuperación
                $reset_token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Guardar token en BD (solo si tienes la tabla password_resets)
                try {
                    $token_stmt = $pdo->prepare("
                        INSERT INTO password_resets (id_usuario, token, expires_at) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
                    ");
                    $token_stmt->execute([$user['id_usuario'], $reset_token, $expires, $reset_token, $expires]);
                    
                    // Enviar email (aquí deberías usar PHPMailer o similar)
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $reset_token;
                    
                    // Por ahora solo mostramos mensaje de éxito
                    $success_message = 'Se ha enviado un enlace de recuperación a tu email';
                } catch (PDOException $e) {
                    $success_message = 'Se ha enviado un enlace de recuperación a tu email (simulado)';
                }
            } else {
                $error_message = 'No se encontró ninguna cuenta con ese email';
            }
        } catch (PDOException $e) {
            error_log("Error de recuperación: " . $e->getMessage());
            $error_message = 'Error al procesar la solicitud';
        }
    }
}

// Verificar cookie de "recordar usuario"
if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.nombre, u.email, r.nombre_rol
            FROM remember_tokens rt
            JOIN usuarios u ON rt.id_usuario = u.id_usuario
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$_COOKIE['remember_token']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Auto-login
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['nombre_rol'];            
            header('Location: dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        // Si hay error, eliminar cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HairSoft - Login</title>
	<link rel="stylesheet" href="css/styles_login.css">
</head>
<body>
    <!-- Elementos decorativos del salón -->
    <div class="salon-element mirror" style="top: 20%; left: 10%;"></div>
    <div class="salon-element chair" style="top: 45%; left: 15%;"></div>
    <div class="salon-element mirror" style="top: 25%; right: 20%;"></div>
    <div class="salon-element chair" style="top: 50%; right: 25%;"></div>
    <div class="salon-element light" style="top: 10%; left: 30%;"></div>
    <div class="salon-element light" style="top: 15%; right: 40%;"></div>

    <div class="header">
        <div class="logo">HairSoft</div>
    </div>

    <div class="login-container">
        <div class="login-box">
            <h2 class="login-title">Login</h2>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form id="loginForm" method="POST" action="">
                <input type="hidden" name="login" value="1">
                
                <div class="form-group">
                    <input type="email" 
                           name="email" 
                           class="form-input" 
                           placeholder="Ingrese su correo electrónico" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <input type="password" 
                           name="password" 
                           class="form-input" 
                           placeholder="Ingrese su contraseña" 
                           required>
                </div>

                <div class="remember-container">
                    <input type="checkbox" 
                           id="remember" 
                           name="remember" 
                           class="remember-checkbox"
                           <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                    <label for="remember" class="remember-label">Recordar nombre de usuario</label>
                </div>

                <button type="submit" class="login-btn">Ingresar</button>

                <div class="">
                    <a href="#" onclick="showForgotPassword()">¿Olvidaste tu contraseña?</a>
                </div>

                <div class="register-link">
                    ¿No tenés una cuenta? <a href="register.php">¡REGISTRATE!</a>
                </div>
            </form>

            <!-- Formulario de Recuperación de Contraseña -->
            <form id="forgotPasswordForm" class="forgot-password-form" method="POST" action="">
                <input type="hidden" name="forgot_password" value="1">
                
                <h3>Recuperar Contraseña</h3>
                <p>Ingresa tu email para recibir un enlace de recuperación</p>
                
                <div class="form-group">
                    <input type="email" 
                           name="forgot_email" 
                           class="form-input" 
                           placeholder="Ingrese su correo electrónico" 
                           required>
                </div>

                <button type="submit" class="login-btn">Enviar Enlace</button>
                
                <div class="back-to-login" onclick="showLogin()">
                    ← Volver al login
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-item">
                <div class="footer-icon">
                    <img src="https://img.icons8.com/?size=100&id=118497&format=png&color=000000" alt="Icono" width="45" height="45">
                </div>
                <span>Los Últimos Serán Los Primeros</span>
            </div>
            <div class="footer-item">
                <img src="https://img.icons8.com/?size=100&id=32323&format=png&color=000000" alt="Icono" width="45" height="45">
                <span>Los Últimos Serán Los Primeros</span>
            </div>
            <div class="footer-item">
                <div class="footer-icon">
                    <img src="https://img.icons8.com/?size=100&id=16713&format=png&color=000000" alt="Icono" width="45" height="45">
                </div>
                <span>3755-713031</span>
            </div>
        </div>
        <div class="address">
            Avenida Libertador 928 - San Vicente - Misiones
        </div>
    </footer>

    <script>
        function showForgotPassword() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('forgotPasswordForm').classList.add('active');
        }

        function showLogin() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('forgotPasswordForm').classList.remove('active');
        }

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>