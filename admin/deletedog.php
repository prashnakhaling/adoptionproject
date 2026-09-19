<?php
include 'dataconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dog_id'])) {
    $dog_id = (int)$_POST['dog_id'];

    // Fetch the image path
    $stmt = $conn->prepare("SELECT dog_image FROM dogs WHERE dog_id = ?");
    $stmt->bind_param("i", $dog_id);
    $stmt->execute();
    $stmt->bind_result($imagePath);
    $stmt->fetch();
    $stmt->close();

    // Delete the dog record
    $stmt = $conn->prepare("DELETE FROM dogs WHERE dog_id = ?");
    $stmt->bind_param("i", $dog_id);
    $stmt->execute();
    $stmt->close();

    // Delete image file if exists
    if (!empty($imagePath)) {
        $file = __DIR__ . '/' . trim($imagePath);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    header("Location: admindashboard.php");
    exit();
} else {
    echo "Invalid request.";
}
?>

