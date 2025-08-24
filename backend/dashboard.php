<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$stmt = $conn->prepare("SELECT budget FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$user_budget = $user_data['budget'] ?? 5000.00;

$current_month = date('Y-m');
$stmt = $conn->prepare("
    SELECT category, SUM(amount) as total 
    FROM expenses 
    WHERE user_id = ? AND date LIKE ? 
    GROUP BY category
");
$stmt->execute([$user_id, "$current_month%"]);
$expenses_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$total_expenses = array_sum(array_column($expenses_by_category, 'total'));

$remaining_budget = $user_budget - $total_expenses;
$budget_percentage = $user_budget > 0 ? min(($total_expenses / $user_budget) * 100, 100) : 0;

$stmt = $conn->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $goal_id = $_POST['goal_id'];
    $amount = floatval($_POST['amount']);
    
    $stmt = $conn->prepare("UPDATE goals SET current_amount = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$amount, $goal_id, $user_id]);
    
    header("Location: dashboard.php?updated=1");
    exit();
}

$main_category = null;
if (!empty($expenses_by_category)) {
    $main_category = array_reduce($expenses_by_category, 
        fn($a, $b) => ($a['total'] ?? 0) > ($b['total'] ?? 0) ? $a : $b
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../frontend/StylesCSS/DashboardStyle.css">
    <style>
        :root {
            --primary: #3498db;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --success: #2ecc71;
            --danger: #e74c3c;
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
        
        /* Sidebar mejorado */
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
        
        /* Main Content optimizado */
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
        
        /* Cards más compactas */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .card-title {
            font-size: 1rem;
            color: var(--dark);
        }
        
        .card-value {
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--primary);
            margin: 8px 0;
        }
        
        /* Progress Bar */
        .progress-container {
            margin-top: 12px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
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
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Secciones mejoradas */
        .section-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        /* Gráfico optimizado */
        .chart-container {
            position: relative;
            height: 300px;
            max-width: 700px;
            margin: 15px auto;
        }

        #expensesChart {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        /* Metas mejoradas */
        .goal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .goal-info {
            flex: 1;
        }
        
        .goal-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }
        
        .goal-deadline {
            font-size: 0.75rem;
            color: #7f8c8d;
        }
        
        .goal-form {
            display: flex;
            align-items: center;
        }
        
        .goal-form input {
            width: 90px;
            padding: 7px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        .update-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .update-btn:hover {
            background: #27ae60;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .empty-state p {
            margin-bottom: 15px;
        }

        .card-value[style*="var(--success)"] {
            color: var(--success) !important;
        }

        .card-value[style*="var(--danger)"] {
            color: var(--danger) !important;
        }

        .fa-piggy-bank {
            color: #ff6b6b;
        }

        /* ===== MEDIA QUERIES PARA DISPOSITIVOS MÓVILES ===== */

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
            
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .chart-container {
                height: 250px;
            }
        }

        /* Dispositivos móviles pequeños (480px o menos) */
        @media (max-width: 480px) {
            .main-content {
                padding: 65px 10px 20px 10px;
            }
            
            .card {
                padding: 12px;
            }
            
            .card-value {
                font-size: 1.4rem;
            }
            
            .section-container {
                padding: 15px;
            }
            
            .goal-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .goal-form {
                width: 100%;
                justify-content: space-between;
            }
            
            .goal-form input {
                flex: 1;
            }
            
            .chart-container {
                height: 200px;
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
            
            /* Asegurar que las imágenes y gráficos sean responsivos */
            canvas {
                max-width: 100% !important;
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="ControllerExpenses/createExpense.php"><i class="fas fa-plus-circle"></i> Agregar Gasto</a></li>
                <li><a href="ControllerGoals/createGoals.php"><i class="fas fa-bullseye"></i> Nueva Meta</a></li>
                <li><a href="ControllerExpenses/listExpenses.php"><i class="fas fa-list-ul"></i> Lista de Gastos</a></li>
                <li><a href="ControllerGoals/listGoals.php"><i class="fas fa-tasks"></i> Mis Metas</a></li>
                <li><a href="profile.php"><i class="fas fa-user-cog"></i> Configuración</a></li>
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
                <a href="ControllerAuth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Gastos este mes</span>
                        <i class="fas fa-wallet card-icon"></i>
                    </div>
                    <div class="card-value">
                        $<?= number_format($total_expenses, 2) ?>
                    </div>
                    <div class="progress-container">
                        <div class="progress-info">
                            <span>Presupuesto: $<?= number_format($user_budget, 2) ?></span>
                            <span><?= round($budget_percentage) ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?= $budget_percentage ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Presupuesto restante</span>
                        <i class="fas fa-piggy-bank card-icon"></i>
                    </div>
                    <div class="card-value" style="color: <?= $remaining_budget >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                        $<?= number_format($remaining_budget, 2) ?>
                    </div>
                    <div class="progress-info">
                        <span>Días restantes: <?= date('t') - date('j') ?></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Metas activas</span>
                        <i class="fas fa-bullseye card-icon"></i>
                    </div>
                    <div class="card-value"><?= count($goals) ?></div>
                    <div class="progress-info">
                        <span>Completadas: <?= count(array_filter($goals, fn($g) => $g['current_amount'] >= $g['target_amount'])) ?></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Categoría principal</span>
                        <i class="fas fa-tag card-icon"></i>
                    </div>
                    <?php if ($main_category): ?>
                        <div class="card-value"><?= htmlspecialchars($main_category['category']) ?></div>
                        <div class="progress-info">
                            <span>Gastado: $<?= number_format($main_category['total'], 2) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="card-value">N/A</div>
                        <div class="progress-info">
                            <span>No hay gastos registrados</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-container">
                <h3 class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    Distribución de Gastos
                </h3>
                <div class="chart-container">
                    <?php if (!empty($expenses_by_category)): ?>
                        <canvas id="expensesChart"></canvas>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-pie"></i>
                            <p>No hay datos de gastos para mostrar</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-container">
                <h3 class="section-title">
                    <i class="fas fa-bullseye"></i>
                    Tus Metas Financieras
                </h3>
                
                <?php if (!empty($goals)): ?>
                    <?php foreach ($goals as $goal): 
                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                        $is_complete = $goal['current_amount'] >= $goal['target_amount'];
                    ?>
                        <div class="goal-item">
                            <div class="goal-info">
                                <div class="goal-title">
                                    <?= htmlspecialchars($goal['title']) ?>
                                    <?php if ($is_complete): ?>
                                        <span style="color: var(--success); font-size: 0.75rem; margin-left: 6px;">
                                            <i class="fas fa-check-circle"></i> Completada
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="goal-deadline">
                                    <i class="fas fa-calendar-alt"></i>
                                    Meta: $<?= number_format($goal['target_amount'], 2) ?> • 
                                    Fecha límite: <?= date('d/m/Y', strtotime($goal['deadline'])) ?>
                                </div>
                                <div class="progress-container" style="margin-top: 8px;">
                                    <div class="progress-info">
                                        <span>$<?= number_format($goal['current_amount'], 2) ?> de $<?= number_format($goal['target_amount'], 2) ?></span>
                                        <span><?= round($progress) ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?= min($progress, 100) ?>%; background: <?= $is_complete ? 'var(--success)' : 'var(--primary)' ?>;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!$is_complete): ?>
                                <form method="POST" class="goal-form">
                                    <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                                    <input type="number" name="amount" step="0.01" min="0" max="<?= $goal['target_amount'] ?>" 
                                           value="<?= $goal['current_amount'] ?>" required>
                                    <button type="submit" name="update_goal" class="update-btn">
                                        <i class="fas fa-sync-alt"></i> Actualizar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bullseye"></i>
                        <p>No tienes metas financieras registradas</p>
                        <a href="ControllerGoals/createGoals.php" style="display: inline-block; margin-top: 12px; background: var(--primary); color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">
                            <i class="fas fa-plus"></i> Crear mi primera meta
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!empty($expenses_by_category)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('expensesChart').getContext('2d');
        window.expensesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($expenses_by_category, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($expenses_by_category, 'total')) ?>,
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
                        '#1abc9c', '#d35400', '#34495e', '#7f8c8d', '#27ae60'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                },
                cutout: '65%'
            }
        });
    });
    </script>
    <?php endif; ?>

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
    </script>
</body>
</html>
