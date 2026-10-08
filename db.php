<?php

$conn = mysqli_connect("localhost", "root", "", "campuscare");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>