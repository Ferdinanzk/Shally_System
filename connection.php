<?php
// --- Database Connection ---
// This script establishes a connection to a MySQL database.

// 1. Set database credentials
// Replace these with your actual database details.
$servername = "localhost"; // Or your server's IP address
$username = "root";   // Your database username
$password = "";   // Your database password
$dbname = "莎莉好食_1";     // The name of the database you want to connect to

// 2. Create connection
// We are creating a new mysqli object. The constructor takes the credentials as parameters.
$conn = new mysqli($servername, $username, $password, $dbname);

// 3. Check connection
// The connect_error property will be non-null if an error occurred during the connection attempt.
if ($conn->connect_error) {
    // die() will exit the script and print a message.
    // It's a good practice to stop the script if the database connection fails.
    die("Connection failed: " . $conn->connect_error);
} 

// 4. Optional: Set character set
// It's good practice to set the character set to utf8mb4 for full Unicode support.
$conn->set_charset("utf8mb4");

/*
// --- Connection Success Message (Optional) ---
// You can uncomment the line below for testing to confirm the connection is successful.
// On a live site, you would typically leave this commented out.
// echo "Connected successfully";
*/

?>
