<?php
// Handle form submission for adoption
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fullname'])) {
    $mysqli = new mysqli("localhost", "root", "", "dogadoption");

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $fullname = $mysqli->real_escape_string($_POST['fullname']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $phone = $mysqli->real_escape_string($_POST['phone']);
    $address = $mysqli->real_escape_string($_POST['address']);
    $dogname = $mysqli->real_escape_string($_POST['dogname']);

    $query = "INSERT INTO adoptions (fullname, email, phone, address, dogname) 
              VALUES ('$fullname', '$email', '$phone', '$address', '$dogname')";

    if ($mysqli->query($query)) {
        echo "<script>alert('Application submitted successfully!');</script>";
    } else {
        echo "<script>alert('Error submitting application. Please try again.');</script>";
    }

    $mysqli->close();
}

// Fetch dogs (with search filter)
$mysqli = new mysqli("localhost", "root", "", "dogadoption");
$dogs = [];
$searchTerm = "";

if (!$mysqli->connect_error) {
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = $mysqli->real_escape_string(trim($_GET['search']));
        $result = $mysqli->query("SELECT dog_breed AS name, dog_image 
                                  FROM dogs 
                                  WHERE dog_breed LIKE '%$searchTerm%' 
                                  ORDER BY added_date DESC");
    } else {
        $result = $mysqli->query("SELECT dog_breed AS name, dog_image FROM dogs ORDER BY added_date DESC");
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dogs[] = $row;
        }
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dog Adoption Search</title>

    <style>
        /* your existing styles remain unchanged */
        .search-bar {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .search-bar input {
            padding: 8px;
            font-size: 16px;
            width: 300px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .search-bar button {
            padding: 8px 16px;
            background-color: #adb2d4;
            color: white;
            border: none;
            border-radius: 5px;
            margin-left: 5px;
            cursor: pointer;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            max-width: 2000px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            background-color: #adb2d4;
            line-height: 2.5;
            text-align: center;
            color: #333;
        }

        .dog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .dog-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 15px;
        }

        .dog-card img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .dog-card h2 {
            margin: 10px 0;
            color: #555;
        }

        .adopt-button {
            background-color: #adb2d4;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .form-section {
            display: none;
            background-color: #ffffff;
            margin: 40px auto;
            padding: 25px;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .form-section h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        form label {
            margin-top: 10px;
            font-weight: bold;
        }

        form input,
        form textarea {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-top: 5px;
            font-size: 16px;
        }

        form button {
            margin-top: 20px;
            padding: 12px;
            background-color: #adb2d4;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
        }

        footer {
            background-color: #adb2d4;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            color: #333;
            margin-top: 50px;
        }

        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 15px;
            border-radius: 6px;
            max-width: 600px;
            margin: 20px auto;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="dashboard">
        <h1>Welcome to the Dog Adoption Center 🐶</h1>

        <!-- Search Form -->
        <div class="search-bar">
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search by breed..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        <style>
            button {
                padding: 8px 16px;
                background-color: #adb2d4;
                /* Red color */

                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: bold;
            }

            .container {
                display: flex;
                justify-content: flex-end;
                padding: 10px;
            }
        </style>

        <div class="container">
            <form action="logout.php" method="post">
                <button type="submit">Logout</button>
            </form>
        </div>
        <div class="container">
            <a href="user_chatsupport.php">
                <button type="button">💬 Chat with Admin</button>
            </a>
        </div>

        <!-- Dog Cards -->
        <div class="dog-grid">
            <?php if (empty($dogs)): ?>
                <p style="text-align:center;">No dogs found matching your search.</p>
            <?php else: ?>
                <?php foreach ($dogs as $dog): ?>
                    <div class="dog-card">
                        <img src="<?php echo htmlspecialchars($dog['dog_image']); ?>" alt="Dog <?php echo htmlspecialchars($dog['name']); ?>">
                        <h2><?php echo htmlspecialchars($dog['name']); ?></h2>
                        <button class="adopt-button" data-dogname="<?php echo htmlspecialchars($dog['name']); ?>">Adopt</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Adoption Form -->
    <div class="form-section" id="formSection" style="display:none;">
        <button class="close-btn" onclick="document.getElementById('formSection').style.display='none'">✖</button>
        <h2>Adoption Form</h2>
        <form id="adoptionForm" method="post" action="">
            <label for="fullname">Full Name:</label>
            <input type="text" id="fullname" name="fullname" required>
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>
            <label for="phone">Phone Number:</label>
            <input type="tel" id="phone" name="phone" required>
            <label for="address">Home Address:</label>
            <textarea id="address" name="address" rows="3" required></textarea>
            <label for="dogname">Name of Dog to Adopt:</label>
            <input type="text" id="dogname" name="dogname" readonly required>
            <button type="submit">Submit Application</button>
        </form>
    </div>


    <script>
        const adoptButtons = document.querySelectorAll('.adopt-button');
        const formSection = document.getElementById('formSection');
        const dogNameInput = document.getElementById('dogname');

        adoptButtons.forEach(button => {
            button.addEventListener('click', () => {
                const dogName = button.getAttribute('data-dogname');
                dogNameInput.value = dogName;
                formSection.style.display = 'block';
                formSection.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
    <footer>
        <p>
            "Adopt love — it has four paws and a wagging tail." <br>
            "You can't buy happiness, but you can adopt it." <br>
            "Give a homeless dog a forever home — change their life and yours." <br>
            "Rescue dogs aren’t broken, they’ve just experienced more life." <br>
            "Adoption is not just about saving a life, it's about gaining a loyal friend." <br>
            "They may have had a rough start, but you can give them a beautiful future."
        </p>
    </footer>
</body>

</html>