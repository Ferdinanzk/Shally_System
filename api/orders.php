<?php
// --- SETUP & DATA FETCHING ---
require_once 'connection.php';

// 1. Initialize filter variables from GET parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// 2. Check for the "Today" quick filter
if (isset($_GET['filter']) && $_GET['filter'] == 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
}

// 3. Build the dynamic SQL query
// We join the orders and customer tables to get the customer's name.
$sql = "SELECT
            o.id,
            o.shipment_date,
            o.remarks,
            c.`name(abbreviation)` as customer_name
        FROM orders o
        JOIN customer c ON o.customer_id = c.id
        WHERE c.is_active = 1"; // Only show orders from active customers

$params = [];
$types = '';

// Add date range filter if both dates are provided
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND o.shipment_date BETWEEN ? AND ?";
    // Add time to the end date to include the entire day
    $params[] = $start_date . " 00:00:00";
    $params[] = $end_date . " 23:59:59";
    $types .= 'ss';
}

// Add search term filter if provided
if (!empty($search_term)) {
    $sql .= " AND c.`name(abbreviation)` LIKE ?";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $types .= 's';
}

$sql .= " ORDER BY o.shipment_date DESC";

// 4. Prepare and execute the statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- PAGE SETUP ---
$page_title = '訂單列表';
require_once 'template/header.php';
?>

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">訂單列表</h1>
                <p class="text-gray-600 mt-1">查看、篩選並管理所有訂單。</p>
            </div>
            <a href="function/add_order.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                新增訂單
            </a>
        </header>

        <!-- Filter Bar -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-md">
            <form action="orders.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">搜尋客戶名稱</label>
                    <input type="search" id="search" name="search" placeholder="客戶名稱..." class="w-full p-2 border rounded-lg text-gray-700" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">出貨日期 (起)</label>
                    <input type="date" id="start_date" name="start_date" class="w-full p-2 border rounded-lg text-gray-700" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">出貨日期 (迄)</label>
                    <input type="date" id="end_date" name="end_date" class="w-full p-2 border rounded-lg text-gray-700" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <!-- Buttons -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">篩選</button>
                    <a href="orders.php?filter=today" class="w-full text-center bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">今日</a>
                    <a href="orders.php" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">清除</a>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單編號</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客戶名稱</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">出貨日期</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">備註</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900'>#" . htmlspecialchars($row["id"]) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800'>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . date("Y-m-d", strtotime($row["shipment_date"])) . "</td>";
                                echo "<td class='px-6 py-4 text-sm text-gray-700 max-w-xs truncate'>" . htmlspecialchars($row["remarks"]) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>";
                                echo "<a href='function/view_order_details.php?id=" . htmlspecialchars($row['id']) . "' class='text-blue-600 hover:text-blue-900'>詳情</a>";
                                echo "<a href='function/edit_order.php?id=" . htmlspecialchars($row['id']) . "' class='text-indigo-600 hover:text-indigo-900 ml-4'>編輯</a>";
                                echo "<a href='function/delete_order.php?id=" . htmlspecialchars($row['id']) . "' class='text-red-600 hover:text-red-900 ml-4' onclick='return confirm(\"您確定要刪除這筆訂單嗎？所有相關品項將一併刪除。\");'>刪除</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center px-6 py-4 text-gray-500'>找不到符合篩選條件的訂單。</td></tr>";
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
