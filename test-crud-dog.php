<?php
include "includes/db.php";

echo "Testing CRUD on dogs table...<br><br>";

// INSERT
$sql = "INSERT INTO dogs (name, breed, age, gender, size, description)
        VALUES ('TestDog', 'TestBreed', 3, 'male', 'medium', 'Testing insert')";

if ($conn->query($sql) === TRUE) {
    echo "Insert OK<br>";
} else {
    echo "Insert FAILED: " . $conn->error . "<br>";
}

// SELECT
$sql = "SELECT * FROM dogs";
$result = $conn->query($sql);

if ($result) {
    echo "Select OK — Found " . $result->num_rows . " rows<br>";
} else {
    echo "Select FAILED: " . $conn->error . "<br>";
}

// UPDATE
$sql = "UPDATE dogs SET name='UpdatedDog' WHERE name='TestDog'";

if ($conn->query($sql) === TRUE) {
    echo "Update OK<br>";
} else {
    echo "Update FAILED: " . $conn->error . "<br>";
}

// DELETE
$sql = "DELETE FROM dogs WHERE name='UpdatedDog'";

if ($conn->query($sql) === TRUE) {
    echo "Delete OK<br>";
} else {
    echo "Delete FAILED: " . $conn->error . "<br>";
}

$conn->close();
?>
