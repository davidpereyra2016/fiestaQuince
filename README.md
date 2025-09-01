# 🎉 Aplicación Web para Fiesta de Quince Años

Una aplicación web moderna y elegante para que los invitados de tu fiesta de quince años puedan subir y compartir fotos de la celebración.

## ✨ Características

- **Subida de fotos**: Los invitados pueden subir fotos desde sus dispositivos móviles o computadoras
- **Galería en tiempo real**: Visualización instantánea de todas las fotos subidas
- **Base de datos MySQL**: Almacenamiento seguro y confiable con PDO
- **Imágenes locales**: Las fotos se guardan en la carpeta `imagenes/` del servidor
- **Código QR inteligente**: Generación única de QR que desaparece después de usarse
- **Diseño responsivo**: Funciona perfectamente en móviles, tablets y computadoras
- **Interfaz en español**: Todo el sistema está completamente en español

## 📋 Requisitos

- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4** o superior
- **MySQL 5.7** o superior
- Navegador web moderno

## 🚀 Instalación

### Paso 1: Configurar XAMPP
1. Asegúrate de que XAMPP esté instalado y funcionando
2. Inicia **Apache** y **MySQL** desde el panel de control de XAMPP

### Paso 2: Crear la base de datos
1. Abre **phpMyAdmin** en tu navegador: `http://localhost/phpmyadmin`
2. Ejecuta el archivo `crear_base_datos.sql` que se encuentra en la carpeta del proyecto
3. Esto creará automáticamente:
   - Base de datos `fiesta_quince`
   - Tabla `fotos_galeria` (para las fotos)
   - Tabla `codigos_qr` (para controlar los códigos QR)

### Paso 3: Configurar permisos
1. Asegúrate de que la carpeta `imagenes/` tenga permisos de escritura
2. Si no existe, se creará automáticamente al subir la primera foto

### Paso 4: Acceder a la aplicación
1. Abre tu navegador y ve a: `http://localhost/fiestaQuince/`
2. ¡La aplicación estará lista para usar!

## 📱 Cómo usar

### Para los invitados:
1. **Subir fotos**: Selecciona una foto, opcionalmente ingresa tu nombre, y haz clic en "Subir Momento"
2. **Ver galería**: Haz clic en "Ver Galería de Momentos" para ver todas las fotos
3. **Ver fotos grandes**: Haz clic en cualquier foto para verla en pantalla completa

### Para la quinceañera:
1. **Generar QR**: Haz clic en "Generar mi QR" para crear el código QR
2. **Imprimir QR**: Haz clic derecho sobre el QR generado y selecciona "Guardar imagen"
3. **Control inteligente**: El botón de generar QR desaparece después de usarse para mantener la interfaz limpia

## 🗂️ Estructura de archivos

```
fiestaQuince/
├── index.html              # Página principal de la aplicación
├── db.php                  # Configuración de base de datos con PDO
├── api.php                 # API endpoints para todas las operaciones
├── crear_base_datos.sql    # Script para crear la base de datos
├── imagenes/               # Carpeta donde se guardan las fotos
└── README.md              # Este archivo de documentación
```

## 🔧 Configuración avanzada

### Cambiar configuración de base de datos
Edita el archivo `db.php` y modifica estas variables según tu configuración:

```php
private $servidor = 'localhost';    // Servidor MySQL
private $nombre_bd = 'fiesta_quince'; // Nombre de la base de datos
private $usuario = 'root';          // Usuario MySQL
private $contrasena = '';           // Contraseña MySQL
```

### Personalizar límites de archivos
En `api.php` puedes modificar:
- **Tipos de archivo permitidos**: Línea con `$tipos_permitidos`
- **Tamaño máximo**: Línea con `10 * 1024 * 1024` (actualmente 10MB)

## 📊 Base de datos

### Tabla `fotos_galeria`
- `id`: ID único de la foto
- `nombre_archivo`: Nombre del archivo guardado
- `url_imagen`: Ruta completa de la imagen
- `nombre_invitado`: Nombre del invitado (o "Anónimo")
- `fecha_subida`: Fecha y hora de subida
- `activa`: Si la foto está activa

### Tabla `codigos_qr`
- `id`: ID único del QR
- `codigo_qr`: Código QR generado
- `url_generada`: URL para la cual se generó
- `fecha_generacion`: Fecha de generación
- `usado`: Si ya fue marcado como usado
- `fecha_uso`: Fecha en que se marcó como usado

## 🎨 Personalización

### Cambiar colores y estilos
Todos los estilos están en el `<style>` del `index.html`. Puedes modificar:
- Colores principales (rosa/púrpura)
- Fuentes y tamaños
- Animaciones y efectos

### Cambiar foto de perfil
Reemplaza la URL en la línea 60 del `index.html`:
```html
<img src="TU_FOTO_AQUI" alt="Foto de la Quinceañera" ...>
```

## 🔒 Seguridad

- **Validación de archivos**: Solo se permiten imágenes (JPG, PNG, GIF, WEBP)
- **Límite de tamaño**: Máximo 10MB por archivo
- **Nombres únicos**: Cada archivo tiene un nombre único para evitar conflictos
- **PDO preparado**: Todas las consultas SQL usan declaraciones preparadas

## 🚨 Solución de problemas

### Error de conexión a base de datos
1. Verifica que MySQL esté ejecutándose en XAMPP
2. Confirma que la base de datos `fiesta_quince` existe
3. Revisa las credenciales en `db.php`

### Las fotos no se suben
1. Verifica permisos de la carpeta `imagenes/`
2. Revisa el tamaño del archivo (máximo 10MB)
3. Confirma que el tipo de archivo sea válido

### El QR no se genera
1. Verifica que la librería QRCode esté cargando correctamente
2. Revisa la consola del navegador para errores JavaScript

## 📞 Soporte

Si tienes problemas o necesitas ayuda adicional, revisa:
1. Los logs de error de Apache en XAMPP
2. La consola del navegador (F12)
3. Los logs de PHP en XAMPP

---

¡Disfruta tu fiesta de quince años y que todos los invitados compartan sus mejores momentos! 🎊✨
