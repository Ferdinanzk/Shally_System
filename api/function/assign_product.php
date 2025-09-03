<?php
// 1. Include the database connection using a more robust path
require_once __DIR__ . '/../connection.php';

// --- INITIALIZATION ---
$order_id = null;
$error_message = '';
$success_message = '';
$order_customer_name = '';
$existing_items = [];

// Get the Order ID from the URL
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Fetch the customer's name for display
    $customer_stmt = $conn->prepare("SELECT c.`name(abbreviation)` FROM orders o JOIN customer c ON o.customer_id = c.id WHERE o.id = ?");
    $customer_stmt->bind_param("i", $order_id);
    $customer_stmt->execute();
    $customer_result = $customer_stmt->get_result();
    if ($customer_row = $customer_result->fetch_assoc()) {
        $order_customer_name = $customer_row['name(abbreviation)'];
    }
    $customer_stmt->close();

    // Fetch existing items for this order to pre-populate the form
    $existing_items_stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
    $existing_items_stmt->bind_param("i", $order_id);
    $existing_items_stmt->execute();
    $existing_items_result = $existing_items_stmt->get_result();
    while ($row = $existing_items_result->fetch_assoc()) {
        $existing_items[$row['item_id']] = $row['quantity'];
    }
    $existing_items_stmt->close();
} else {
    // Redirect if no valid order_id is provided
    header("Location: ../orders.php");
    exit();
}

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    
    // Start a transaction
    $conn->begin_transaction();
    try {
        // Step 1: Delete all existing items for this order
        $delete_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $delete_stmt->bind_param("i", $order_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Step 2: Insert the new set of items
        if (!empty($items)) {
            $insert_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity) VALUES (?, ?, ?)");
            foreach ($items as $item_id => $quantity) {
                $quantity = intval($quantity);
                if ($quantity > 0) { // Only insert if quantity is positive
                    $item_id = intval($item_id);
                    $insert_stmt->bind_param("iii", $order_id, $item_id, $quantity);
                    $insert_stmt->execute();
                }
            }
            $insert_stmt->close();
        }
        
        // If everything was successful, commit the transaction
        $conn->commit();
        $success_message = "產品已成功更新！";
        
        // Refresh existing items array after successful update
        $existing_items = [];
        $existing_items_stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
        $existing_items_stmt->bind_param("i", $order_id);
        $existing_items_stmt->execute();
        $existing_items_result = $existing_items_stmt->get_result();
        while ($row = $existing_items_result->fetch_assoc()) {
            $existing_items[$row['item_id']] = $row['quantity'];
        }
        $existing_items_stmt->close();

    } catch (mysqli_sql_exception $exception) {
        // If anything went wrong, roll back the transaction
        $conn->rollback();
        $error_message = "更新失敗：" . $exception->getMessage();
    }
}


// --- DATA FETCHING FOR DISPLAY ---
// Fetch all products to display in the form
$products = [];
$product_result = $conn->query("SELECT id, item_name, item_code, category FROM item ORDER BY category, item_name");
while ($row = $product_result->fetch_assoc()) {
    $products[] = $row;
}

// Fetch all unique categories for the filter dropdown
$categories = [];
$category_result = $conn->query("SELECT DISTINCT category FROM item ORDER BY category");
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row['category'];
}


// --- PAGE SETUP ---
$page_title = '分配產品';
require_once __DIR__ . '/../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">分配產品至訂單 #<?php echo $order_id; ?></h1>
        <p class="text-gray-600 mt-1">客戶: <?php echo htmlspecialchars($order_customer_name); ?></p>
    </header>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">錯誤！</strong>
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">成功！</strong>
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <form action="assign_product.php?order_id=<?php echo $order_id; ?>" method="POST">
            
            <!-- Filters -->
            <div class="flex flex-col md:flex-row gap-4 mb-6 sticky top-0 bg-white py-4 z-10">
                <input type="text" id="search-input" placeholder="依名稱或編號搜尋產品..." class="w-full md:w-1/2 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <select id="category-filter" class="w-full md:w-1/2 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">所有分類</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Product List Table -->
            <div class="max-h-[60vh] overflow-y-auto">
                <table class="w-full table-auto border-collapse">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">產品名稱</th>
                            <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分類</th>
                            <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">數量</th>
                        </tr>
                    </thead>
                    <tbody id="product-table-body">
                        <?php foreach ($products as $product): ?>
                            <tr class="product-row border-b" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-name="<?php echo htmlspecialchars(strtolower($product['item_name'])); ?>" data-code="<?php echo htmlspecialchars(strtolower($product['item_code'])); ?>">
                                <td class="p-3">
                                    <label for="item-<?php echo $product['id']; ?>" class="font-medium text-gray-900 block">
                                        <?php echo htmlspecialchars($product['item_name']); ?>
                                        <span class="text-gray-500 text-sm block"><?php echo htmlspecialchars($product['item_code']); ?></span>
                                    </label>
                                </td>
                                <td class="p-3 text-sm text-gray-600"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td class="p-3">
                                    <input 
                                        type="number" 
                                        min="0" 
                                        name="items[<?php echo $product['id']; ?>]" 
                                        id="item-<?php echo $product['id']; ?>"
                                        value="<?php echo isset($existing_items[$product['id']]) ? htmlspecialchars($existing_items[$product['id']]) : '0'; ?>"
                                        class="w-full p-2 border rounded-lg text-center"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex justify-end gap-4">
                <a href="../orders.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">返回訂單列表</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">儲存變更</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const productTableBody = document.getElementById('product-table-body');
    const productRows = productTableBody.getElementsByClassName('product-row');

    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;

        for (let row of productRows) {
            const category = row.getAttribute('data-category');
            const name = row.getAttribute('data-name');
            const code = row.getAttribute('data-code');

            const categoryMatch = selectedCategory === '' || category === selectedCategory;
            const searchMatch = searchTerm === '' || name.includes(searchTerm) || code.includes(searchTerm);

            if (categoryMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    searchInput.addEventListener('keyup', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
});
</script>

<?php
// Include the footer template using a more robust path
require_once __DIR__ . '/../template/footer.php';
?>
