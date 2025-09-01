<?php
/**
 * API para manejar las operaciones de la aplicación Fiesta de Quince
 * Endpoints para subir fotos, obtener galería y manejar códigos QR
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

class ApiQuince {
    private $bd;
    private $carpeta_imagenes = 'imagenes/';

    public function __construct() {
        $this->bd = obtenerBaseDatos()->obtenerConexion();
        
        // Crear carpeta de imágenes si no existe
        if (!file_exists($this->carpeta_imagenes)) {
            mkdir($this->carpeta_imagenes, 0755, true);
        }
    }

    /**
     * Subir una nueva foto a la galería
     */
    public function subirFoto() {
        try {
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió ninguna foto válida');
            }

            $archivo = $_FILES['foto'];
            $nombre_invitado = $_POST['nombre_invitado'] ?? 'Anónimo';

            // Validar tipo de archivo
            $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($archivo['type'], $tipos_permitidos)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP');
            }

            // Validar tamaño (máximo 10MB)
            if ($archivo['size'] > 10 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 10MB');
            }

            // Generar nombre único para el archivo
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_archivo = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
            $ruta_completa = $this->carpeta_imagenes . $nombre_archivo;

            // Mover archivo a la carpeta de imágenes
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                throw new Exception('Error al guardar la imagen en el servidor');
            }

            // Guardar información en la base de datos
            $sql = "INSERT INTO fotos_galeria (nombre_archivo, url_imagen, nombre_invitado) VALUES (?, ?, ?)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$nombre_archivo, $ruta_completa, $nombre_invitado]);

            $this->responderExito([
                'mensaje' => '¡Foto subida con éxito!',
                'id' => $this->bd->lastInsertId(),
                'url_imagen' => $ruta_completa,
                'nombre_invitado' => $nombre_invitado
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Obtener todas las fotos de la galería
     */
    public function obtenerGaleria() {
        try {
            $sql = "SELECT id, nombre_archivo, url_imagen, nombre_invitado, fecha_subida 
                    FROM fotos_galeria 
                    WHERE activa = TRUE 
                    ORDER BY fecha_subida DESC";
            
            $stmt = $this->bd->prepare($sql);
            $stmt->execute();
            $fotos = $stmt->fetchAll();

            // Convertir rutas a URLs completas
            foreach ($fotos as &$foto) {
                $foto['url_completa'] = $this->obtenerUrlCompleta($foto['url_imagen']);
                $foto['fecha_formateada'] = date('d/m/Y H:i', strtotime($foto['fecha_subida']));
            }

            $this->responderExito([
                'fotos' => $fotos,
                'total' => count($fotos)
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Generar y guardar código QR
     */
    public function generarCodigoQR() {
        try {
            $url = $_POST['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL requerida para generar el código QR');
            }

            // Verificar si ya existe un QR para esta URL
            $sql = "SELECT id FROM codigos_qr WHERE url_generada = ? AND usado = FALSE";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$url]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('Ya existe un código QR activo para esta URL');
            }

            // Guardar el código QR en la base de datos
            $sql = "INSERT INTO codigos_qr (codigo_qr, url_generada) VALUES (?, ?)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute(['QR_' . uniqid(), $url]);

            $this->responderExito([
                'mensaje' => 'Código QR generado exitosamente',
                'id' => $this->bd->lastInsertId(),
                'url' => $url
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Marcar código QR como usado (para ocultarlo)
     */
    public function marcarQRUsado() {
        try {
            $url = $_POST['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL requerida');
            }

            $sql = "UPDATE codigos_qr SET usado = TRUE, fecha_uso = NOW() WHERE url_generada = ? AND usado = FALSE";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$url]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('No se encontró un código QR activo para esta URL');
            }

            $this->responderExito(['mensaje' => 'Código QR marcado como usado']);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Verificar si existe un QR activo para la URL actual
     */
    public function verificarQRActivo() {
        try {
            $url = $_GET['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL requerida');
            }

            $sql = "SELECT id FROM codigos_qr WHERE url_generada = ? AND usado = FALSE";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$url]);

            $this->responderExito([
                'qr_activo' => $stmt->rowCount() > 0
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Obtener URL completa para una imagen
     */
    private function obtenerUrlCompleta($ruta_relativa) {
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $servidor = $_SERVER['HTTP_HOST'];
        $directorio = dirname($_SERVER['REQUEST_URI']);
        return $protocolo . '://' . $servidor . $directorio . '/' . $ruta_relativa;
    }

    /**
     * Responder con éxito
     */
    private function responderExito($datos) {
        http_response_code(200);
        echo json_encode([
            'exito' => true,
            'datos' => $datos
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Responder con error
     */
    private function responderError($mensaje) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'error' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Manejar las peticiones
try {
    $api = new ApiQuince();
    $accion = $_GET['accion'] ?? '';

    switch ($accion) {
        case 'subir_foto':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $api->subirFoto();
            break;

        case 'obtener_galeria':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            $api->obtenerGaleria();
            break;

        case 'generar_qr':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $api->generarCodigoQR();
            break;

        case 'marcar_qr_usado':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $api->marcarQRUsado();
            break;

        case 'verificar_qr':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            $api->verificarQRActivo();
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
