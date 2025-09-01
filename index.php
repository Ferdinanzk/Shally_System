<?php
// --- PRODUCTION LIST LOGIC ---

// 1. Include the database connection
require_once 'connection.php';

// 2. Set the date range for today
// This ensures we get all orders for the current day, from midnight to 11:59:59 PM.
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// 3. Define the SQL query
// This query joins orders, order_items, and items.
// It sums the quantities for each item and groups them.
// It only includes orders with a shipment_date for today.
$sql = "SELECT
            i.item_code,
            i.item_name,
            i.category,
            SUM(oi.quantity) AS total_quantity
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN item i ON oi.item_id = i.id
        WHERE o.shipment_date >= ? AND o.shipment_date <= ?
        GROUP BY i.category, i.item_code, i.item_name
        ORDER BY i.category, i.item_name";

// 4. Prepare and execute the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $today_start, $today_end);
$stmt->execute();
$result = $stmt->get_result();

// --- PAGE SETUP ---
$page_title = '今日生產列表'; // Set the page title for the header
require_once 'template/header.php'; // Include the header template from the 'template' folder

?>

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">今日生產列表</h1>
            <p class="text-gray-600 mt-1">匯總今日所有訂單需要生產的品項與數量。</p>
        </header>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分類</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品項編號</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品項名稱</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">總數量</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        // Check if there are results
                        if ($result->num_rows > 0) {
                            $current_category = '';
                            // Loop through each row of the result set
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                // Display the category only when it changes
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-700'>" . htmlspecialchars($row['category']) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row['item_code']) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['item_name']) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900'>" . htmlspecialchars($row['total_quantity']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            // Display a message if no items are scheduled for production today
                            echo "<tr><td colspan='4' class='text-center px-6 py-4 text-gray-500'>今日沒有需要生產的品項。</td></tr>";
                        }

                        // Close the statement and the database connection
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
require_once 'template/footer.php'; // Include the footer template from the 'template' folder
?>
