<?php
/**
 * Configuración de conexión a MySQL con PDO
 * Base de datos para la aplicación de Fiesta de Quince Años
 */

class BaseDatos {
    private $servidor = 'localhost';
    private $nombre_bd = 'fiesta_quince';
    private $usuario = 'root';
    private $contrasena = '';
    private $conexion;

    public function __construct() {
        $this->conectar();
    }

    private function conectar() {
        try {
            $dsn = "mysql:host={$this->servidor};dbname={$this->nombre_bd};charset=utf8mb4";
            $this->conexion = new PDO($dsn, $this->usuario, $this->contrasena);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function obtenerConexion() {
        return $this->conexion;
    }

    /**
     * Crear las tablas necesarias para la aplicación
     */
    public function crearTablas() {
        try {
            // Tabla para las fotos de la galería
            $sql_fotos = "CREATE TABLE IF NOT EXISTS fotos_galeria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre_archivo VARCHAR(255) NOT NULL,
                url_imagen VARCHAR(500) NOT NULL,
                nombre_invitado VARCHAR(100) DEFAULT 'Anónimo',
                fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                activa BOOLEAN DEFAULT TRUE,
                INDEX idx_fecha (fecha_subida),
                INDEX idx_activa (activa)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            // Tabla para los códigos QR generados
            $sql_qr = "CREATE TABLE IF NOT EXISTS codigos_qr (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo_qr TEXT NOT NULL,
                url_generada VARCHAR(500) NOT NULL,
                fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                usado BOOLEAN DEFAULT FALSE,
                fecha_uso TIMESTAMP NULL,
                INDEX idx_usado (usado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->conexion->exec($sql_fotos);
            $this->conexion->exec($sql_qr);

            return true;
        } catch(PDOException $e) {
            throw new Exception("Error al crear tablas: " . $e->getMessage());
        }
    }
}

// Función para obtener una instancia de la base de datos
function obtenerBaseDatos() {
    static $bd = null;
    if ($bd === null) {
        $bd = new BaseDatos();
    }
    return $bd;
}

// Crear las tablas al incluir este archivo
try {
    $base_datos = obtenerBaseDatos();
    $base_datos->crearTablas();
} catch(Exception $e) {
    error_log("Error en db.php: " . $e->getMessage());
}
?>