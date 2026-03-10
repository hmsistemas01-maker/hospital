<?php

require_once __DIR__ . '/../config/config.php';

// Obtener módulos del usuario para el menú lateral
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("
        SELECT m.nombre, m.ruta,
               CASE m.nombre
                   WHEN 'Administracion' THEN '👑'
                   WHEN 'Almacén' THEN '📦'
                   WHEN 'Citas' THEN '📅'
                   WHEN 'Doctor' THEN '🩺'
                   WHEN 'Farmacia' THEN '💊'
                   WHEN 'Registro Pacientes' THEN '📋'
                   ELSE '🔷'
               END as icono
        FROM usuario_modulos um
        JOIN modulos m ON um.modulo_id = m.id
        WHERE um.usuario_id = ?
        ORDER BY 
            CASE m.nombre
                WHEN 'Registro Pacientes' THEN 1
                WHEN 'Citas' THEN 2
                WHEN 'Doctor' THEN 3
                WHEN 'Farmacia' THEN 4
                WHEN 'Almacén' THEN 5
                WHEN 'Administracion' THEN 6
                ELSE 7
            END
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $menu_modulos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SISTEMA_NOMBRE ?> | Panel de Control</title>
    <!-- Bootstrap 5 CSS (CDN) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    <style>
        /* Estilos para el layout con sidebar */
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
        }
        
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background: var(--gray-100);
        }
        
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark) 0%, #0a2a4a 100%);
            color: white;
            transition: width 0.3s ease;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .main-header {
            background: white;
            padding: var(--spacing-md) var(--spacing-xl);
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--gray-700);
            font-size: 1.5rem;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-toggle:hover {
            background: var(--gray-200);
        }
        
        .sidebar-logo {
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: var(--spacing-lg);
        }
        
        .sidebar-logo h2 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        .sidebar-logo .logo-icon {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin: var(--spacing-xs) 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            white-space: nowrap;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--warning);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: var(--success);
        }
        
        .nav-icon {
            font-size: 1.5rem;
            min-width: 40px;
            text-align: center;
        }
        
        .nav-text {
            margin-left: var(--spacing-sm);
            opacity: 1;
            transition: opacity 0.2s ease;
        }
        
        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: var(--spacing-md) 0;
        }
        
        .sidebar.collapsed .nav-icon {
            min-width: auto;
        }
        
        .sidebar.collapsed .sidebar-logo h2 {
            display: none;
        }
        
        .user-info-sidebar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--spacing-lg);
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-info-sidebar .user-name {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: white;
            white-space: nowrap;
        }
        
        .user-info-sidebar .user-avatar {
            font-size: 2rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .sidebar-toggle-mobile {
                display: block !important;
            }
        }
        
        .sidebar-toggle-mobile {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-700);
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">🏥</div>
                <h2><?= SISTEMA_NOMBRE ?></h2>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                            <span class="nav-icon">📊</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item" style="margin-top: var(--spacing-lg);">
                        <div style="padding: 0 var(--spacing-lg); color: rgba(255,255,255,0.5); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                            <span class="nav-text">Módulos</span>
                        </div>
                    </li>
                    
                    <?php foreach ($menu_modulos as $modulo): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>modules/<?= $modulo['ruta'] ?>/index.php" 
                               class="nav-link <?= strpos($_SERVER['PHP_SELF'], $modulo['ruta']) !== false ? 'active' : '' ?>">
                                <span class="nav-icon"><?= $modulo['icono'] ?></span>
                                <span class="nav-text"><?= $modulo['nombre'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="user-info-sidebar">
                    <div class="user-name">
                        <span class="user-avatar">👤</span>
                        <span class="nav-text">
                            <strong><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></strong>
                            <br>
                            <small style="font-size: 0.7rem; opacity: 0.7;"><?= $_SESSION['rol'] ?? '' ?></small>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
        
        <main class="main-content" id="mainContent">
            <header class="main-header">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
                        ☰
                    </button>
                    <button class="sidebar-toggle-mobile" id="sidebarToggleMobile" title="Abrir menú">
                        ☰
                    </button>
                    <h2 style="margin: 0; font-size: 1.2rem;">
                        <?php
                        $pagina = basename($_SERVER['PHP_SELF']);
                        switch($pagina) {
                            case 'dashboard.php':
                                echo 'Panel Principal';
                                break;
                            case 'usuarios.php':
                                echo 'Administración de Usuarios';
                                break;
                            case 'pacientes.php':
                                echo 'Gestión de Pacientes';
                                break;
                            default:
                                echo ucfirst(str_replace('.php', '', $pagina));
                        }
                        ?>
                    </h2>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                        <span style="display: flex; align-items: center; gap: var(--spacing-xs);">
                            <span>📅</span>
                            <span id="fechaActual"><?= date('d/m/Y') ?></span>
                            <span>⏰</span>
                            <span id="horaActual"></span>
                        </span>
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-danger" onclick="return confirm('¿Cerrar sesión?')">
                            🚪 Salir
                        </a>
                    </div>
                <?php endif; ?>
            </header>
            
            <div style="flex: 1; padding: var(--spacing-xl);">