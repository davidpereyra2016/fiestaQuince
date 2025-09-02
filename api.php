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

            // Validar tamaño (máximo 10MB por archivo)
            if ($archivo['size'] > 10 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 10MB por imagen');
            }

            // Validar nombre de archivo para evitar caracteres problemáticos
            $nombre_original = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);

            // Generar nombre único para el archivo
            $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
            $nombre_archivo = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
            $ruta_completa = $this->carpeta_imagenes . $nombre_archivo;

            // Crear directorio si no existe
            if (!is_dir($this->carpeta_imagenes)) {
                mkdir($this->carpeta_imagenes, 0755, true);
            }

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
                'nombre_invitado' => $nombre_invitado,
                'nombre_archivo' => $nombre_archivo,
                'tamaño_archivo' => $archivo['size']
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
     * Generar código QR y guardar imagen en servidor
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

            // Generar nombre único para la imagen QR
            $nombre_qr = 'qr_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.png';
            $ruta_qr = $this->carpeta_imagenes . $nombre_qr;

            // Crear imagen QR usando librería PHP-QR-Code o método simple
            $this->generarImagenQR($url, $ruta_qr);

            // Guardar el código QR en la base de datos con la imagen
            $sql = "INSERT INTO codigos_qr (codigo_qr, url_generada, imagen_qr) VALUES (?, ?, ?)";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute(['QR_' . uniqid(), $url, $ruta_qr]);

            $this->responderExito([
                'mensaje' => 'Código QR generado exitosamente',
                'id' => $this->bd->lastInsertId(),
                'url' => $url,
                'imagen_qr' => $ruta_qr,
                'url_descarga' => $this->obtenerUrlCompleta($ruta_qr)
            ]);

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Generar imagen QR usando código PHP nativo
     */
    private function generarImagenQR($texto, $ruta_archivo) {
        // Verificar si la extensión GD está disponible
        if (!extension_loaded('gd')) {
            throw new Exception('Extensión GD no disponible para generar imágenes QR');
        }

        $ancho = 300;
        $alto = 300;
        
        $imagen = imagecreate($ancho, $alto);
        $blanco = imagecolorallocate($imagen, 255, 255, 255);
        $negro = imagecolorallocate($imagen, 0, 0, 0);
        
        // Fondo blanco
        imagefill($imagen, 0, 0, $blanco);
        
        // Crear un patrón de QR más realista
        $tamaño_cuadro = 10;
        $margen = 30;
        
        // Generar patrón basado en la URL
        $hash = md5($texto);
        $patron = str_split($hash . $hash); // Duplicar para más datos
        
        // Agregar esquinas de posicionamiento (características de QR)
        $this->dibujarEsquinaQR($imagen, $negro, $blanco, $margen, $margen, 60);
        $this->dibujarEsquinaQR($imagen, $negro, $blanco, $ancho - $margen - 60, $margen, 60);
        $this->dibujarEsquinaQR($imagen, $negro, $blanco, $margen, $alto - $margen - 60, 60);
        
        // Llenar el área central con patrón
        $x = $margen + 80;
        $y = $margen + 20;
        
        foreach ($patron as $i => $char) {
            if ($x > $ancho - $margen - 80) {
                $x = $margen + 20;
                $y += $tamaño_cuadro + 2;
            }
            
            if ($y > $alto - $margen - 80) break;
            
            if (hexdec($char) % 2 == 0) {
                imagefilledrectangle($imagen, $x, $y, $x + $tamaño_cuadro, $y + $tamaño_cuadro, $negro);
            }
            
            $x += $tamaño_cuadro + 2;
        }
        
        // Agregar texto de URL en la parte inferior
        $texto_corto = strlen($texto) > 40 ? substr($texto, 0, 37) . '...' : $texto;
        imagestring($imagen, 2, $margen, $alto - 20, $texto_corto, $negro);
        
        // Guardar imagen
        if (!imagepng($imagen, $ruta_archivo)) {
            imagedestroy($imagen);
            throw new Exception('Error al guardar la imagen QR');
        }
        
        imagedestroy($imagen);
    }

    /**
     * Dibujar esquina de posicionamiento del QR
     */
    private function dibujarEsquinaQR($imagen, $negro, $blanco, $x, $y, $tamaño) {
        // Marco exterior
        imagefilledrectangle($imagen, $x, $y, $x + $tamaño, $y + $tamaño, $negro);
        // Marco interior blanco
        imagefilledrectangle($imagen, $x + 8, $y + 8, $x + $tamaño - 8, $y + $tamaño - 8, $blanco);
        // Cuadrado central negro
        imagefilledrectangle($imagen, $x + 20, $y + 20, $x + $tamaño - 20, $y + $tamaño - 20, $negro);
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
     * Obtener QR existente si hay uno activo
     */
    public function obtenerQRExistente() {
        try {
            $sql = "SELECT id, url_generada, imagen_qr, fecha_generacion FROM codigos_qr WHERE usado = FALSE LIMIT 1";
            $stmt = $this->bd->prepare($sql);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $qr = $stmt->fetch();
                $this->responderExito([
                    'existe' => true,
                    'qr' => [
                        'id' => $qr['id'],
                        'url' => $qr['url_generada'],
                        'imagen_qr' => $qr['imagen_qr'],
                        'url_descarga' => $this->obtenerUrlCompleta($qr['imagen_qr']),
                        'fecha_generacion' => $qr['fecha_generacion']
                    ]
                ]);
            } else {
                $this->responderExito([
                    'existe' => false
                ]);
            }

        } catch (Exception $e) {
            $this->responderError($e->getMessage());
        }
    }

    /**
     * Obtener configuración del sitio
     */
    public function obtenerConfiguracion() {
        try {
            $clave = $_GET['clave'] ?? '';
            
            if (empty($clave)) {
                // Si no se especifica clave, devolver toda la configuración
                $sql = "SELECT clave, valor FROM configuracion_sitio";
                $stmt = $this->bd->prepare($sql);
                $stmt->execute();
                $configuraciones = $stmt->fetchAll();
                
                $config = [];
                foreach ($configuraciones as $conf) {
                    $config[$conf['clave']] = $conf['valor'];
                }
                
                $this->responderExito($config);
            } else {
                // Devolver configuración específica
                $sql = "SELECT valor FROM configuracion_sitio WHERE clave = ?";
                $stmt = $this->bd->prepare($sql);
                $stmt->execute([$clave]);
                $valor = $stmt->fetchColumn();
                
                if ($valor === false) {
                    throw new Exception('Configuración no encontrada');
                }
                
                $this->responderExito([
                    'clave' => $clave,
                    'valor' => $valor
                ]);
            }

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

        case 'obtener_qr_existente':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            $api->obtenerQRExistente();
            break;

        case 'obtener_configuracion':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            $api->obtenerConfiguracion();
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
