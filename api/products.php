<?php
// --- SETUP & DATA FETCHING ---
require_once 'connection.php';

// 1. Fetch all unique categories for the filter dropdown
$category_sql = "SELECT DISTINCT category FROM item ORDER BY category ASC";
$category_result = $conn->query($category_sql);
$categories = [];
if ($category_result->num_rows > 0) {
    while($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// 2. Initialize filter variables
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

// 3. Build the dynamic SQL query
$sql = "SELECT `id`, `item_code`, `item_name`, `category` FROM item";
$where_clauses = [];
$params = [];
$types = '';

// Add category filter if selected
if (!empty($selected_category)) {
    $where_clauses[] = "`category` = ?";
    $params[] = $selected_category;
    $types .= 's';
}

// Add search term filter if provided
if (!empty($search_term)) {
    $where_clauses[] = "(`item_name` LIKE ? OR `item_code` LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

// Append WHERE clauses to the main query if any filters are active
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY category, item_name ASC";

// 4. Prepare and execute the statement
$stmt = $conn->prepare($sql);

// Bind parameters dynamically if there are any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- PAGE SETUP ---
$page_title = '產品列表';
require_once 'template/header.php';
?>

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">產品列表</h1>
                <p class="text-gray-600 mt-1">查看資料庫中的所有產品。</p>
            </div>
            <a href="function/add_product.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                新增產品
            </a>
        </header>

        <!-- Filter Bar -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-md">
            <form action="products.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <!-- Search Input -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">搜尋名稱或編號</label>
                    <div class="relative">
                        <input type="search" id="search" name="search" placeholder="搜尋..." class="w-full pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo htmlspecialchars($search_term); ?>">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                </div>
                <!-- Category Select -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">分類</label>
                    <select id="category" name="category" class="w-full p-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">所有分類</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($selected_category == $category) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Buttons -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">篩選</button>
                    <a href="products.php" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">清除</a>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">產品編號</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">產品名稱</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分類</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["item_code"]) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['item_name']) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["category"]) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>";
                                echo "<a href='function/edit_product.php?id=" . htmlspecialchars($row['id']) . "' class='text-indigo-600 hover:text-indigo-900'>編輯</a>";
                                echo "<a href='function/delete_product.php?id=" . htmlspecialchars($row['id']) . "' class='text-red-600 hover:text-red-900 ml-4' onclick='return confirm(\"您確定要刪除這個產品嗎？\");'>刪除</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center px-6 py-4 text-gray-500'>找不到符合篩選條件的產品。</td></tr>";
                        }
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
require_once 'template/footer.php';
?>
