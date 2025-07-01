<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HairSoft - Los Últimos Serán Los Primeros</title>
    <link rel="stylesheet" href="css/styles_index.css" />
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>

    <header class="header">
        <div class="nav-container">
            <div class="logo"> <a href="index.php">HairSoft</a></div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="#Nuestros Servicios" class="nav-link">Servicios</a></li>
                    <li class="nav-item"><a href="#productos" class="nav-link">Productos</a></li>
                    <li class="nav-item"><a href="#turnos" class="nav-link">Turnos</a></li>
                </ul>
            </nav>

            <div class="auth-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="welcome-msg">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <a href="logout.php" class="btn btn-logout">Cerrar sesión</a>
                <?php else: ?>
                    <a href="Formulario_Reg_vista_cliente.php" class="btn btn-register">Registrarse</a>
                    <a href="login.php" class="btn btn-login">Ingresar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="hero-section">
            <div class="welcome-message">
                <h1 class="welcome-title">"Los Últimos Serán Los Primeros"</h1>
                <p class="welcome-subtitle">Tu destino para un cabello saludable. Ofrecemos una amplia gama de servicios para satisfacer todas tus necesidades de cuidado del cabello.</p>
                <img src="https://images.unsplash.com/photo-1585747860715-2ba37e788b70?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Peluquería Los Últimos Serán Los Primeros" class="barbershop-image" id="barbershopImage" />
            </div>
        </section>

        <section id="Nuestros Servicios" class="services-section">
            <h2 class="services-title">Nuestros Servicios</h2>
            <div class="services-grid">

                <div class="service-card" onclick="selectService('cortes')">
                    <img src="corte.png" alt="Corte de cabello" width="67%" height="67%" />
                    <h3 class="service-name">Cortes</h3>
                    <p class="service-description">Cortes modernos y clásicos adaptados a tu estilo personal</p>
                </div>

                <div class="service-card" onclick="selectService('barbas')">
                    <img src="barba.jpg" alt="Barba" width="67%" height="67%" />
                    <h3 class="service-name">Barbas</h3>
                    <p class="service-description">Arreglo y diseño de barbas con técnicas profesionales</p>
                </div>

                <div class="service-card" onclick="selectService('tinturas')">
                    <img src="tintura.jpg" alt="Tintura" width="67%" height="67%" />
                    <h3 class="service-name">Tinturas</h3>
                    <p class="service-description">Coloración profesional para renovar tu look</p>
                </div>

                <div class="service-card" onclick="selectService('perfilados')">
                    <img src="ceja.jpg" alt="Perfilado" width="67%" height="67%" />
                    <h3 class="service-name">Perfilados</h3>
                    <p class="service-description">Definición perfecta de contornos y acabados</p>
                </div>

            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-item">
                <div class="footer-icon">
                    <img src="https://img.icons8.com/?size=100&id=118497&format=png&color=000000" alt="Icono" width="45" height="45" />
                </div>
                <span>Los Últimos Serán Los Primeros</span>
            </div>
            <div class="footer-item">
                <img src="https://img.icons8.com/?size=100&id=32323&format=png&color=000000" alt="Icono" width="45" height="45" />
                <span>Los Últimos Serán Los Primeros</span>
            </div>
            <div class="footer-item">
                <div class="footer-icon">
                    <img src="https://img.icons8.com/?size=100&id=16713&format=png&color=000000" alt="Icono" width="45" height="45" />
                </div>
                <span>3755-713031</span>
            </div>
        </div>
        <div class="address">Avenida Libertador 928 - San Vicente - Misiones</div>
    </footer>

</body>
</html>
