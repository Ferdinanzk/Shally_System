<?php
// Set a default title if the page calling this header doesn't set one.
if (!isset($page_title)) {
    $page_title = '莎莉好食';
}
// Determine the current page to highlight the active link in the navbar.
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - 莎莉好食</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom font */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="font-bold text-xl text-indigo-600">莎莉好食</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-200'; ?> px-3 py-2 rounded-md text-sm font-medium" <?php if ($current_page == 'index.php') echo 'aria-current="page"'; ?>>今日生產</a>
                        <a href="customer.php" class="<?php echo ($current_page == 'customer.php') ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-200'; ?> px-3 py-2 rounded-md text-sm font-medium" <?php if ($current_page == 'customer.php') echo 'aria-current="page"'; ?>>客戶</a>
                        <a href="products.php" class="<?php echo ($current_page == 'products.php') ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-200'; ?> px-3 py-2 rounded-md text-sm font-medium" <?php if ($current_page == 'products.php') echo 'aria-current="page"'; ?>>產品</a>
                        <a href="orders.php" class="<?php echo ($current_page == 'orders.php') ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-200'; ?> px-3 py-2 rounded-md text-sm font-medium" <?php if ($current_page == 'orders.php') echo 'aria-current="page"'; ?>>訂單</a>
                    </div>
                </div>
                <!-- Mobile menu button can be added here if needed -->
            </div>
        </div>
    </nav>
