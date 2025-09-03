<?php
// --- ADD CUSTOMER LOGIC ---
require_once __DIR__ . '/../connection.php';

$name = '';
$cus_code = '';
$error_message = '';
$success_message = '';

// --- Handle POST request (form submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get data from the form and trim whitespace
    $name = trim($_POST['name']);
    $cus_code = trim($_POST['cus_code']);

    // 2. Simple validation
    if (empty($name) || empty($cus_code)) {
        $error_message = "所有欄位都是必填的。";
    } else {
        // 3. Prepare the INSERT statement to prevent SQL injection
        $sql = "INSERT INTO customer (`name(abbreviation)`, `cus_code`) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        // 4. Bind parameters and execute
        $stmt->bind_param("ss", $name, $cus_code);
        
        if ($stmt->execute()) {
            $success_message = "新客戶新增成功！";
            // Clear the form fields after successful submission
            $name = '';
            $cus_code = '';
        } else {
            $error_message = "新增失敗：" . $conn->error;
        }
        $stmt->close();
    }
    $conn->close();
}

// --- PAGE SETUP ---
$page_title = '新增客戶';
require_once '../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">新增客戶</h1>
        <p class="text-gray-600 mt-1">請填寫新客戶的詳細資訊。</p>
    </header>

    <div class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
        
        <!-- Display Success or Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form action="add_customer.php" method="POST">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">名字 (簡稱):</label>
                <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="mb-6">
                <label for="cus_code" class="block text-gray-700 text-sm font-bold mb-2">客戶編號:</label>
                <input type="text" id="cus_code" name="cus_code" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($cus_code); ?>" required>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    新增客戶
                </button>
                <a href="../customer.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                    返回客戶列表
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
