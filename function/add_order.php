<?php
// --- SETUP & DATA FETCHING ---
require_once '../connection.php';

$error_message = '';
$success_message = '';

// --- Handle POST request (form submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start a transaction
    $conn->begin_transaction();

    try {
        // 1. Get main order details
        $customer_id = intval($_POST['customer_id']);
        $shipment_date = trim($_POST['shipment_date']);
        $remarks = trim($_POST['remarks']);
        $item_ids = $_POST['item_id'];
        $quantities = $_POST['quantity'];

        // 2. Validate input
        if (empty($customer_id) || empty($shipment_date) || empty($item_ids)) {
            throw new Exception("客戶、出貨日期及至少一項產品為必填項。");
        }

        // 3. Insert into 'orders' table
        $sql_order = "INSERT INTO orders (customer_id, shipment_date, remarks) VALUES (?, ?, ?)";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("iss", $customer_id, $shipment_date, $remarks);
        $stmt_order->execute();
        
        // Get the ID of the new order
        $order_id = $conn->insert_id;
        $stmt_order->close();

        // 4. Insert into 'order_items' table
        $sql_items = "INSERT INTO order_items (order_id, item_id, quantity) VALUES (?, ?, ?)";
        $stmt_items = $conn->prepare($sql_items);
        
        $item_added = false;
        foreach ($item_ids as $index => $item_id) {
            $quantity = intval($quantities[$index]);
            // Only add items with a quantity greater than 0
            if ($item_id > 0 && $quantity > 0) {
                $stmt_items->bind_param("iii", $order_id, $item_id, $quantity);
                $stmt_items->execute();
                $item_added = true;
            }
        }
        $stmt_items->close();
        
        if (!$item_added) {
             throw new Exception("請至少為一項產品輸入大於0的數量。");
        }

        // 5. If everything was successful, commit the transaction
        $conn->commit();
        $success_message = "訂單 #" . $order_id . " 已成功新增！";

    } catch (Exception $e) {
        // If anything fails, roll back the transaction
        $conn->rollback();
        $error_message = "新增訂單失敗：" . $e->getMessage();
    }
}

// --- Fetch data for the form dropdowns ---
// Fetch active customers
$customers_result = $conn->query("SELECT id, `name(abbreviation)` FROM customer WHERE is_active = 1 ORDER BY `name(abbreviation)` ASC");
// Fetch all items
$items_result = $conn->query("SELECT id, item_name, item_code FROM item ORDER BY item_name ASC");

// --- PAGE SETUP ---
$page_title = '新增訂單';
require_once '../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">新增訂單</h1>
        <p class="text-gray-600 mt-1">請填寫訂單的詳細資訊及所需品項。</p>
    </header>

    <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
        
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

        <form action="add_order.php" method="POST">
            <!-- Order Details Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="customer_id" class="block text-gray-700 text-sm font-bold mb-2">客戶:</label>
                    <select id="customer_id" name="customer_id" class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="">-- 選擇客戶 --</option>
                        <?php while($customer = $customers_result->fetch_assoc()): ?>
                            <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name(abbreviation)']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="shipment_date" class="block text-gray-700 text-sm font-bold mb-2">出貨日期:</label>
                    <input type="date" id="shipment_date" name="shipment_date" class="shadow border rounded w-full py-2 px-3 text-gray-700" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="md:col-span-2">
                    <label for="remarks" class="block text-gray-700 text-sm font-bold mb-2">備註:</label>
                    <textarea id="remarks" name="remarks" rows="3" class="shadow border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>
            </div>

            <!-- Order Items Section -->
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-t pt-4">訂單品項</h2>
            <div id="order-items-container" class="space-y-4">
                <!-- Dynamic item rows will be added here -->
            </div>

            <div class="mt-4">
                <button type="button" id="add-item-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    新增品項
                </button>
            </div>

            <!-- Submission Buttons -->
            <div class="flex items-center justify-end mt-8 border-t pt-6">
                <a href="../orders.php" class="inline-block font-bold text-sm text-gray-600 hover:text-gray-800 mr-6">
                    返回訂單列表
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded">
                    儲存訂單
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for Dynamic Rows -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('order-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    
    // The HTML template for a new item row
    const itemRowTemplate = `
        <div class="order-item-row grid grid-cols-12 gap-4 items-center p-2 bg-gray-50 rounded-lg">
            <div class="col-span-7">
                <select name="item_id[]" class="item-select w-full p-2 border rounded text-gray-700" required>
                    <option value="">-- 選擇產品 --</option>
                    <?php mysqli_data_seek($items_result, 0); // Reset pointer for template ?>
                    <?php while($item = $items_result->fetch_assoc()): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-span-3">
                <input type="number" name="quantity[]" class="w-full p-2 border rounded text-gray-700" placeholder="數量" min="1" required>
            </div>
            <div class="col-span-2">
                <button type="button" class="remove-item-btn bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded w-full">移除</button>
            </div>
        </div>
    `;

    // Function to add a new item row
    function addNewItemRow() {
        const div = document.createElement('div');
        div.innerHTML = itemRowTemplate.trim();
        container.appendChild(div.firstChild);
    }

    // Add the first row when the page loads
    addNewItemRow();

    // Event listener for the "Add Item" button
    addItemBtn.addEventListener('click', addNewItemRow);

    // Event listener for "Remove" buttons (using event delegation)
    container.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-item-btn')) {
            // Find the parent .order-item-row and remove it
            e.target.closest('.order-item-row').remove();
        }
    });
});
</script>

<?php
$conn->close();
require_once '../template/footer.php';
?>
