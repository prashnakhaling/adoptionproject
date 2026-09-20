<?php

include 'dataconnection.php';


// =====================================================
// DELETE DOG
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dog'])) {

  $dog_id = (int)($_POST['dog_id'] ?? 0);

  if ($dog_id > 0) {

    // Get current image
    $stmt = $conn->prepare(
      "SELECT dog_image FROM dogs WHERE dog_id = ?"
    );

    $stmt->bind_param("i", $dog_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $dog = $result->fetch_assoc();

    $stmt->close();


    // Delete database record
    $stmt = $conn->prepare(
      "DELETE FROM dogs WHERE dog_id = ?"
    );

    $stmt->bind_param("i", $dog_id);

    if ($stmt->execute()) {

      $stmt->close();

      // Delete image from folder
      if (!empty($dog['dog_image'])) {

        $oldImage = __DIR__ . '/../' . $dog['dog_image'];

        if (
          file_exists($oldImage) &&
          is_file($oldImage)
        ) {
          unlink($oldImage);
        }
      }

      header("Location: admindashboard.php");
      exit;
    } else {

      echo "Error deleting dog: " . $stmt->error;

      $stmt->close();
    }
  }
}



// =====================================================
// UPDATE DOG
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dog'])) {

  $dog_id = (int)($_POST['dog_id'] ?? 0);

  $breed = trim($_POST['breed'] ?? '');

  $age = (int)($_POST['age'] ?? 0);

  $description = trim($_POST['description'] ?? '');


  // Validation
  if (
    $dog_id <= 0 ||
    empty($breed) ||
    $age < 0 ||
    empty($description)
  ) {

    die("Please fill all required fields.");
  }


  // =================================================
  // GET CURRENT IMAGE
  // =================================================

  $stmt = $conn->prepare(
    "SELECT dog_image FROM dogs WHERE dog_id = ?"
  );

  $stmt->bind_param("i", $dog_id);

  $stmt->execute();

  $result = $stmt->get_result();

  if ($result->num_rows === 0) {

    $stmt->close();

    die("Dog not found.");
  }

  $dog = $result->fetch_assoc();

  $currentImage = $dog['dog_image'];

  $stmt->close();


  // Keep old image
  $imagePath = $currentImage;


  // =================================================
  // NEW IMAGE
  // =================================================

  if (
    isset($_FILES['image']) &&
    $_FILES['image']['error'] === UPLOAD_ERR_OK
  ) {

    $uploadDir = __DIR__ . '/../dogpic/';


    // Create folder if missing
    if (!is_dir($uploadDir)) {

      mkdir($uploadDir, 0777, true);
    }


    $originalName = $_FILES['image']['name'];

    $tmpName = $_FILES['image']['tmp_name'];


    $extension = strtolower(
      pathinfo(
        $originalName,
        PATHINFO_EXTENSION
      )
    );


    $allowedExtensions = [
      'jpg',
      'jpeg',
      'png',
      'gif',
      'webp',
      'jfif'
    ];


    if (!in_array($extension, $allowedExtensions)) {

      die("Invalid image format.");
    }


    // Unique filename
    $newFileName =
      uniqid('dog_', true)
      . '.'
      . $extension;


    $destination =
      $uploadDir . $newFileName;


    if (
      !move_uploaded_file(
        $tmpName,
        $destination
      )
    ) {

      die("Failed to upload image.");
    }


    // Database path
    $imagePath =
      'dogpic/' . $newFileName;


    // Delete old image
    if (!empty($currentImage)) {

      $oldImage =
        __DIR__ . '/../' . $currentImage;


      if (
        file_exists($oldImage) &&
        is_file($oldImage)
      ) {

        unlink($oldImage);
      }
    }
  }


  // =================================================
  // UPDATE DATABASE
  // =================================================

  $stmt = $conn->prepare("
        UPDATE dogs
        SET
            dog_breed = ?,
            age = ?,
            description = ?,
            dog_image = ?
        WHERE dog_id = ?
    ");


  $stmt->bind_param(
    "sissi",
    $breed,
    $age,
    $description,
    $imagePath,
    $dog_id
  );


  if ($stmt->execute()) {

    $stmt->close();

    header("Location: admindashboard.php");

    exit;
  } else {

    echo "Error updating dog: " . $stmt->error;

    $stmt->close();
  }
}



// =====================================================
// IMAGE PATH FUNCTION
// =====================================================

function getValidImagePath($imagePath)
{

  $imagePath = trim($imagePath);


  if (
    !empty($imagePath) &&
    file_exists(__DIR__ . '/' . $imagePath)
  ) {

    return $imagePath;
  }


  return 'placeholder.jpg';
}



// =====================================================
// TOTAL DOG COUNT
// =====================================================

$totalDogsResult = $conn->query(
  "SELECT COUNT(*) AS total FROM dogs"
);


$totalDogs = 0;


if (
  $totalDogsResult &&
  $row = $totalDogsResult->fetch_assoc()
) {

  $totalDogs = (int)$row['total'];
}



// =====================================================
// GET DOGS
// =====================================================

$dogsResult = $conn->query("
    SELECT
        dog_id,
        dog_breed,
        age,
        description,
        dog_image,
        added_date
    FROM dogs
    ORDER BY added_date DESC
");

?>


<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

  <title>
    Happy Tails - Admin Dashboard
  </title>


  <style>
    /* =====================================================
   RESET
===================================================== */

    * {
      box-sizing: border-box;
    }


    /* =====================================================
   BODY
===================================================== */

    body {

      font-family: 'Segoe UI', sans-serif;

      margin: 0;

      background-color: #f9f9f9;

      color: #333;

    }


    /* =====================================================
   SIDEBAR
===================================================== */

    .sidebar {

      width: 220px;

      background: #adb2d4;

      color: white;

      height: 100vh;

      position: fixed;

      left: 0;

      top: 0;

      padding-top: 20px;

    }


    .sidebar h2 {

      text-align: center;

      margin-bottom: 25px;

    }


    .sidebar a {

      display: block;

      color: white;

      padding: 12px 20px;

      text-decoration: none;

    }


    .sidebar a:hover {

      background: #8f96bd;

    }


    /* =====================================================
   MAIN
===================================================== */

    .main {

      margin-left: 220px;

      padding: 25px;

    }


    /* =====================================================
   DASHBOARD CARDS
===================================================== */

    .cards {

      display: flex;

      gap: 20px;

      margin-bottom: 30px;

    }


    .card {

      background: white;

      padding: 20px;

      border-radius: 8px;

      box-shadow:
        0 0 5px rgba(0, 0, 0, 0.1);

      flex: 1;

      text-align: center;

    }


    .card h3 {

      margin-top: 0;

    }


    .card p {

      font-size: 24px;

      font-weight: bold;

    }


    /* =====================================================
   DATE / TIME
===================================================== */

    .datetime-box {

      position: absolute;

      top: 20px;

      right: 20px;

      background: #adb2d4;

      color: white;

      padding: 12px 20px;

      border-radius: 10px;

      text-align: center;

    }


    #clock {

      font-size: 25px;

      font-weight: bold;

    }


    #calendar {

      font-size: 14px;

      margin-top: 5px;

    }


    /* =====================================================
   TABLE CONTAINER
===================================================== */

    .table-container {

      width: 100%;

      overflow-x: auto;

    }


    /* =====================================================
   TABLE
===================================================== */

    table {

      width: 100%;

      min-width: 900px;

      border-collapse: collapse;

      background: white;

      border-radius: 8px;

      overflow: hidden;

      box-shadow:
        0 0 5px rgba(0, 0, 0, 0.1);

    }


    th,
    td {

      padding: 12px;

      text-align: left;

      border-bottom: 1px solid #ddd;

      vertical-align: middle;

    }


    th {

      background-color: #f0f0f0;

    }


    td.description {

      max-width: 300px;

      line-height: 1.4;

    }


    /* =====================================================
   DOG IMAGE
===================================================== */

    .dog-image {

      width: 60px;

      height: 60px;

      object-fit: cover;

      border-radius: 6px;

    }


    /* =====================================================
   ACTION BUTTON CONTAINER
===================================================== */

    .action-buttons {

      display: flex;

      flex-direction: row;

      align-items: center;

      gap: 8px;

    }


    /* =====================================================
   EDIT BUTTON
===================================================== */

    .edit-btn {

      display: inline-block;

      width: auto !important;

      min-width: 60px;

      padding: 7px 12px;

      background-color: #858ec6;

      color: white;

      border: none;

      border-radius: 4px;

      cursor: pointer;

      font-size: 14px;

    }


    .edit-btn:hover {

      background-color: #6f78b5;

    }


    /* =====================================================
   DELETE BUTTON
===================================================== */

    .delete-btn {

      display: inline-block;

      width: auto !important;

      min-width: 65px;

      padding: 7px 12px;

      background-color: red;

      color: white;

      border: none;

      border-radius: 4px;

      cursor: pointer;

      font-size: 14px;

    }


    .delete-btn:hover {

      background-color: darkred;

    }


    /* =====================================================
   MODAL BACKGROUND
===================================================== */

    .modal {

      display: none;

      position: fixed;

      z-index: 9999;

      left: 0;

      top: 0;

      width: 100%;

      height: 100%;

      overflow-y: auto;

      background-color:
        rgba(0, 0, 0, 0.55);

    }


    /* =====================================================
   MODAL BOX
===================================================== */

    .modal-content {

      position: relative;

      width: 90%;

      max-width: 460px;

      margin: 50px auto;

      padding: 25px;

      background-color: white;

      border-radius: 10px;

      box-shadow:
        0 5px 25px rgba(0, 0, 0, 0.3);

    }


    /* =====================================================
   MODAL TITLE
===================================================== */

    .modal-content h2 {

      margin-top: 0;

      margin-bottom: 20px;

    }


    /* =====================================================
   CLOSE BUTTON
===================================================== */

    .closeBtn {

      position: absolute;

      top: 10px;

      right: 15px;

      font-size: 28px;

      line-height: 28px;

      color: #555;

      cursor: pointer;

    }


    .closeBtn:hover {

      color: red;

    }


    /* =====================================================
   MODAL FORM
===================================================== */

    .modal-form {

      width: 100%;

    }


    /* =====================================================
   FORM LABEL
===================================================== */

    .modal-form label {

      display: block;

      width: 100%;

      margin-bottom: 5px;

      font-weight: 500;

    }


    /* =====================================================
   TEXT INPUT
===================================================== */

    .modal-form input[type="text"],

    .modal-form input[type="number"],

    .modal-form input[type="file"],

    .modal-form textarea {

      display: block;

      width: 100%;

      max-width: 100%;

      padding: 10px;

      margin: 0 0 15px 0;

      border: 1px solid #ccc;

      border-radius: 5px;

      font-family: inherit;

      font-size: 14px;

    }


    /* =====================================================
   TEXTAREA
===================================================== */

    .modal-form textarea {

      min-height: 100px;

      resize: vertical;

    }


    /* =====================================================
   SAVE BUTTON
===================================================== */

    .modal-form .save-btn {

      display: block;

      width: 100%;

      height: 45px;

      padding: 10px 15px;

      margin: 15px 0 0 0;

      background-color: #858ec6;

      color: white;

      border: none;

      border-radius: 5px;

      cursor: pointer;

      font-size: 16px;

      font-weight: 600;

      text-align: center;

      line-height: 25px;

      visibility: visible;

      opacity: 1;

    }


    .modal-form .save-btn:hover {

      background-color: #6f78b5;

    }


    /* =====================================================
   CURRENT IMAGE
===================================================== */

    .current-image {

      display: block;

      width: 100px;

      height: 100px;

      object-fit: cover;

      border-radius: 8px;

      margin: 5px 0 15px 0;

      border: 1px solid #ddd;

    }


    /* =====================================================
   FOOTER
===================================================== */

    footer {

      text-align: center;

      margin-top: 40px;

      padding: 20px;

      font-size: 14px;

      color: #666;

    }


    /* =====================================================
   MOBILE
===================================================== */

    @media (max-width: 768px) {

      .sidebar {

        width: 180px;

      }


      .main {

        margin-left: 180px;

      }


      .cards {

        flex-direction: column;

      }


      .datetime-box {

        position: static;

        margin-bottom: 20px;

        width: fit-content;

      }

    }
  </style>

</head>


<body>


  <!-- =====================================================
     ADD DOG MODAL
===================================================== -->

  <div
    id="dogModal"
    class="modal">

    <div class="modal-content">


      <span
        class="closeBtn"
        data-modal="dogModal">
        &times;
      </span>


      <h2>
        Add New Dog
      </h2>


      <form
        class="modal-form"
        action="doginsert.php"
        method="POST"
        enctype="multipart/form-data">


        <label>
          Breed:
        </label>

        <input
          type="text"
          name="breed"
          required>


        <label>
          Age:
        </label>

        <input
          type="number"
          name="age"
          min="0"
          required>


        <label>
          Description:
        </label>

        <textarea
          name="description"
          placeholder="Enter description about the dog..."
          required></textarea>


        <label>
          Image:
        </label>

        <input
          type="file"
          name="image"
          accept="image/*"
          required>


        <button
          type="submit"
          class="save-btn">
          Add Dog
        </button>


      </form>

    </div>

  </div>



  <!-- =====================================================
     EDIT DOG MODAL
===================================================== -->

  <div
    id="editDogModal"
    class="modal">

    <div class="modal-content">


      <span
        class="closeBtn"
        data-modal="editDogModal">
        &times;
      </span>


      <h2>
        Edit Dog
      </h2>


      <form
        class="modal-form"
        method="POST"
        enctype="multipart/form-data">


        <!-- Tell PHP this is UPDATE -->

        <input
          type="hidden"
          name="update_dog"
          value="1">


        <!-- DOG ID -->

        <input
          type="hidden"
          name="dog_id"
          id="edit_dog_id">


        <!-- BREED -->

        <label>
          Breed:
        </label>

        <input
          type="text"
          name="breed"
          id="edit_breed"
          required>


        <!-- AGE -->

        <label>
          Age:
        </label>

        <input
          type="number"
          name="age"
          id="edit_age"
          min="0"
          required>


        <!-- DESCRIPTION -->

        <label>
          Description:
        </label>

        <textarea
          name="description"
          id="edit_description"
          placeholder="Enter description..."
          required></textarea>


        <!-- CURRENT IMAGE -->

        <label>
          Current Image:
        </label>


        <img
          id="edit_current_image"
          class="current-image"
          src=""
          alt="Current Dog Image">


        <!-- NEW IMAGE -->

        <label>
          Change Image (optional):
        </label>

        <input
          type="file"
          name="image"
          accept="image/*">


        <!-- SAVE BUTTON -->

        <button
          type="submit"
          class="save-btn">
          Save Changes
        </button>


      </form>

    </div>

  </div>



  <!-- =====================================================
     SIDEBAR
===================================================== -->

  <div class="sidebar">


    <h2>
      Dog Admin
    </h2>


    <a href="#">
      Dashboard
    </a>


    <a
      href="#"
      id="addDogBtn">
      Add Dog
    </a>


    <a
      href="#"
      id="pendingAppBtn">
      Applications
    </a>


    <a href="admin_chatsupport.php">
      Chat
    </a>


    <a href="#">
      Settings
    </a>


  </div>



  <!-- =====================================================
     MAIN CONTENT
===================================================== -->

  <div class="main">


    <h1>
      Welcome, Admin
    </h1>


    <!-- DASHBOARD CARDS -->

    <div class="cards">


      <div class="card">

        <h3>
          Total Dogs
        </h3>

        <p>
          <?= $totalDogs ?>
        </p>

      </div>


      <div class="card">

        <h3>
          Pending Applications
        </h3>

        <p>
          12
        </p>

      </div>


      <div class="card">

        <h3>
          Completed Adoptions
        </h3>

        <p>
          89
        </p>

      </div>


    </div>



    <!-- DATE AND TIME -->

    <div class="datetime-box">

      <div id="clock"></div>

      <div id="calendar"></div>

    </div>



    <h2>
      Dog Listings
    </h2>



    <!-- TABLE -->

    <div class="table-container">

      <table>


        <thead>

          <tr>

            <th>
              Breed
            </th>

            <th>
              Image
            </th>

            <th>
              Age
            </th>

            <th>
              Description
            </th>

            <th>
              Added Date
            </th>

            <th>
              Action
            </th>

          </tr>

        </thead>



        <tbody>


          <?php while ($row = $dogsResult->fetch_assoc()):

            $imagePath =
              getValidImagePath(
                $row['dog_image']
              );

          ?>


            <tr>


              <!-- BREED -->

              <td>

                <?= htmlspecialchars(
                  $row['dog_breed'] ?? ''
                ) ?>

              </td>



              <!-- IMAGE -->

              <td>

                <img
                  src="<?= htmlspecialchars($imagePath) ?>"
                  class="dog-image"
                  alt="Dog Image">

              </td>



              <!-- AGE -->

              <td>

                <?= (int)$row['age'] ?>

              </td>



              <!-- DESCRIPTION -->

              <td class="description">

                <?= htmlspecialchars(
                  $row['description']
                    ?? 'No description available'
                ) ?>

              </td>



              <!-- DATE -->

              <td>

                <?= htmlspecialchars(
                  $row['added_date'] ?? ''
                ) ?>

              </td>



              <!-- ACTION -->

              <td>


                <div class="action-buttons">


                  <!-- EDIT -->

                  <button
                    type="button"
                    class="edit-btn"
                    onclick='openEditModal(
                                    <?= json_encode((int)$row["dog_id"]) ?>,
                                    <?= json_encode($row["dog_breed"] ?? "") ?>,
                                    <?= json_encode((int)$row["age"]) ?>,
                                    <?= json_encode($row["description"] ?? "") ?>,
                                    <?= json_encode($imagePath) ?>
                                )'>

                    Edit

                  </button>



                  <!-- DELETE -->

                  <form
                    method="POST"
                    style="margin:0;"
                    onsubmit="return confirm('Are you sure you want to delete this dog?');">

                    <input
                      type="hidden"
                      name="dog_id"
                      value="<?= (int)$row['dog_id'] ?>">


                    <input
                      type="hidden"
                      name="delete_dog"
                      value="1">


                    <button
                      type="submit"
                      class="delete-btn">

                      Delete

                    </button>

                  </form>


                </div>


              </td>


            </tr>


          <?php endwhile; ?>


        </tbody>

      </table>

    </div>



    <!-- FOOTER -->

    <footer>

      <p>

        Adopt love — it has four paws and a wagging tail.
        <br>

        You can't buy happiness, but you can adopt it.
        <br>

        Give a homeless dog a forever home.

      </p>

    </footer>


  </div>



  <!-- =====================================================
     APPLICATION MODAL
===================================================== -->

  <div
    id="applicationsModal"
    class="modal">

    <div class="modal-content">


      <span
        class="closeBtn"
        data-modal="applicationsModal">
        &times;
      </span>


      <h2>
        Applications
      </h2>


      <p>
        List of adoption applications goes here...
      </p>


    </div>

  </div>



  <script>
    /* =====================================================
   ADD DOG MODAL
===================================================== */

    document
      .getElementById("addDogBtn")
      .addEventListener("click", function(e) {

        e.preventDefault();

        document
          .getElementById("dogModal")
          .style.display = "block";

      });



    /* =====================================================
       APPLICATION MODAL
    ===================================================== */

    document
      .getElementById("pendingAppBtn")
      .addEventListener("click", function(e) {

        e.preventDefault();

        document
          .getElementById("applicationsModal")
          .style.display = "block";

      });



    /* =====================================================
       EDIT MODAL
    ===================================================== */

    function openEditModal(
      dogId,
      breed,
      age,
      description,
      image
    ) {


      document
        .getElementById("edit_dog_id")
        .value = dogId;


      document
        .getElementById("edit_breed")
        .value = breed;


      document
        .getElementById("edit_age")
        .value = age;


      document
        .getElementById("edit_description")
        .value = description;


      document
        .getElementById("edit_current_image")
        .src = image;


      document
        .getElementById("editDogModal")
        .style.display = "block";

    }



    /* =====================================================
       CLOSE BUTTONS
    ===================================================== */

    document
      .querySelectorAll(".closeBtn")
      .forEach(function(button) {

        button.addEventListener(
          "click",
          function() {

            const modalId =
              this.getAttribute(
                "data-modal"
              );


            document
              .getElementById(modalId)
              .style.display = "none";

          }
        );

      });



    /* =====================================================
       CLICK OUTSIDE MODAL
    ===================================================== */

    window.addEventListener(
      "click",
      function(event) {

        document
          .querySelectorAll(".modal")
          .forEach(function(modal) {

            if (event.target === modal) {

              modal.style.display = "none";

            }

          });

      }
    );



    /* =====================================================
       CLOCK
    ===================================================== */

    function updateDateTime() {


      const now = new Date();


      const time =
        now.toLocaleTimeString(
          "en-US", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit"
          }
        );


      const date =
        now.toLocaleDateString(
          "en-US", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric"
          }
        );


      document
        .getElementById("clock")
        .textContent = time;


      document
        .getElementById("calendar")
        .textContent = date;

    }


    setInterval(
      updateDateTime,
      1000
    );


    updateDateTime();
  </script>


</body>

</html>


<?php

$dogsResult->free();

$conn->close();

?>