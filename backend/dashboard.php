<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.html");
    exit();
}

// Obtener datos del usuario incluyendo el presupuesto
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Obtener el presupuesto del usuario
$stmt = $conn->prepare("SELECT budget FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$user_budget = $user_data['budget'] ?? 5000.00; // Valor por defecto si no existe

// Obtener gastos del mes actual
$current_month = date('Y-m');
$stmt = $conn->prepare("
    SELECT category, SUM(amount) as total 
    FROM expenses 
    WHERE user_id = ? AND date LIKE ? 
    GROUP BY category
");
$stmt->execute([$user_id, "$current_month%"]);
$expenses_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Calcular total de gastos del mes
$total_expenses = array_sum(array_column($expenses_by_category, 'total'));

// Calcular presupuesto restante
$remaining_budget = $user_budget - $total_expenses;
$budget_percentage = $user_budget > 0 ? min(($total_expenses / $user_budget) * 100, 100) : 0;

// Obtener todas las metas
$stmt = $conn->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar actualización de metas (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $goal_id = $_POST['goal_id'];
    $amount = floatval($_POST['amount']);
    
    $stmt = $conn->prepare("UPDATE goals SET current_amount = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$amount, $goal_id, $user_id]);
    
    header("Location: dashboard.php?updated=1");
    exit();
}

// Determinar categoría principal de gastos
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
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar optimizado -->
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

        <!-- Main Content mejorado -->
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

            <!-- Cards Resumen optimizadas -->
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
</body>
</html>
