<?php
$conn = mysqli_connect(
    "localhost",
    "u350827689_hypercar",
    "Hypercar123",
    "u350827689_hypercar"
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set Philippine Time
date_default_timezone_set('Asia/Manila');

// Set MySQL session to Philippine Time
mysqli_query($conn, "SET time_zone = '+08:00'");
?>