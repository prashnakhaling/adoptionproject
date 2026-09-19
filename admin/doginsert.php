<?php
// Database connection
$host = 'localhost';
$db = 'dogadoption';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $breed = trim($_POST['breed'] ?? '');
    $age = (int)($_POST['age'] ?? 0);

    // Validate inputs
    if (empty($breed) || $age <= 0) {
        die("Breed and valid age are required.");
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = 'dogpic/';
        $originalName = basename($_FILES['image']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Convert .jfif to .jpg for compatibility
        if ($extension === 'jfif') {
            $extension = 'jpg';
        }

        // Validate allowed file types
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedTypes)) {
            die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        }

        // Verify actual image content
        $imageCheck = getimagesize($_FILES['image']['tmp_name']);
        if ($imageCheck === false) {
            die("Uploaded file is not a valid image.");
        }

        // Generate 4-letter random name
        $randomName = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 4);
        $uniqueName = $randomName . '.' . $extension;
        $uploadPath = $uploadDir . $uniqueName;

        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $stmt = $conn->prepare("INSERT INTO dogs (dog_breed, age, dog_image, added_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sis", $breed, $age, $uploadPath);

            if ($stmt->execute()) {
                header("Location: admindashboard.php");
                exit;
            } else {
                echo "Database error: " . htmlspecialchars($stmt->error);
            }

            $stmt->close();
        } else {
            echo "Failed to move uploaded file.";
        }
    } else {
        echo "No image uploaded or an error occurred during upload.";
    }
}

$conn->close();
