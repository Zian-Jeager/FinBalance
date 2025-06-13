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
    <link rel="stylesheet" href="/frontend/StylesCSS/GoalsStyle.css">
</head>
<body>
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
