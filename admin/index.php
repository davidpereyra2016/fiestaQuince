<?php
session_start();

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: panel.php');
    exit();
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Credenciales simples (en producción usar hash y BD)
    if ($usuario === 'admin' && $password === 'quince2025') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $usuario;
        header('Location: panel.php');
        exit();
    } else {
        $error = 'Credenciales incorrectas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Mis 15 Años</title>
    
    <!-- Tailwind CSS para un diseño moderno y adaptable -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Estilos personalizados */
        body {
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            min-height: 100vh;
        }
        .login-form {
            backdrop-filter: blur(10px);
            background: rgba(55, 65, 81, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-field {
            background: rgba(31, 41, 55, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            background: rgba(31, 41, 55, 0.8);
            border-color: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }
        .btn-login {
            background: linear-gradient(to right, #ec4899, #8b5cf6);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(236, 72, 153, 0.3);
        }
    </style>
</head>
<body class="login-container text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 mx-auto mb-4 bg-pink-500 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-pink-400">Panel Admin</h1>
                <p class="text-gray-300 mt-2">Gestión de Fiesta de 15 Años</p>
            </div>

            <!-- Formulario de Login -->
            <div class="login-form rounded-2xl p-8 shadow-2xl">
                <?php if (isset($error)): ?>
                    <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-200 text-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-300 mb-2">
                            Usuario
                        </label>
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            required
                            class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                            placeholder="Ingresa tu usuario"
                            value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                            Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                            placeholder="Ingresa tu contraseña"
                        >
                    </div>

                    <button 
                        type="submit" 
                        class="btn-login w-full py-3 px-4 rounded-lg text-white font-bold text-lg shadow-lg"
                    >
                        Iniciar Sesión
                    </button>
                </form>

                <!-- Credenciales de prueba -->
                <div class="mt-6 p-4 bg-blue-500/20 border border-blue-500/50 rounded-lg">
                    <p class="text-blue-200 text-sm font-medium mb-2">Credenciales de prueba:</p>
                    <p class="text-blue-300 text-xs">Usuario: <span class="font-mono">admin</span></p>
                    <p class="text-blue-300 text-xs">Contraseña: <span class="font-mono">quince2025</span></p>
                </div>

                <!-- Link de regreso -->
                <div class="mt-6 text-center">
                    <a href="../index.html" class="text-gray-400 hover:text-pink-400 text-sm transition-colors">
                        ← Volver a la fiesta
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
