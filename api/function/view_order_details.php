<?php
// --- SETUP & DATA FETCHING ---
require_once __DIR__ . '/../connection.php';

// 1. Validate the Order ID from the URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("錯誤：無效或遺失的訂單ID。");
}
$order_id = intval($_GET['id']);

// 2. Fetch the main order details
$sql_order = "SELECT
                  o.id,
                  o.shipment_date,
                  o.remarks,
                  c.`name(abbreviation)` as customer_name,
                  c.cus_code
              FROM orders o
              JOIN customer c ON o.customer_id = c.id
              WHERE o.id = ?";
              
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows === 0) {
    die("找不到此訂單。");
}
$order = $result_order->fetch_assoc();
$stmt_order->close();

// 3. Fetch the items associated with this order
$sql_items = "SELECT
                  oi.quantity,
                  i.id as item_id,
                  i.item_code,
                  i.item_name,
                  i.category
              FROM order_items oi
              JOIN item i ON oi.item_id = i.id
              WHERE oi.order_id = ?
              ORDER BY i.category, i.item_name";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單詳情 #<?php echo $order_id; ?> - 莎莉好食</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for printing */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .container {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .bg-white {
                box-shadow: none !important;
                border: 1px solid #e5e7eb;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    
    <!-- Action Buttons (Hidden on Print) -->
    <div class="no-print mb-6 flex justify-end gap-4">
        <a href="../orders.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
            返回訂單列表
        </a>
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            列印此頁
        </button>
    </div>

    <header class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">訂單詳情 <span class="text-indigo-600">#<?php echo htmlspecialchars($order['id']); ?></span></h1>
    </header>

    <!-- Order Details Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">主要資訊</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div>
                <strong class="text-gray-600">客戶名稱:</strong>
                <p class="text-gray-900 text-lg"><?php echo htmlspecialchars($order['customer_name']); ?></p>
            </div>
            <div>
                <strong class="text-gray-600">客戶編號:</strong>
                <p class="text-gray-900 text-lg"><?php echo htmlspecialchars($order['cus_code']); ?></p>
            </div>
            <div>
                <strong class="text-gray-600">出貨日期:</strong>
                <p class="text-gray-900 text-lg"><?php echo date("Y年m月d日", strtotime($order['shipment_date'])); ?></p>
            </div>
            <div>
                <strong class="text-gray-600">備註:</strong>
                <p class="text-gray-900 text-lg"><?php echo !empty($order['remarks']) ? htmlspecialchars($order['remarks']) : '無'; ?></p>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-2xl font-semibold text-gray-800">訂單品項列表</h2>
            <a href="assign_product.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" class="no-print bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                指派產品
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品項編號</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品項名稱</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分類</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">數量</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    if ($result_items->num_rows > 0) {
                        while($item = $result_items->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($item["item_code"]) . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($item['item_name']) . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($item["category"]) . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900'>" . htmlspecialchars($item['quantity']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center px-6 py-4 text-gray-500'>此訂單沒有任何品項。</td></tr>";
                    }
                    $stmt_items->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
