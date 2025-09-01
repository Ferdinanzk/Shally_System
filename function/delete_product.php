<?php
// --- DELETE PRODUCT LOGIC ---

// 1. Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../products.php");
    exit();
}

// 2. Include database connection
require_once '../connection.php';

// 3. Get and sanitize the ID
$id = intval($_GET['id']);

// 4. Prepare and execute the DELETE statement
// NOTE: This will fail if the product is used in any orders due to foreign key constraints.
// A "soft delete" (setting an 'is_active' flag to 0) is the recommended long-term solution.
$sql = "DELETE FROM item WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $id);

// 5. Execute and redirect
if (!$stmt->execute()) {
    // If deletion fails, redirect with an error message
    // You can create a more user-friendly error display on products.php
    header("Location: ../products.php?error=deletefailed");
} else {
    // On success, redirect back to the product list
    header("Location: ../products.php");
}

$stmt->close();
$conn->close();
exit();

?>
