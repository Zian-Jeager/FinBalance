<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Obtener el mes seleccionado o usar el actual
$selected_month = $_GET['month'] ?? date('Y-m');
$prev_month = date('Y-m', strtotime($selected_month . ' -1 month'));
$next_month = date('Y-m', strtotime($selected_month . ' +1 month'));

// Obtener gastos del mes seleccionado
$stmt = $conn->prepare("
    SELECT id, category, amount, description, DATE_FORMAT(date, '%d/%m/%Y') as formatted_date, date 
    FROM expenses 
    WHERE user_id = ? AND date LIKE ? 
    ORDER BY date DESC
");
$stmt->execute([$user_id, "$selected_month%"]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total del mes
$total_month = array_sum(array_column($expenses, 'amount'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Gastos | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/frontend/StylesCSS/ExpensesStyle.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-wallet"></i>
                <h2>FinBalance</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../ControllerExpenses/createExpense.php"><i class="fas fa-plus-circle"></i> Agregar Gasto</a></li>
                <li><a href="../ControllerGoals/createGoals.php"><i class="fas fa-bullseye"></i> Nueva Meta</a></li>
                <li><a href="../ControllerExpenses/listExpenses.php"><i class="fas fa-list-ul"></i> Lista de Gastos</a></li>
                <li><a href="../ControllerGoals/listGoals.php"><i class="fas fa-tasks"></i> Mis Metas</a></li>
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

            <div class="month-selector">
                <div>
                    <span class="month-title">Gastos de <?= date('F Y', strtotime($selected_month)) ?></span>
                </div>
                <div class="month-nav">
                    <a href="listExpenses.php?month=<?= $prev_month ?>" class="month-nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <input type="text" class="calendar-input" id="monthPicker" placeholder="Seleccionar mes" readonly>
                    <a href="listExpenses.php?month=<?= $next_month ?>" class="month-nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <?php if (!empty($expenses)): ?>
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Monto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?= htmlspecialchars($expense['formatted_date']) ?></td>
                                <td><span class="expense-category"><?= htmlspecialchars($expense['category']) ?></span></td>
                                <td><?= htmlspecialchars($expense['description']) ?></td>
                                <td class="expense-amount">$<?= number_format($expense['amount'], 2) ?></td>
                                <td class="action-btns">
                                    <button class="action-btn edit-btn" onclick="editExpense(<?= $expense['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?= $expense['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3">Total del mes</td>
                            <td class="expense-amount">$<?= number_format($total_month, 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-expenses">
                    <i class="fas fa-wallet"></i>
                    <p>No hay gastos registrados para este mes</p>
                    <a href="createExpense.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">
                        <i class="fas fa-plus"></i> Agregar gasto
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        // Configurar el selector de mes
        flatpickr("#monthPicker", {
            locale: "es",
            dateFormat: "Y-m",
            defaultDate: "<?= $selected_month ?>",
            static: true,
            plugins: [new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })],
            onChange: function(selectedDates, dateStr) {
                if(dateStr) {
                    window.location.href = `listExpenses.php?month=${dateStr}`;
                }
            }
        });

        function editExpense(id) {
            window.location.href = `updateExpense.php?id=${id}`;
        }

        function confirmDelete(id) {
            if(confirm('¿Estás seguro de eliminar este gasto?')) {
                window.location.href = `deleteExpense.php?id=${id}`;
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/monthSelect.js"></script>
</body>
</html>
