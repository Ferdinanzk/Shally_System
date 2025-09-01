<?php
// --- SEARCH & FILTER LOGIC ---
require_once 'connection.php';
$search_term = '';
// The base SQL query now only selects active customers.
$sql = "SELECT `id`, `name(abbreviation)`, `cus_code` FROM customer WHERE is_active = 1";

if (!empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
    // Append the search condition to the existing WHERE clause.
    $sql .= " AND (`name(abbreviation)` LIKE ? OR `cus_code` LIKE ?)";
    $search_param = "%" . $search_term . "%";
}

$stmt = $conn->prepare($sql);

if (!empty($search_term)) {
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();

// --- PAGE SETUP ---
$page_title = '客戶列表';
require_once 'template/header.php';
?>

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">客戶列表</h1>
                <p class="text-gray-600 mt-1">查看資料庫中的所有客戶。</p>
            </div>
            <a href="function/add_customer.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                新增客戶
            </a>
        </header>

        <div class="mb-6 bg-white p-4 rounded-lg shadow-md">
            <form action="customer.php" method="GET" class="flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:w-auto sm:flex-grow">
                    <input type="search" name="search" placeholder="搜尋名字或客戶編號..." class="w-full pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo htmlspecialchars($search_term); ?>">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                    </div>
                </div>
                <button type="submit" class="w-full sm:w-auto bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">搜尋</button>
                <a href="customer.php" class="w-full sm:w-auto text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">清除</a>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名字</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客戶編號</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['name(abbreviation)']) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row["cus_code"]) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>";
                                echo "<a href='function/edit_customer.php?id=" . htmlspecialchars($row['id']) . "' class='text-indigo-600 hover:text-indigo-900'>編輯</a>";
                                echo "<a href='function/delete_customer.php?id=" . htmlspecialchars($row['id']) . "' class='text-red-600 hover:text-red-900 ml-4' onclick='return confirm(\"您確定要刪除這位客戶嗎？\");'>刪除</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center px-6 py-4 text-gray-500'>找不到符合搜尋條件的客戶。</td></tr>";
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
