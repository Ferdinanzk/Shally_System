<?php
// --- SETUP & DATA FETCHING (with security enhancements) ---
require_once __DIR__ . '/../connection.php';

$error_message = '';
$success_message = '';

// 1. Validate the Order ID from the URL
if (!isset($_GET['order_id']) && !isset($_POST['order_id'])) {
    // Redirect to the main orders page if no ID is specified
    header("Location: ../orders.php");
    exit();
}
$order_id = intval($_GET['order_id'] ?? $_POST['order_id']);


// --- Handle POST request (form submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start a transaction for data safety
    $conn->begin_transaction();

    try {
        $item_ids = $_POST['item_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        // 1. Delete all existing items for this order
        $stmt_delete = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_delete->bind_param("i", $order_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // 2. Insert the new set of items
        $stmt_insert = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity) VALUES (?, ?, ?)");
        
        foreach ($item_ids as $index => $item_id) {
            $quantity = intval($quantities[$index]);
            $item_id = intval($item_id);
            // Only insert if both item and quantity are valid
            if ($item_id > 0 && $quantity > 0) {
                $stmt_insert->bind_param("iii", $order_id, $item_id, $quantity);
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();

        // 3. If everything was successful, commit the transaction
        $conn->commit();
        $success_message = "訂單 #" . $order_id . " 的品項已成功更新！";

    } catch (Exception $e) {
        // If anything fails, roll back the transaction to prevent partial data
        $conn->rollback();
        $error_message = "更新訂單失敗：" . $e->getMessage();
    }
}


// --- Fetch data for the page using prepared statements ---
// Fetch order details for display
$stmt_order = $conn->prepare("SELECT c.`name(abbreviation)` as customer_name FROM orders o JOIN customer c ON o.customer_id = c.id WHERE o.id = ?");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
$order = $order_result->fetch_assoc();
$stmt_order->close();

// Fetch all available items and categories for the dropdowns and filters
$items = [];
$items_result = $conn->query("SELECT id, item_name, item_code, category FROM item ORDER BY category, item_name ASC");
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

$categories = [];
$categories_result = $conn->query("SELECT DISTINCT category FROM item ORDER BY category ASC");
while($row = $categories_result->fetch_assoc()){
    $categories[] = $row['category'];
}

// Fetch items currently assigned to this order to pre-populate the form
$assigned_items = [];
$stmt_assigned = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
$stmt_assigned->bind_param("i", $order_id);
$stmt_assigned->execute();
$assigned_items_result = $stmt_assigned->get_result();
while($row = $assigned_items_result->fetch_assoc()){
    $assigned_items[] = $row;
}
$stmt_assigned->close();


// --- PAGE SETUP ---
$page_title = '指派產品至訂單 #' . $order_id;
require_once __DIR__ . '/../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">指派產品至訂單 <span class="text-indigo-600">#<?php echo $order_id; ?></span></h1>
        <p class="text-gray-600 mt-1">客戶: <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
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

        <form action="assign_product.php" method="POST">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

            <!-- Filters for Product Selection -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="category-filter" class="block text-sm font-medium text-gray-700 mb-1">依分類篩選</label>
                    <select id="category-filter" class="w-full p-2 border rounded-lg text-gray-700">
                        <option value="">所有分類</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="search-filter" class="block text-sm font-medium text-gray-700 mb-1">依名稱/編號搜尋</label>
                    <input type="text" id="search-filter" placeholder="搜尋產品..." class="w-full p-2 border rounded-lg text-gray-700">
                </div>
            </div>

            <!-- Order Items Section -->
            <h2 class="text-xl font-bold text-gray-800 mb-4">訂單品項</h2>
            <div id="order-items-container" class="space-y-4">
                <?php foreach ($assigned_items as $assigned_item): ?>
                <div class="order-item-row grid grid-cols-12 gap-4 items-center p-2 bg-gray-50 rounded-lg">
                    <div class="col-span-7">
                        <select name="item_id[]" class="item-select w-full p-2 border rounded text-gray-700" required>
                            <option value="">-- 選擇產品 --</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" data-category="<?php echo htmlspecialchars($item['category']); ?>" <?php echo ($item['id'] == $assigned_item['item_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <input type="number" name="quantity[]" class="w-full p-2 border rounded text-gray-700" placeholder="數量" min="1" value="<?php echo htmlspecialchars($assigned_item['quantity']); ?>" required>
                    </div>
                    <div class="col-span-2">
                        <button type="button" class="remove-item-btn bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded w-full">移除</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <button type="button" id="add-item-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    新增品項
                </button>
            </div>

            <!-- Submission Buttons -->
            <div class="flex items-center justify-between mt-8 border-t pt-6">
                <a href="view_order_details.php?id=<?php echo $order_id; ?>" class="inline-block font-bold text-sm text-gray-600 hover:text-gray-800">
                    返回訂單詳情
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded">
                    更新訂單品項
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template for new item rows -->
<template id="item-row-template">
    <div class="order-item-row grid grid-cols-12 gap-4 items-center p-2 bg-gray-50 rounded-lg">
        <div class="col-span-7">
            <select name="item_id[]" class="item-select w-full p-2 border rounded text-gray-700" required>
                <option value="">-- 選擇產品 --</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?php echo $item['id']; ?>" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                        <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-span-3">
            <input type="number" name="quantity[]" class="w-full p-2 border rounded text-gray-700" placeholder="數量" min="1" required>
        </div>
        <div class="col-span-2">
            <button type="button" class="remove-item-btn bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded w-full">移除</button>
        </div>
    </div>
</template>

<!-- JavaScript for Dynamic Rows & Filtering -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('order-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    const categoryFilter = document.getElementById('category-filter');
    const searchFilter = document.getElementById('search-filter');
    const template = document.getElementById('item-row-template');
    
    // If there are no items initially, add one empty row for the user to start
    if (container.children.length === 0) {
        addNewItemRow();
    }

    function addNewItemRow() {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        filterProductDropdowns(); // Apply current filters to the new row
    }

    addItemBtn.addEventListener('click', addNewItemRow);

    container.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-item-btn')) {
            e.target.closest('.order-item-row').remove();
        }
    });

    // --- Filtering Logic ---
    function filterProductDropdowns() {
        const categoryValue = categoryFilter.value;
        const searchValue = searchFilter.value.toLowerCase();
        const allDropdowns = container.querySelectorAll('.item-select');

        allDropdowns.forEach(select => {
            const selectedValue = select.value; // Preserve the currently selected value
            const options = select.querySelectorAll('option');
            
            options.forEach(option => {
                // Always show the placeholder and the selected option
                if (!option.value || option.value === selectedValue) {
                    option.style.display = '';
                    return;
                }

                const optionCategory = option.dataset.category;
                const optionText = option.textContent.toLowerCase();
                
                const categoryMatch = categoryValue === '' || optionCategory === categoryValue;
                const searchMatch = searchValue === '' || optionText.includes(searchValue);

                option.style.display = (categoryMatch && searchMatch) ? '' : 'none';
            });
        });
    }

    categoryFilter.addEventListener('change', filterProductDropdowns);
    searchFilter.addEventListener('input', filterProductDropdowns);
});
</script>

<?php
$conn->close();
require_once __DIR__ . '/../template/footer.php';
?>

