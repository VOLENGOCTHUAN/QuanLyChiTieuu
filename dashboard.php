<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db_connect.php';
require 'exchange.php';  // >>> Thêm dòng này để dùng chuyển USD

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

// 1. LẤY SỐ LIỆU THỐNG KÊ TỔNG QUAN (cho tháng hiện tại)
$current_month = date('Y-m');

// Tổng Thu
$income_result = $conn->query(
    "SELECT SUM(t.amount) AS total_income
     FROM Transactions t
     JOIN Categories c ON t.category_id = c.category_id
     WHERE t.user_id = $current_user_id 
     AND c.type = 'income'
     AND DATE_FORMAT(t.transaction_date, '%Y-%m') = '$current_month'"
);
$total_income = $income_result->fetch_assoc()['total_income'] ?? 0;

// Tổng Chi
$expense_result = $conn->query(
    "SELECT SUM(t.amount) AS total_expense
     FROM Transactions t
     JOIN Categories c ON t.category_id = c.category_id
     WHERE t.user_id = $current_user_id 
     AND c.type = 'expense'
     AND DATE_FORMAT(t.transaction_date, '%Y-%m') = '$current_month'"
);
$total_expense = $expense_result->fetch_assoc()['total_expense'] ?? 0;

// Số dư
$balance = $total_income - $total_expense;

// >>> Chuyển sang USD
$income_usd    = vnd_to_usd($total_income);
$expense_usd   = vnd_to_usd($total_expense);
$balance_usd   = vnd_to_usd($balance);


// 2. BIỂU ĐỒ CHI TIÊU
$chart_data_result = $conn->query(
    "SELECT c.name, SUM(t.amount) AS total_amount
     FROM Transactions t
     JOIN Categories c ON t.category_id = c.category_id
     WHERE t.user_id = $current_user_id 
     AND c.type = 'expense'
     AND DATE_FORMAT(t.transaction_date, '%Y-%m') = '$current_month'
     GROUP BY c.name
     ORDER BY total_amount DESC"
);

$chart_labels = [];
$chart_values = [];

if ($chart_data_result->num_rows > 0) {
    while ($row = $chart_data_result->fetch_assoc()) {
        $chart_labels[] = $row['name'];
        $chart_values[] = $row['total_amount'];
    }
}

$js_chart_labels = json_encode($chart_labels);
$js_chart_values = json_encode($chart_values);


// Lấy danh mục
$expense_categories_result = $conn->query(
    "SELECT * FROM Categories WHERE user_id = $current_user_id AND type = 'expense'"
);
$income_categories_result = $conn->query(
    "SELECT * FROM Categories WHERE user_id = $current_user_id AND type = 'income'"
);

// Lấy giao dịch
$transactions_result = $conn->query("
    SELECT t.transaction_id, t.amount, t.transaction_date, t.description, c.name AS category_name
    FROM Transactions t
    JOIN Categories c ON t.category_id = c.category_id
    WHERE t.user_id = $current_user_id
    ORDER BY t.transaction_date DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bảng điều khiển</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    .summary {
        display: flex;
        justify-content: space-around;
        background: #f4f4f4;
        padding: 20px;
    }
    .summary-box { text-align: center; }
    .income { color: green; }
    .expense { color: red; }
    .balance { color: blue; }
    .content { display: flex; gap: 20px; margin-top: 20px; }
    .add-transaction { flex: 1; }
    .chart-container { flex: 1; max-width: 400px; }
    </style>
</head>

<body>
<header>
    <h1>Chào mừng, <?php echo htmlspecialchars($current_username); ?>!</h1>
    <nav>
        <a href="categories.php">Quản lý Danh mục</a> |
        <a href="actions/action_logout.php">Đăng xuất</a>
    </nav>
</header>

<section class="summary">
    <div class="summary-box">
        <h3>Tổng Thu (Tháng này)</h3>
        <p class="income"><?php echo number_format($total_income); ?> VND</p>
        <p class="income">(<?php echo number_format($income_usd, 2); ?> USD)</p>
    </div>

    <div class="summary-box">
        <h3>Tổng Chi (Tháng này)</h3>
        <p class="expense"><?php echo number_format($total_expense); ?> VND</p>
        <p class="expense">(<?php echo number_format($expense_usd, 2); ?> USD)</p>
    </div>

    <div class="summary-box">
        <h3>Số dư</h3>
        <p class="balance"><?php echo number_format($balance); ?> VND</p>
        <p class="balance">(<?php echo number_format($balance_usd, 2); ?> USD)</p>
    </div>
</section>


<main class="content">
    <section class="add-transaction">
        <h2>Thêm Chi tiêu</h2>
        <form action="actions/action_add_transaction.php" method="POST">
            <label>Số tiền:</label>
            <input type="number" name="amount" required>

            <label>Ngày:</label>
            <input type="date" name="date" required>

            <label>Danh mục:</label>
            <select name="category_id" required>
                <option value="">-- Chọn danh mục --</option>
                <?php
                while ($row = $expense_categories_result->fetch_assoc()) {
                    echo "<option value='{$row['category_id']}'>{$row['name']}</option>";
                }
                ?>
            </select>

            <label>Ghi chú:</label>
            <textarea name="description"></textarea>

            <button type="submit">Thêm Chi tiêu</button>
        </form>
    </section>

    <section class="add-income" style="background-color: #f0f8ff;">
        <h2>Thêm Thu nhập</h2>
        <form action="actions/action_add_transaction.php" method="POST">
            <label>Số tiền:</label>
            <input type="number" name="amount" required>

            <label>Ngày:</label>
            <input type="date" name="date" required>

            <label>Danh mục:</label>
            <select name="category_id" required>
                <option value="">-- Chọn danh mục --</option>
                <?php
                while ($row = $income_categories_result->fetch_assoc()) {
                    echo "<option value='{$row['category_id']}'>{$row['name']}</option>";
                }
                ?>
            </select>

            <label>Ghi chú:</label>
            <textarea name="description"></textarea>

            <button type="submit">Thêm Thu nhập</button>
        </form>
    </section>

    <section class="chart-container">
        <h2>Chi tiêu tháng này</h2>
        <canvas id="expensePieChart"></canvas>
    </section>
</main>


<section class="transaction-list">
    <h2>Giao dịch gần đây</h2>
    <table>
        <thead>
            <tr>
                <th>Ngày</th>
                <th>Danh mục</th>
                <th>Số tiền</th>
                <th>Ghi chú</th>
                <th>USD</th>
                <th>Hành động</th>
            </tr>
        </thead>

        <tbody>
        <?php
        if ($transactions_result->num_rows > 0) {
            while ($row = $transactions_result->fetch_assoc()) {
                $amount_usd = vnd_to_usd($row['amount']);

                echo "<tr>";
                echo "<td>{$row['transaction_date']}</td>";
                echo "<td>{$row['category_name']}</td>";
                echo "<td>" . number_format($row['amount']) . " VND</td>";
                echo "<td>{$row['description']}</td>";
                echo "<td>" . number_format($amount_usd, 2) . " USD</td>";

                echo "<td>
                    <a href='edit_transaction.php?id={$row['transaction_id']}'>Sửa</a> | 
                    <a href='actions/action_delete_transaction.php?id={$row['transaction_id']}'
                       onclick='return confirm(\"Bạn có chắc chắn muốn xóa giao dịch này?\")'
                       style='color:red;'>Xóa</a>
                </td>";

                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Chưa có giao dịch nào.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</section>


<script>
const labels = <?php echo $js_chart_labels; ?>;
const dataValues = <?php echo $js_chart_values; ?>;

if (labels.length > 0) {
    new Chart(document.getElementById('expensePieChart'), {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: dataValues,
                backgroundColor: [
                    'rgba(255,99,132,0.8)',
                    'rgba(54,162,235,0.8)',
                    'rgba(255,206,86,0.8)',
                    'rgba(75,192,192,0.8)',
                    'rgba(153,102,255,0.8)',
                    'rgba(255,159,64,0.8)'
                ]
            }]
        }
    });
}
</script>

</body>
</html>

<?php $conn->close(); ?>
