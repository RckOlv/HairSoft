<?php
// config/conexion.php

function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // Configuración de la base de datos
            $host = 'localhost';
            $dbname = 'peluprueba'; // Cambia por el nombre de tu base de datos
            $username = 'root';      // Usuario de tu base de datos
            $password = '';          // Contraseña de tu base de datos (vacía por defecto en XAMPP)
            $charset = 'utf8mb4';

            // DSN (Data Source Name)
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            // Opciones de PDO
            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Crear la conexión PDO
            $pdo = new PDO($dsn, $username, $password, $opciones);
            
        } catch (PDOException $e) {
            // En caso de error, mostrar mensaje y detener ejecución
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// También crear la variable global $pdo para compatibilidad con código existente
$pdo = getConnection();
?>