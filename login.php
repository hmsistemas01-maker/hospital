<?php
require_once 'config/config.php';
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['user_id'] = $user['id'];

        header("Location: index.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SISTEMA_NOMBRE ?> | Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        /* Estilos específicos para el login */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: var(--spacing-md);
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .login-header .logo {
            font-size: 4rem;
            display: inline-block;
            background: white;
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--spacing-md);
        }
        
        .login-header h1 {
            color: white;
            border-bottom: none;
            font-size: var(--font-size-xxl);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .login-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
        }
        
        .login-card h2 {
            color: var(--primary);
            text-align: center;
            margin-bottom: var(--spacing-lg);
            font-weight: 600;
        }
        
        .input-group {
            position: relative;
            margin-bottom: var(--spacing-lg);
        }
        
        .input-group i {
            position: absolute;
            left: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.2rem;
        }
        
        .input-group input {
            width: 100%;
            padding: var(--spacing-md) var(--spacing-md) var(--spacing-md) 45px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: var(--font-size-md);
            transition: all var(--transition-fast);
        }
        
        .input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 110, 156, 0.1);
            outline: none;
        }
        
        .input-group input::placeholder {
            color: var(--gray-400);
        }
        
        .login-btn {
            width: 100%;
            padding: var(--spacing-md);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--spacing-md);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert {
            margin-bottom: var(--spacing-lg);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
        }
        
        .alert-danger {
            background-color: var(--danger-light);
            color: var(--danger-dark);
            border-left: 4px solid var(--danger);
        }
        
        .login-footer {
            text-align: center;
            margin-top: var(--spacing-lg);
            color: var(--gray-600);
            font-size: var(--font-size-sm);
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Placeholder para iconos (usando emojis como fallback) */
        .icon-user::before {
            content: "👤";
        }
        
        .icon-lock::before {
            content: "🔒";
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🏥</div>
            <h1><?= SISTEMA_NOMBRE ?></h1>
        </div>
        
        <div class="login-card">
            <h2>Iniciar Sesión</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="input-group">
                    <i>👤</i>
                    <input 
                        type="text" 
                        name="usuario" 
                        placeholder="Usuario" 
                        required 
                        autofocus
                        autocomplete="username"
                    >
                </div>
                
                <div class="input-group">
                    <i>🔒</i>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Contraseña" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="login-btn">
                    Entrar al Sistema
                </button>
                
                <div class="login-footer">
                    <p>¿Olvidaste tu contraseña? Contacta al administrador</p>
                    <p style="margin-top: var(--spacing-sm);">
                        <small>Usuario demo: admin / admin123</small>
                    </p>
                </div>
            </form>
        </div>
        
        <div class="login-footer">
            <p>© <?= date('Y') ?> <?= SISTEMA_NOMBRE ?> - Sistema de Gestión Hospitalaria</p>
        </div>
    </div>
    
    <script>
        // Animación adicional al enviar el formulario
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('.login-btn');
            btn.innerHTML = '⏳ Ingresando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
