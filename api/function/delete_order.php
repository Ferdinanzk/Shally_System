<?php
// --- DELETE ORDER LOGIC ---

// 1. Check if an ID is provided in the URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // If no ID, redirect back to the orders list
    header("Location: ../orders.php");
    exit(); // Stop script execution
}

// 2. Include the database connection
require_once __DIR__ . '/../connection.php';

// 3. Get the ID and sanitize it
$order_id = intval($_GET['id']);

// 4. Prepare the DELETE statement
// Because of the "ON DELETE CASCADE" constraint in the database,
// deleting the order from the `orders` table will automatically
// delete all corresponding records from the `order_items` table.
$sql = "DELETE FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// 5. Bind the ID parameter and execute
$stmt->bind_param("i", $order_id);
$stmt->execute();

// 6. Close the statement and connection
$stmt->close();
$conn->close();

// 7. Redirect back to the orders list page after deletion
header("Location: ../orders.php");
exit();

?>
