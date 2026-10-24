<?php

include "../db.php";

date_default_timezone_set('Asia/Manila');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $plate = mysqli_real_escape_string(
        $conn,
        $_POST['plate']
    );

    $owner = mysqli_real_escape_string(
        $conn,
        $_POST['owner']
    );

    $slot = intval($_POST['slot']);

    // Use PHP's Philippine time instead of MySQL NOW()
    $timeIn = date('Y-m-d H:i:s');

    $sql = "
        INSERT INTO vehicles
        (
            plate_number,
            owner_name,
            slot_number,
            time_in
        )
        VALUES
        (
            '$plate',
            '$owner',
            '$slot',
            '$timeIn'
        )
    ";

    if (mysqli_query($conn, $sql)) {

        header("Location: index.php?added=success");
        exit();

    } else {

        echo "Error: " . mysqli_error($conn);
    }
}
?>