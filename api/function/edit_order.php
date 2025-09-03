<?php
// --- SETUP & DATA FETCHING ---
require_once '../connection.php';

$error_message = '';
$success_message = '';
$order_id = 0;
$customer_id = 0;
$shipment_date = '';
$remarks = '';

// --- Handle POST request (form submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = intval($_POST['order_id']);
    $customer_id = intval($_POST['customer_id']);
    $shipment_date = trim($_POST['shipment_date']);
    $remarks = trim($_POST['remarks']);

    // Validation
    if (empty($customer_id) || empty($shipment_date) || $order_id <= 0) {
        $error_message = "客戶和出貨日期為必填項。";
    } else {
        // Prepare and execute UPDATE statement
        $sql = "UPDATE orders SET customer_id = ?, shipment_date = ?, remarks = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $customer_id, $shipment_date, $remarks, $order_id);

        if ($stmt->execute()) {
            $success_message = "訂單 #" . $order_id . " 的資料已成功更新！";
        } else {
            $error_message = "更新失敗：" . $conn->error;
        }
        $stmt->close();
    }
} else {
    // --- Handle GET request (load initial data) ---
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        die("錯誤：無效或遺失的訂單ID。");
    }
    $order_id = intval($_GET['id']);

    $sql = "SELECT customer_id, shipment_date, remarks FROM orders WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $order = $result->fetch_assoc();
        $customer_id = $order['customer_id'];
        $shipment_date = date('Y-m-d', strtotime($order['shipment_date'])); // Format for date input
        $remarks = $order['remarks'];
    } else {
        die("找不到此訂單。");
    }
    $stmt->close();
}

// Fetch active customers for the dropdown
$customers_result = $conn->query("SELECT id, `name(abbreviation)` FROM customer WHERE is_active = 1 ORDER BY `name(abbreviation)` ASC");

// --- PAGE SETUP ---
$page_title = '編輯訂單 #' . $order_id;
require_once '../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">編輯訂單 <span class="text-indigo-600">#<?php echo $order_id; ?></span></h1>
        <p class="text-gray-600 mt-1">修改訂單的主要資訊。</p>
    </header>

    <div class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
        
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form action="edit_order.php" method="POST">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

            <div class="mb-4">
                <label for="customer_id" class="block text-gray-700 text-sm font-bold mb-2">客戶:</label>
                <select id="customer_id" name="customer_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                    <option value="">-- 選擇客戶 --</option>
                    <?php while($customer = $customers_result->fetch_assoc()): ?>
                        <option value="<?php echo $customer['id']; ?>" <?php echo ($customer_id == $customer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name(abbreviation)']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="shipment_date" class="block text-gray-700 text-sm font-bold mb-2">出貨日期:</label>
                <input type="date" id="shipment_date" name="shipment_date" class="shadow border rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($shipment_date); ?>" required>
            </div>
            <div class="mb-6">
                <label for="remarks" class="block text-gray-700 text-sm font-bold mb-2">備註:</label>
                <textarea id="remarks" name="remarks" rows="4" class="shadow border rounded w-full py-2 px-3 text-gray-700"><?php echo htmlspecialchars($remarks); ?></textarea>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    更新訂單
                </button>
                <a href="../orders.php" class="inline-block font-bold text-sm text-gray-600 hover:text-gray-800">
                    返回訂單列表
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
require_once '../template/footer.php';
?>
