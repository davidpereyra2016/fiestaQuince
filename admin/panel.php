<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

require_once '../db.php';

// Obtener estadísticas
try {
    $bd = obtenerBaseDatos()->obtenerConexion();
    
    // Contar fotos totales
    $stmt = $bd->query("SELECT COUNT(*) as total FROM fotos_galeria WHERE activa = TRUE");
    $totalFotos = $stmt->fetch()['total'];
    
    // Contar fotos de hoy
    $stmt = $bd->query("SELECT COUNT(*) as hoy FROM fotos_galeria WHERE DATE(fecha_subida) = CURDATE() AND activa = TRUE");
    $fotosHoy = $stmt->fetch()['hoy'];
    
    // Contar QR generados
    $stmt = $bd->query("SELECT COUNT(*) as qr_total FROM codigos_qr");
    $totalQR = $stmt->fetch()['qr_total'];
    
    // Obtener imagen de quinceañera
    $stmt = $bd->prepare("SELECT valor FROM configuracion_sitio WHERE clave = 'imagen_quinceañera'");
    $stmt->execute();
    $imagenQuinceañera = $stmt->fetch();
    $rutaImagenQuinceañera = $imagenQuinceañera ? $imagenQuinceañera['valor'] : null;
    
} catch (Exception $e) {
    $totalFotos = 0;
    $fotosHoy = 0;
    $totalQR = 0;
    $rutaImagenQuinceañera = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Mis 15 Años</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .admin-bg {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            min-height: 100vh;
        }
        .card {
            backdrop-filter: blur(10px);
            background: rgba(55, 65, 81, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
            border: 1px solid rgba(236, 72, 153, 0.3);
        }
        .btn-primary {
            background: linear-gradient(to right, #ec4899, #8b5cf6);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(236, 72, 153, 0.3);
        }
        .btn-danger {
            background: linear-gradient(to right, #ef4444, #dc2626);
        }
        .btn-danger:hover {
            background: linear-gradient(to right, #dc2626, #b91c1c);
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        @media (max-width: 640px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body class="admin-bg text-white">
    <!-- Header -->
    <header class="card m-4 p-4 rounded-xl">
        <div class="flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <div class="w-12 h-12 bg-pink-500 rounded-full flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-pink-400">Panel Admin</h1>
                    <p class="text-gray-300 text-sm">Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_user']); ?></p>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="../index.html" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm transition-colors">
                    Ver Fiesta
                </a>
                <a href="logout.php" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm transition-colors">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="mx-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card p-6 rounded-xl">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-pink-500/30 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalFotos; ?></p>
                        <p class="text-gray-300 text-sm">Fotos Totales</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card p-6 rounded-xl">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500/30 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white"><?php echo $fotosHoy; ?></p>
                        <p class="text-gray-300 text-sm">Fotos Hoy</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card p-6 rounded-xl">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-500/30 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalQR; ?></p>
                        <p class="text-gray-300 text-sm">QR Generados</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Imagen de Quinceañera -->
    <div class="mx-4 mb-6">
        <div class="card p-6 rounded-xl">
            <h2 class="text-xl font-bold text-pink-400 mb-4">Imagen de la Quinceañera</h2>
            <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                <div class="w-32 h-32 bg-gray-700 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center">
                    <?php if ($rutaImagenQuinceañera && file_exists($rutaImagenQuinceañera)): ?>
                        <img id="quinceañera-preview" src="../<?php echo htmlspecialchars($rutaImagenQuinceañera); ?>" alt="Quinceañera" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="text-center">
                            <svg class="w-12 h-12 text-gray-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-xs text-gray-500">Sin imagen</p>
                        </div>
                        <img id="quinceañera-preview" src="" alt="Quinceañera" class="w-full h-full object-cover hidden">
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <p class="text-gray-300 mb-4">Cambia la imagen principal que aparece en la página de la fiesta.</p>
                    <input type="file" id="quinceañera-input" accept="image/*" class="hidden">
                    <button onclick="document.getElementById('quinceañera-input').click()" class="btn-primary px-6 py-2 rounded-lg text-white font-medium">
                        Cambiar Imagen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Subir Código QR -->
    <div class="mx-4 mb-6">
        <div class="card p-6 rounded-xl">
            <h2 class="text-xl font-bold text-pink-400 mb-4">Subir Código QR</h2>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="mb-4">
                    <label for="url-qr" class="block text-white font-medium mb-2">URL del QR:</label>
                    <input type="text" id="url-qr" placeholder="https://ejemplo.com/galeria" 
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-pink-500">
                </div>
                <div class="mb-4">
                    <label for="qr-input" class="block text-white font-medium mb-2">Imagen del QR:</label>
                    <input type="file" id="qr-input" accept="image/*" 
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-pink-600 file:text-white hover:file:bg-pink-700">
                </div>
                <div class="flex space-x-3">
                    <button onclick="subirQR()" class="btn-primary px-6 py-2 rounded-lg text-white font-medium flex-1">
                        📤 Subir QR
                    </button>
                    <button onclick="limpiarFormularioQR()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-white font-medium">
                        🗑️ Limpiar
                    </button>
                </div>
                <div id="qr-upload-status" class="mt-4 hidden">
                    <!-- Status messages will appear here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Galería de Fotos -->
    <div class="mx-4 mb-6">
        <div class="card p-6 rounded-xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-pink-400">Galería de Fotos</h2>
                <button onclick="cargarGaleria()" class="btn-primary px-4 py-2 rounded-lg text-white text-sm">
                    Actualizar
                </button>
            </div>
            
            <div id="galeria-admin" class="gallery-grid">
                <!-- Las fotos se cargarán aquí dinámicamente -->
            </div>
            
            <div id="no-fotos" class="text-center py-12 hidden">
                <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-400">No hay fotos en la galería</p>
            </div>
        </div>
    </div>

    <!-- Códigos QR Generados -->
    <div class="mx-4 mb-6">
        <div class="card p-6 rounded-xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-pink-400">Códigos QR Generados</h2>
                <button onclick="cargarQRCodes()" class="btn-primary px-4 py-2 rounded-lg text-white text-sm">
                    Actualizar
                </button>
            </div>
            
            <div id="qr-container">
                <div class="text-center text-gray-500">
                    <p>Cargando códigos QR...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver imagen completa -->
    <div id="modal-imagen" class="fixed inset-0 bg-black bg-opacity-80 hidden flex items-center justify-center z-50 p-4">
        <div class="relative max-w-4xl max-h-full">
            <img id="modal-img" src="" class="max-w-full max-h-full rounded-lg" alt="Vista ampliada">
            <button onclick="cerrarModal()" class="absolute top-4 right-4 text-white text-3xl font-bold bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center">
                ×
            </button>
            <div class="absolute bottom-4 left-4 right-4 bg-black bg-opacity-70 rounded-lg p-4">
                <p id="modal-info" class="text-white text-sm"></p>
                <div class="mt-2 flex space-x-2">
                    <button onclick="eliminarFoto()" class="btn-danger px-4 py-2 rounded text-white text-sm">
                        Eliminar Foto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let fotoActual = null;

        // Cargar galería y QR codes al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarGaleria();
            cargarQRCodes();
        });

        // Función para cargar la galería
        async function cargarGaleria() {
            try {
                const respuesta = await fetch('../api.php?accion=obtener_galeria');
                const resultado = await respuesta.json();

                const galeriaDiv = document.getElementById('galeria-admin');
                const noFotosDiv = document.getElementById('no-fotos');

                if (resultado.exito && resultado.datos.fotos.length > 0) {
                    noFotosDiv.classList.add('hidden');
                    galeriaDiv.innerHTML = '';

                    resultado.datos.fotos.forEach(foto => {
                        const fotoDiv = document.createElement('div');
                        fotoDiv.className = 'relative group cursor-pointer';
                        fotoDiv.onclick = () => abrirModal(foto);

                        fotoDiv.innerHTML = `
                            <div class="aspect-square bg-gray-700 rounded-lg overflow-hidden">
                                <img src="${foto.url_completa}" alt="Foto de ${foto.nombre_invitado}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 rounded-lg flex items-end p-2">
                                <div class="text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <p class="font-semibold">${foto.nombre_invitado}</p>
                                    <p class="text-gray-300">${foto.fecha_formateada}</p>
                                </div>
                            </div>
                        `;

                        galeriaDiv.appendChild(fotoDiv);
                    });
                } else {
                    galeriaDiv.innerHTML = '';
                    noFotosDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error al cargar galería:', error);
                alert('Error al cargar la galería');
            }
        }

        // Función para abrir modal
        function abrirModal(foto) {
            fotoActual = foto;
            document.getElementById('modal-img').src = foto.url_completa;
            document.getElementById('modal-info').innerHTML = `
                <strong>${foto.nombre_invitado}</strong><br>
                Subida: ${foto.fecha_formateada}<br>
                ID: ${foto.id}
            `;
            document.getElementById('modal-imagen').classList.remove('hidden');
        }

        // Función para cerrar modal
        function cerrarModal() {
            document.getElementById('modal-imagen').classList.add('hidden');
            fotoActual = null;
        }

        // Función para eliminar foto
        async function eliminarFoto() {
            if (!fotoActual) return;

            if (!confirm(`¿Estás seguro de eliminar la foto de ${fotoActual.nombre_invitado}?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', fotoActual.id);

                const respuesta = await fetch('admin_api.php?accion=eliminar_foto', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await respuesta.json();

                if (resultado.exito) {
                    alert('Foto eliminada correctamente');
                    cerrarModal();
                    cargarGaleria();
                } else {
                    alert('Error: ' + resultado.error);
                }
            } catch (error) {
                console.error('Error al eliminar foto:', error);
                alert('Error al eliminar la foto');
            }
        }

        // Función para cargar códigos QR
        async function cargarQRCodes() {
            try {
                const respuesta = await fetch('admin_api.php?accion=obtener_qr');
                const resultado = await respuesta.json();

                const qrContainer = document.getElementById('qr-container');

                if (resultado.exito && resultado.datos.qr_codes.length > 0) {
                    qrContainer.innerHTML = '';

                    resultado.datos.qr_codes.forEach(qr => {
                        const qrDiv = document.createElement('div');
                        qrDiv.className = 'bg-gray-800 rounded-lg p-4 mb-4 border border-gray-700';

                        const estadoClass = qr.usado ? 'bg-red-500' : 'bg-green-500';
                        const estadoText = qr.usado ? 'Usado' : 'Activo';

                        qrDiv.innerHTML = `
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-4 sm:space-y-0">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="px-2 py-1 text-xs font-medium text-white rounded-full ${estadoClass}">
                                            ${estadoText}
                                        </span>
                                        <span class="text-gray-400 text-sm">
                                            ${qr.fecha_formateada}
                                        </span>
                                    </div>
                                    <p class="text-white font-medium mb-1">Código: ${qr.codigo_qr}</p>
                                    <p class="text-gray-300 text-sm break-all">URL: ${qr.url_generada}</p>
                                    ${qr.usado && qr.fecha_uso_formateada ? `<p class="text-gray-400 text-xs mt-1">Usado: ${qr.fecha_uso_formateada}</p>` : ''}
                                </div>
                                <div class="flex items-center space-x-3">
                                    ${qr.imagen_qr ? `
                                        <div class="w-16 h-16 bg-white rounded-lg p-1">
                                            <img src="${qr.url_imagen_completa}" alt="QR Code" class="w-full h-full object-contain">
                                        </div>
                                        <a href="${qr.url_imagen_completa}" download="qr_${qr.id}.png" 
                                           class="btn-primary px-4 py-2 rounded-lg text-white text-sm hover:bg-pink-600 transition-colors">
                                            Descargar
                                        </a>
                                    ` : '<span class="text-gray-500 text-sm">Sin imagen</span>'}
                                </div>
                            </div>
                        `;

                        qrContainer.appendChild(qrDiv);
                    });
                } else {
                    qrContainer.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h4"></path>
                            </svg>
                            <p class="text-gray-400">No hay códigos QR generados</p>
                        </div>
                    `;
                }

            } catch (error) {
                console.error('Error al cargar códigos QR:', error);
                document.getElementById('qr-container').innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-400">Error al cargar códigos QR</p>
                    </div>
                `;
            }
        }

        // Función para subir código QR
        async function subirQR() {
            const urlInput = document.getElementById('url-qr');
            const archivoInput = document.getElementById('qr-input');
            const statusDiv = document.getElementById('qr-upload-status');

            const url = urlInput.value.trim();
            const archivo = archivoInput.files[0];

            // Validaciones
            if (!url) {
                mostrarStatusQR('Por favor ingresa una URL válida', 'error');
                return;
            }

            if (!archivo) {
                mostrarStatusQR('Por favor selecciona una imagen QR', 'error');
                return;
            }

            // Validar tipo de archivo
            if (!archivo.type.startsWith('image/')) {
                mostrarStatusQR('Por favor selecciona un archivo de imagen válido', 'error');
                return;
            }

            try {
                mostrarStatusQR('Subiendo código QR...', 'loading');

                const formData = new FormData();
                formData.append('url', url);
                formData.append('qr_imagen', archivo);

                const respuesta = await fetch('admin_api.php?accion=subir_qr', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await respuesta.json();

                if (resultado.exito) {
                    mostrarStatusQR('¡Código QR subido exitosamente!', 'success');
                    limpiarFormularioQR();
                    cargarQRCodes(); // Recargar la lista de QR codes
                } else {
                    mostrarStatusQR('Error: ' + resultado.error, 'error');
                }

            } catch (error) {
                console.error('Error al subir QR:', error);
                mostrarStatusQR('Error al subir el código QR', 'error');
            }
        }

        // Función para mostrar status de upload de QR
        function mostrarStatusQR(mensaje, tipo) {
            const statusDiv = document.getElementById('qr-upload-status');
            statusDiv.classList.remove('hidden');

            let claseColor = '';
            let icono = '';

            switch (tipo) {
                case 'success':
                    claseColor = 'text-green-400 bg-green-500/20 border-green-500/30';
                    icono = '✅';
                    break;
                case 'error':
                    claseColor = 'text-red-400 bg-red-500/20 border-red-500/30';
                    icono = '❌';
                    break;
                case 'loading':
                    claseColor = 'text-blue-400 bg-blue-500/20 border-blue-500/30';
                    icono = '⏳';
                    break;
            }

            statusDiv.innerHTML = `
                <div class="p-3 rounded-lg border ${claseColor}">
                    <p class="text-sm font-medium">${icono} ${mensaje}</p>
                </div>
            `;

            // Auto-hide success/error messages after 3 seconds
            if (tipo !== 'loading') {
                setTimeout(() => {
                    statusDiv.classList.add('hidden');
                }, 3000);
            }
        }

        // Función para limpiar formulario de QR
        function limpiarFormularioQR() {
            document.getElementById('url-qr').value = '';
            document.getElementById('qr-input').value = '';
            document.getElementById('qr-upload-status').classList.add('hidden');
        }

        // Cambiar imagen de quinceañera
        document.getElementById('quinceañera-input').addEventListener('change', async function(e) {
            const archivo = e.target.files[0];
            if (!archivo) return;

            const formData = new FormData();
            formData.append('imagen', archivo);

            try {
                const respuesta = await fetch('admin_api.php?accion=cambiar_quinceañera', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await respuesta.json();

                if (resultado.exito) {
                    // Actualizar la imagen de vista previa
                    const preview = document.getElementById('quinceañera-preview');
                    const container = preview.parentElement;
                    
                    // Ocultar el placeholder si existe
                    const placeholder = container.querySelector('div');
                    if (placeholder && placeholder !== preview) {
                        placeholder.style.display = 'none';
                    }
                    
                    // Mostrar y actualizar la imagen
                    preview.src = '../' + resultado.datos.ruta_imagen;
                    preview.classList.remove('hidden');
                    preview.style.display = 'block';
                    
                    alert('Imagen actualizada correctamente');
                } else {
                    alert('Error: ' + resultado.error);
                }

            } catch (error) {
                console.error('Error al cambiar imagen:', error);
                alert('Error al cambiar la imagen');
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
