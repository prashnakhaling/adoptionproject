<?php
// Connection
include 'dataconnection.php';

// Function to return image path (assuming full path is already stored in DB)
function getValidImagePath($imagePath)
{
  $imagePath = trim($imagePath);
  return (!empty($imagePath) && file_exists(__DIR__ . '/' . $imagePath)) ? $imagePath : 'placeholder.jpg';
}

// Total dog count
$totalDogsResult = $conn->query("SELECT COUNT(*) AS total FROM dogs");
$totalDogs = ($totalDogsResult && $row = $totalDogsResult->fetch_assoc()) ? (int)$row['total'] : 0;

// Get dog data
$dogsResult = $conn->query("SELECT dog_id, dog_breed, age, dog_image, added_date FROM dogs ORDER BY added_date DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Happy Tails - Admin Dashboard</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background-color: #f9f9f9;
    }

    .sidebar {
      width: 220px;
      background: #adb2d4;
      color: white;
      height: 100vh;
      position: fixed;
      padding-top: 20px;
    }

    .sidebar h2 {
      text-align: center;
    }

    .sidebar a {
      display: block;
      color: white;
      padding: 12px 20px;
      text-decoration: none;
    }

    .sidebar a:hover {
      background: #adb2d4;
    }


    .main {
      margin-left: 240px;
      padding: 20px;
    }

    .cards {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
    }

    .card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
      flex: 1;
      text-align: center;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #f0f0f0;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      padding: 20px;
      font-size: 14px;
      color: #666;
    }

    img {
      border-radius: 6px;
      object-fit: cover;
      width: 60px;
      height: 60px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 100%;
      max-width: 400px;
      position: relative;
    }

    .closeBtn {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      cursor: pointer;
    }

    input,
    button {
      width: 90%;
      padding: 10px;
      margin-top: 5px;
      margin-bottom: 15px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    button[type="submit"],
    #addDogBtn {
      background-color: #858ec6;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover,
    #addDogBtn:hover background-color: :rgb(109, 3, 72);

    .delete-btn {
      background-color: red;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }

    .delete-btn:hover {
      background-color: darkred;
    }

    .datetime-box {
      background: #8b92c0;



      color: white;
      padding: 15px;
      border-radius: 10px;
      width: 250px;
      text-align: center;
      margin-bottom: 20px;
    }

    #clock {
      font-size: 28px;
      font-weight: bold;
    }

    #calendar {
      font-size: 15px;
      margin-top: 5px;
    }

    .datetime-box {
      position: absolute;
      top: 20px;
      right: 20px;
      background: #adb2d4;


      color: white;
      padding: 15px 25px;
      border-radius: 10px;
      text-align: center;
    }
  </style>
</head>

<body>

  <!-- Modal Form -->
  <div id="dogModal" class="modal">
    <div class="modal-content">
      <span class="closeBtn">times;</span>
      <h2>Add New Dog</h2>
      <form action="doginsert.php" method="POST" enctype="multipart/form-data">
        <label>Breed:<br><input type="text" name="breed" required></label><br>
        <label>Age:<br><input type="number" name="age" required></label><br>
        <label>Image:<br><input type="file" name="image" accept="image/*" required></label><br>
        <button type="submit">Submit</button>
      </form>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>Dog Admin</h2>
    <a href="#">Dashboard</a>
    <a href="#" id="addDogBtn">Add Dog</a>
    <a href="#" id="pendingAppBtn">Applications</a>
    <a href="admin_chatsupport.php">Chat</a>
    <a href="#">Settings</a>
  </div>

  <!-- Main Content -->
  <div class="main">
    <h1>Welcome, Admin</h1>
    <div class="cards">
      <div class="card">
        <h3>Total Dogs</h3>
        <p><?= $totalDogs ?></p>
      </div>
      <div class="card">
        <h3>Pending Applications</h3>
        <p>12</p>
      </div>
      <div class="card">
        <h3>Completed Adoptions</h3>
        <p>89</p>
      </div>
    </div>
    <div class="datetime-box">
      <div id="clock"></div>
      <div id="calendar"></div>
    </div>
    <h2>Dog Listings</h2>
    <table>
      <thead>
        <tr>
          <th>Breed</th>
          <th>Image</th>
          <th>Age</th>
          <th>Added Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $dogsResult->fetch_assoc()):
          $imagePath = getValidImagePath($row['dog_image']); ?>
          <tr>
            <td><?= htmlspecialchars($row['dog_breed']) ?></td>
            <td><img src="<?= htmlspecialchars($imagePath) ?>" alt="Dog Image"></td>
            <td><?= (int)$row['age'] ?></td>
            <td><?= htmlspecialchars($row['added_date']) ?></td>
            <td>
              <form method="POST" action="deletedog.php" onsubmit="return confirm('Are you sure you want to delete this dog?');">
                <input type="hidden" name="dog_id" value="<?= (int)$row['dog_id'] ?>">

                <button type="submit" style="background-color: red;" class="delete-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <footer>
      <p>Adopt love — it has four paws and a wagging tail.<br>
        You can't buy happiness, but you can adopt it.<br>
        Give a homeless dog a forever home.</p>
    </footer>
  </div>

  <script>
    document.getElementById('addDogBtn').onclick = () => document.getElementById('dogModal').style.display = 'block';
    document.querySelector('.closeBtn').onclick = () => document.getElementById('dogModal').style.display = 'none';
    window.onclick = e => {
      if (e.target === document.getElementById('dogModal')) {
        document.getElementById('dogModal').style.display = 'none';
      }
    };

    // Open Add Dog Modal
    document.getElementById('addDogBtn').onclick = () => {
      document.getElementById('dogModal').style.display = 'block';
    };

    // Close buttons
    document.querySelectorAll('.closeBtn').forEach(btn => {
      btn.onclick = () => {
        const modalId = btn.getAttribute('data-modal') || 'dogModal';
        document.getElementById(modalId).style.display = 'none';
      };
    });

    // Open Applications Modal
    document.getElementById('pendingAppBtn').onclick = () => {
      document.getElementById('applicationsModal').style.display = 'block';
    };



    // Close modal when clicking outside
    window.onclick = function(e) {
      document.querySelectorAll('.modal').forEach(modal => {
        if (e.target === modal) modal.style.display = 'none';
      });
      // Live Clock & Calendar
      function updateDateTime() {
        const now = new Date();

        const time = now.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });

        const date = now.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });

        document.getElementById('clock').innerHTML = time;
        document.getElementById('calendar').innerHTML = date;
      }

      setInterval(updateDateTime, 1000);
      updateDateTime();
    };
  </script>

</body>
<!-- Applications Modal -->
<div id="applicationsModal" class="modal">
  <div class="modal-content">
    <span class="closeBtn" data-modal="applicationsModal">&times;</span>
    <h2>Applications</h2>
    <p>List of adoption applications goes here...</p>
  </div>
</div>



</html>

<?php
$dogsResult->free();
$conn->close();
?>