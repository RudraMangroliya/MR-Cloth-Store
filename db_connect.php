<?php
// Set your database credentials
$servername = "localhost";
$username = "root"; // Your database username
$password = "Rudra@73"; // Your database password
$dbname = "mr_cloth_store";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
