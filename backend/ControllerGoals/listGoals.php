<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$stmt = $conn->prepare("
    SELECT *, 
           (current_amount / target_amount * 100) as progress,
           DATEDIFF(deadline, CURDATE()) as days_remaining
    FROM goals 
    WHERE user_id = ? 
    ORDER BY 
        CASE WHEN current_amount >= target_amount THEN 1 ELSE 0 END,
        deadline ASC
");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Metas | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--dark);
            color: white;
            padding: 15px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.1rem;
            margin-left: 10px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            color: var(--light);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 25px;
            overflow-y: auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        /* Goals Container */
        .goals-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        /* Goal Filter */
        .goal-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            background: #f5f5f5;
            color: #555;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .filter-btn:hover {
            background: #e0e0e0;
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
        }
        
        /* Goal Cards */
        .goal-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .goal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .goal-card.completed {
            border-left: 4px solid var(--success);
        }
        
        .goal-card:not(.completed) {
            border-left: 4px solid var(--primary);
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .goal-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }
        
        .goal-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: var(--success);
        }
        
        .status-pending {
            background: #e3f2fd;
            color: var(--primary);
        }
        
        .goal-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .goal-deadline {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #777;
        }
        
        /* Progress Bar */
        .progress-container {
            margin-bottom: 15px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }
        
        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Goal Actions */
        .goal-actions {
            display: flex;
            gap: 10px;
        }
        
        .goal-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn {
            background: var(--primary);
            color: white;
        }
        
        .edit-btn:hover {
            background: #2980b9;
        }
        
        .delete-btn {
            background: var(--danger);
            color: white;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        /* No Goals State */
        .no-goals {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .no-goals i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .no-goals p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .add-goal-btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .add-goal-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* ===== MEDIA QUERIES RESPONSIVE ===== */
        /* Tabletas y dispositivos medianos (768px o menos) */
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr !important;
                grid-template-rows: auto 1fr;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 220px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
                padding-top: 50px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .menu-toggle {
                display: block !important;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--dark);
                color: white;
                border: none;
                border-radius: 4px;
                padding: 8px 12px;
                cursor: pointer;
            }
            
            .main-content {
                padding: 70px 15px 25px 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .goal-filter {
                justify-content: center;
            }
            
            .goal-details {
                flex-direction: column;
            }
            
            .goal-actions {
                flex-direction: column;
            }
            
            .goal-btn {
                justify-content: center;
            }
        }

        /* Dispositivos móviles pequeños (480px o menos) */
        @media (max-width: 480px) {
            .main-content {
                padding: 65px 10px 20px 10px;
            }
            
            .goals-container {
                padding: 15px;
            }
            
            .goal-card {
                padding: 15px;
            }
            
            .goal-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .filter-btn {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            .logout-btn {
                width: 100%;
                text-align: center;
                padding: 10px;
            }
        }

        /* Elementos adicionales necesarios para el menú móvil */
        .menu-toggle {
            display: none;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        /* Mejoras generales para móviles */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }
            
            .sidebar-header {
                margin-bottom: 20px;
            }
            
            .logout-btn {
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Botón de menú móvil y overlay -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i> Menú
    </button>
    
    <div class="overlay" id="overlay"></div>

    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-wallet"></i>
                <h2>FinBalance</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../ControllerExpenses/createExpense.php"><i class="fas fa-plus-circle"></i> Agregar Gasto</a></li>
                <li><a href="createGoals.php"><i class="fas fa-bullseye"></i> Nueva Meta</a></li>
                <li><a href="../ControllerExpenses/listExpenses.php"><i class="fas fa-list-ul"></i> Lista de Gastos</a></li>
                <li><a href="listGoals.php" class="active"><i class="fas fa-tasks"></i> Mis Metas</a></li>
                <li><a href="../profile.php"><i class="fas fa-user-cog"></i> Configuración</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user_name) ?></div>
                        <div style="font-size: 0.75rem; color: #777;"><?= date('d M Y') ?></div>
                    </div>
                </div>
                <a href="../ControllerAuth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>

            <div class="goals-container">
                <h3 class="section-title">
                    <i class="fas fa-bullseye"></i>
                    Mis Metas Financieras
                </h3>
                
                <div class="goal-filter">
                    <button class="filter-btn active" data-filter="all">Todas</button>
                    <button class="filter-btn" data-filter="completed">Completadas</button>
                    <button class="filter-btn" data-filter="pending">Pendientes</button>
                    <button class="filter-btn" data-filter="expiring">Por vencer</button>
                </div>
                
                <?php if (!empty($goals)): ?>
                    <?php foreach ($goals as $goal): 
                        $is_completed = $goal['current_amount'] >= $goal['target_amount'];
                        $progress = $goal['progress'];
                        $days_remaining = $goal['days_remaining'];
                    ?>
                        <div class="goal-card <?= $is_completed ? 'completed' : '' ?>" 
                             data-status="<?= $is_completed ? 'completed' : 'pending' ?>"
                             data-days="<?= $days_remaining ?>">
                            <div class="goal-header">
                                <div class="goal-title"><?= htmlspecialchars($goal['title']) ?></div>
                                <div class="goal-status <?= $is_completed ? 'status-completed' : 'status-pending' ?>">
                                    <?= $is_completed ? 'Completada' : 'En progreso' ?>
                                </div>
                            </div>
                            
                            <div class="goal-details">
                                <div>
                                    <span>$<?= number_format($goal['current_amount'], 2) ?> de $<?= number_format($goal['target_amount'], 2) ?></span>
                                </div>
                                <div class="goal-deadline">
                                    <i class="fas fa-calendar-day"></i>
                                    <span>Fecha límite: <?= date('d/m/Y', strtotime($goal['deadline'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span>Progreso: <?= round($progress) ?>%</span>
                                    <span><?= $days_remaining > 0 ? "$days_remaining días restantes" : "Tiempo cumplido" ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?= min($progress, 100) ?>%; background: <?= $is_completed ? 'var(--success)' : 'var(--primary)' ?>;"></div>
                                </div>
                            </div>
                            
                            <div class="goal-actions">
                                <button class="goal-btn edit-btn" onclick="editGoal(<?= $goal['id'] ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="goal-btn delete-btn" onclick="confirmDelete(<?= $goal['id'] ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-goals">
                        <i class="fas fa-bullseye"></i>
                        <p>No tienes metas financieras registradas</p>
                        <a href="createGoals.php" class="add-goal-btn">
                            <i class="fas fa-plus"></i> Crear mi primera meta
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para el funcionamiento del menú móvil
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('overlay');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }
        });
        
        // Funciones originales de filtrado y acciones
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.goal-card').forEach(card => {
                    const status = card.dataset.status;
                    const days = parseInt(card.dataset.days);
                    
                    let show = false;
                    
                    switch(filter) {
                        case 'all':
                            show = true;
                            break;
                        case 'completed':
                            show = status === 'completed';
                            break;
                        case 'pending':
                            show = status === 'pending';
                            break;
                        case 'expiring':
                            show = status === 'pending' && days <= 30 && days >= 0;
                            break;
                    }
                    
                    card.style.display = show ? 'block' : 'none';
                });
            });
        });
        
        function editGoal(id) {
            window.location.href = `updateGoal.php?id=${id}`;
        }
        
        function confirmDelete(id) {
            if(confirm('¿Estás seguro de eliminar esta meta? Todos los progresos se perderán.')) {
                window.location.href = `deleteGoal.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
