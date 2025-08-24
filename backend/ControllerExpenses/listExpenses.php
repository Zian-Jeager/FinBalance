<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$selected_month = $_GET['month'] ?? date('Y-m');
$prev_month = date('Y-m', strtotime($selected_month . ' -1 month'));
$next_month = date('Y-m', strtotime($selected_month . ' +1 month'));

$stmt = $conn->prepare("
    SELECT id, category, amount, description, DATE_FORMAT(date, '%d/%m/%Y') as formatted_date, date 
    FROM expenses 
    WHERE user_id = ? AND date LIKE ? 
    ORDER BY date DESC
");
$stmt->execute([$user_id, "$selected_month%"]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        
        /* Month Selector */
        .month-selector {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .month-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .month-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .month-nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .month-nav-btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }
        
        .calendar-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            min-width: 140px;
            text-align: center;
        }
        
        /* Expenses Table */
        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .expenses-table th {
            background: var(--dark);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .expenses-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .expenses-table tr:last-child td {
            border-bottom: none;
        }
        
        .expenses-table tr:hover {
            background: #f9f9f9;
        }
        
        .expense-category {
            padding: 4px 8px;
            background: #e3f2fd;
            color: var(--primary);
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .expense-amount {
            font-weight: 600;
            color: var(--dark);
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
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
        
        .total-row {
            background: #f8f9fa !important;
            font-weight: 600;
        }
        
        .total-row td {
            border-top: 2px solid #ddd;
        }
        
        /* No Expenses State */
        .no-expenses {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            color: #7f8c8d;
        }
        
        .no-expenses i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .no-expenses p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
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
            
            .month-selector {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .expenses-table {
                display: block;
                overflow-x: auto;
            }
            
            .expenses-table th,
            .expenses-table td {
                padding: 10px;
                font-size: 0.9rem;
            }
        }

        /* Dispositivos móviles pequeños (480px o menos) */
        @media (max-width: 480px) {
            .main-content {
                padding: 65px 10px 20px 10px;
            }
            
            .month-selector {
                padding: 12px;
            }
            
            .month-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .expenses-table {
                font-size: 0.8rem;
            }
            
            .expenses-table th,
            .expenses-table td {
                padding: 8px 5px;
            }
            
            .expense-category {
                font-size: 0.7rem;
                padding: 2px 6px;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-btn {
                width: 28px;
                height: 28px;
                font-size: 0.7rem;
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

        /* Vista de tarjetas para móviles */
        @media (max-width: 640px) {
            .expenses-table {
                display: none;
            }
            
            .mobile-expenses {
                display: block;
            }
            
            .expense-card {
                background: white;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 15px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .expense-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 10px;
            }
            
            .expense-date {
                font-size: 0.9rem;
                color: #777;
            }
            
            .expense-category {
                margin-bottom: 8px;
            }
            
            .expense-description {
                margin-bottom: 10px;
                line-height: 1.4;
            }
            
            .expense-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px solid #eee;
            }
        }

        @media (min-width: 641px) {
            .mobile-expenses {
                display: none;
            }
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
                <!-- Vista de tabla para escritorio -->
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

                <!-- Vista de tarjetas para móviles -->
                <div class="mobile-expenses">
                    <?php foreach ($expenses as $expense): ?>
                        <div class="expense-card">
                            <div class="expense-header">
                                <div class="expense-date"><?= htmlspecialchars($expense['formatted_date']) ?></div>
                                <span class="expense-category"><?= htmlspecialchars($expense['category']) ?></span>
                            </div>
                            <div class="expense-description"><?= htmlspecialchars($expense['description']) ?></div>
                            <div class="expense-footer">
                                <div class="expense-amount">$<?= number_format($expense['amount'], 2) ?></div>
                                <div class="action-btns">
                                    <button class="action-btn edit-btn" onclick="editExpense(<?= $expense['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?= $expense['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="expense-card total-row">
                        <div class="expense-footer">
                            <div>Total del mes:</div>
                            <div class="expense-amount">$<?= number_format($total_month, 2) ?></div>
                        </div>
                    </div>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/monthSelect.js"></script>
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

            // Configuración de Flatpickr
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
</body>
</html>
