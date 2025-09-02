<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['exito' => false, 'error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

class AdminAPI {
    private $bd;
    private $carpeta_imagenes = '../imagenes/';

    public function __construct() {
        $this->bd = obtenerBaseDatos()->obtenerConexion();
    }

    /**
     * Eliminar una foto de la galería
     */
    public function eliminarFoto() {
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                throw new Exception('ID de foto requerido');
            }

            // Obtener información de la foto antes de eliminar
            $sql = "SELECT nombre_archivo, url_imagen FROM fotos_galeria WHERE id = ? AND activa = TRUE";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$id]);
            $foto = $stmt->fetch();

            if (!$foto) {
                throw new Exception('Foto no encontrada');
            }

            // Marcar como inactiva en lugar de eliminar
            $sql = "UPDATE fotos_galeria SET activa = FALSE WHERE id = ?";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$id]);

            // Intentar eliminar el archivo físico
            $rutaArchivo = $foto['url_imagen'];
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }

            $this->responderExito([
                'mensaje' => 'Foto eliminada correctamente',
                'id' => $id
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Cambiar imagen de la quinceañera
     */
    public function cambiarQuinceañera() {
        try {
            if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió ninguna imagen válida');
            }

            $archivo = $_FILES['imagen'];

            // Validar tipo de archivo
            $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($archivo['type'], $tipos_permitidos)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP');
            }

            // Validar tamaño (máximo 5MB)
            if ($archivo['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 5MB');
            }

            // Generar nombre único para el archivo
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_archivo = 'quinceañera_' . date('Y-m-d_H-i-s') . '.' . $extension;
            $ruta_completa = $this->carpeta_imagenes . $nombre_archivo;

            // Mover archivo a la carpeta de imágenes
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                throw new Exception('Error al guardar la imagen en el servidor');
            }

            // Guardar la nueva ruta en la base de datos
            $sql = "INSERT INTO configuracion_sitio (clave, valor) VALUES ('imagen_quinceañera', ?) 
                    ON DUPLICATE KEY UPDATE valor = ?";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$ruta_completa, $ruta_completa]);

            $this->responderExito([
                'mensaje' => 'Imagen de quinceañera actualizada',
                'url_imagen' => $ruta_completa,
                'nombre_archivo' => $nombre_archivo
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Obtener estadísticas del admin
     */
    public function obtenerEstadisticas() {
        try {
            // Fotos por día (últimos 7 días)
            $sql = "SELECT DATE(fecha_subida) as fecha, COUNT(*) as cantidad 
                    FROM fotos_galeria 
                    WHERE activa = TRUE AND fecha_subida >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(fecha_subida) 
                    ORDER BY fecha DESC";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute();
            $fotosPorDia = $stmt->fetchAll();

            // Top invitados
            $sql = "SELECT nombre_invitado, COUNT(*) as cantidad 
                    FROM fotos_galeria 
                    WHERE activa = TRUE 
                    GROUP BY nombre_invitado 
                    ORDER BY cantidad DESC 
                    LIMIT 5";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute();
            $topInvitados = $stmt->fetchAll();

            $this->responderExito([
                'fotos_por_dia' => $fotosPorDia,
                'top_invitados' => $topInvitados
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Obtener códigos QR generados
     */
    public function obtenerQRGenerados() {
        try {
            $sql = "SELECT id, codigo_qr, url_generada, imagen_qr, fecha_generacion, usado, fecha_uso 
                    FROM codigos_qr 
                    ORDER BY fecha_generacion DESC";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute();
            $qrCodes = $stmt->fetchAll();

            // Convertir rutas a URLs completas
            foreach ($qrCodes as &$qr) {
                if ($qr['imagen_qr']) {
                    $qr['url_imagen_completa'] = $this->obtenerUrlCompleta($qr['imagen_qr']);
                }
                $qr['fecha_formateada'] = date('d/m/Y H:i', strtotime($qr['fecha_generacion']));
                if ($qr['fecha_uso']) {
                    $qr['fecha_uso_formateada'] = date('d/m/Y H:i', strtotime($qr['fecha_uso']));
                }
            }

            $this->responderExito([
                'qr_codes' => $qrCodes,
                'total' => count($qrCodes)
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Subir código QR manualmente
     */
    public function subirQR() {
        try {
            $url = $_POST['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL requerida');
            }

            // Verificar si ya existe un QR para esta URL
            $sql = "SELECT id FROM codigos_qr WHERE url_generada = ? AND usado = FALSE";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$url]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Ya existe un código QR activo para esta URL');
            }

            // Validar archivo subido
            if (!isset($_FILES['qr_imagen']) || $_FILES['qr_imagen']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir la imagen del QR');
            }

            $archivo = $_FILES['qr_imagen'];
            
            // Validar tipo de archivo
            $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($archivo['type'], $tiposPermitidos)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF');
            }

            // Validar tamaño (máximo 5MB)
            if ($archivo['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 5MB permitido');
            }

            // Generar nombre único para el archivo
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'qr_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
            $rutaCompleta = $this->carpeta_imagenes . $nombreArchivo;

            // Mover archivo a la carpeta de imágenes
            if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
                throw new Exception('Error al guardar la imagen del QR');
            }

            // Guardar en base de datos
            $codigoQR = 'QR_' . uniqid();
            $sql = "INSERT INTO codigos_qr (codigo_qr, url_generada, imagen_qr, fecha_generacion) VALUES (?, ?, ?, NOW())";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute([$codigoQR, $url, $rutaCompleta]);

            $this->responderExito([
                'mensaje' => 'Código QR subido exitosamente',
                'id' => $this->bd->lastInsertId(),
                'codigo_qr' => $codigoQR,
                'url' => $url,
                'imagen_qr' => $rutaCompleta,
                'url_imagen_completa' => $this->obtenerUrlCompleta($rutaCompleta)
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
    $api = new AdminAPI();
    $accion = $_GET['accion'] ?? '';

    switch ($accion) {
        case 'eliminar_foto':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $api->eliminarFoto();
            break;

        case 'cambiar_quinceañera':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $api->cambiarQuinceañera();
            break;

        case 'estadisticas':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            $api->obtenerEstadisticas();
            break;

        case 'obtener_qr':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            $api->obtenerQRGenerados();
            break;

        case 'subir_qr':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $api->subirQR();
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
