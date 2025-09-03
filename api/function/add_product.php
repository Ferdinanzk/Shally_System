<?php
// --- ADD PRODUCT LOGIC ---
require_once '../connection.php';

$item_code = '';
$item_name = '';
$category = '';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_code = trim($_POST['item_code']);
    $item_name = trim($_POST['item_name']);
    $category = trim($_POST['category']);

    // Validation
    if (empty($item_code) || empty($item_name) || empty($category)) {
        $error_message = "所有欄位都是必填的。";
    } else {
        // Prepare and execute INSERT statement
        $sql = "INSERT INTO item (item_code, item_name, category) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $item_code, $item_name, $category);
        
        if ($stmt->execute()) {
            $success_message = "新產品新增成功！";
            // Clear fields after success
            $item_code = '';
            $item_name = '';
            $category = '';
        } else {
            $error_message = "新增失敗：" . $conn->error;
        }
        $stmt->close();
    }
    $conn->close();
}

// --- PAGE SETUP ---
$page_title = '新增產品';
require_once '../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">新增產品</h1>
        <p class="text-gray-600 mt-1">請填寫新產品的詳細資訊。</p>
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

        <form action="add_product.php" method="POST">
            <div class="mb-4">
                <label for="item_code" class="block text-gray-700 text-sm font-bold mb-2">產品編號:</label>
                <input type="text" id="item_code" name="item_code" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($item_code); ?>" required>
            </div>
            <div class="mb-4">
                <label for="item_name" class="block text-gray-700 text-sm font-bold mb-2">產品名稱:</label>
                <input type="text" id="item_name" name="item_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($item_name); ?>" required>
            </div>
            <div class="mb-6">
                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">分類:</label>
                <input type="text" id="category" name="category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?php echo htmlspecialchars($category); ?>" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    新增產品
                </button>
                <a href="../products.php" class="inline-block font-bold text-sm text-gray-600 hover:text-gray-800">
                    返回產品列表
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
