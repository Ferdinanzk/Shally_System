<?php
// --- EDIT CUSTOMER LOGIC ---
require_once __DIR__ . '/../connection.php';

$name = '';
$cus_code = '';
$id = 0;
$error_message = '';
$success_message = '';

// --- Handle POST request (form submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get data from the form
    // Use intval for the ID and trim for strings
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $cus_code = trim($_POST['cus_code']);

    // 2. Simple validation
    if (empty($name) || empty($cus_code) || $id <= 0) {
        $error_message = "所有欄位都是必填的。";
    } else {
        // 3. Prepare the UPDATE statement to prevent SQL injection
        $sql = "UPDATE customer SET `name(abbreviation)` = ?, `cus_code` = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // 4. Bind parameters and execute
        $stmt->bind_param("ssi", $name, $cus_code, $id);
        
        if ($stmt->execute()) {
            $success_message = "客戶資料更新成功！";
        } else {
            $error_message = "更新失敗：" . $conn->error;
        }
        $stmt->close();
    }
}

// --- Handle GET request (loading the page) ---
// We only fetch the customer if it's a GET request to avoid re-fetching after a POST.
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        die("錯誤：未提供客戶ID。");
    }
    
    $id = intval($_GET['id']);
    
    // 1. Prepare a SELECT statement
    $sql = "SELECT `name(abbreviation)`, `cus_code` FROM customer WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    // 2. Bind ID parameter and execute
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // 3. Fetch the data
    if ($result->num_rows == 1) {
        $customer = $result->fetch_assoc();
        $name = $customer['name(abbreviation)'];
        $cus_code = $customer['cus_code'];
    } else {
        die("找不到該客戶。");
    }
    $stmt->close();
}

$conn->close();

// --- PAGE SETUP ---
$page_title = '編輯客戶';
require_once '../template/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">編輯客戶資料</h1>
        <p class="text-gray-600 mt-1">修改客戶的詳細資訊。</p>
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

        <form action="edit_customer.php" method="POST">
            <!-- Hidden input to store the customer ID -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

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
                    更新資料
                </button>
                <a href="../customer.php" class="inline-block align-baseline font-bold text-sm text-indigo-600 hover:text-indigo-800">
                    返回客戶列表
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
