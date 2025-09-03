<?php
// --- SOFT DELETE CUSTOMER LOGIC ---

// 1. Check if an ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../customer.php"); // Go back to the main customer page
    exit();
}

// 2. Include the database connection
require_once __DIR__ . '/../connection.php';

// 3. Get the ID and sanitize it
$id = intval($_GET['id']);

// 4. Prepare the UPDATE statement to mark the customer as inactive
// Instead of DELETE, we SET is_active to 0
$sql = "UPDATE customer SET is_active = 0 WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// 5. Bind the ID parameter and execute
$stmt->bind_param("i", $id);

// 6. Check for errors and redirect
if ($stmt->execute()) {
    // Success: Redirect back to the customer list
    header("Location: ../customer.php?status=deleted");
} else {
    // Failure: Redirect with an error message
    header("Location: ../customer.php?status=error");
}

$stmt->close();
$conn->close();
exit();

?>
