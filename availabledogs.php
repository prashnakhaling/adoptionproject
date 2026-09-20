<?php
require_once __DIR__ . '/admin/dataconnection.php';
require __DIR__ . '/includes/header.php';


// Fetch all dogs, newest first
$sql = "SELECT dog_id, dog_breed, dog_image, age, added_date FROM dogs ORDER BY added_date DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">

    <title>Dog Adoption - All Dogs</title>
    <style>
        :root {
            --primary: #ff8a3d;
            --primary-dark: #e56f24;
            --danger: #e04b4b;
            --danger-dark: #c73c3c;
            --edit: #3d7bff;
            --edit-dark: #2c5fd6;
            --copy: #4caf7d;
            --copy-dark: #3a8f64;
            --card-bg: #ffffff;
            --page-bg: #f4f6f8;
            --text-dark: #2b2b2b;
            --text-light: #6b6b6b;
            --border: #e6e6e6;
            --radius: 12px;
        }

        /* * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--page-bg);
            color: var(--text-dark);
            padding: 30px 20px 60px;
        } */

        .page-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-header h1 {
            margin: 0 0 6px;
            font-size: 28px;
            color: var(--text-dark);
            padding-top: 20px;
        }

        .page-header p {
            margin: 0;
            color: var(--text-light);
            font-size: 14px;
        }

        /* Grid: exactly 3 dogs per row on desktop */
        .dog-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        @media (max-width: 900px) {
            .dog-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .dog-grid {
                grid-template-columns: 1fr;
            }
        }

        .dog-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: flex;
            flex-direction: column;
        }

        .dog-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        /* Fixed, uniform image size for every dog */
        .dog-image-wrap {
            width: 100%;
            height: 220px;
            background: #eee;
            overflow: hidden;
        }

        .dog-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .dog-info {
            padding: 16px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .dog-breed {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: capitalize;
            color: var(--text-dark);
        }

        .dog-detail-row {
            font-size: 13.5px;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
        }

        .dog-detail-row span.label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .dog-actions {
            display: flex;
            border-top: 1px solid var(--border);
        }

        .dog-actions button,
        .dog-actions a {
            flex: 1;
            border: none;
            padding: 10px 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: #fff;
            transition: background 0.15s ease;
        }

        .btn-edit {
            background: var(--edit);
        }

        .btn-edit:hover {
            background: var(--edit-dark);
        }

        .btn-copy {
            background: var(--copy);
        }

        .btn-copy:hover {
            background: var(--copy-dark);
        }

        .btn-delete {
            background: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger-dark);
        }

        .empty-state {
            text-align: center;
            color: var(--text-light);
            margin-top: 60px;
            font-size: 16px;
        }

        /* Toast notification for copy/delete feedback */
        #toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #2b2b2b;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
            z-index: 999;
        }

        #toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>

<body>

    <div class="page-header">
        <h1>Dog Adoption Gallery</h1>
        <!-- <p>All dogs currently listed in the database</p> -->
    </div>

    <div class="dog-grid" id="dogGrid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($dog = mysqli_fetch_assoc($result)): ?>
                <?php
                $dogId    = htmlspecialchars($dog['dog_id']);
                $breed    = htmlspecialchars($dog['dog_breed']);
                $image    = htmlspecialchars($dog['dog_image']);
                $age      = htmlspecialchars($dog['age']);
                // $added    = htmlspecialchars($dog['added_date']);
                ?>
                <div class="dog-card" id="dog-card-<?php echo $dogId; ?>">
                    <div class="dog-image-wrap">
                        <img src="<?php echo $image; ?>" alt="<?php echo $breed; ?>" loading="lazy"
                            onerror="this.src='https://via.placeholder.com/400x300?text=No+Image';">
                    </div>
                    <div class="dog-info">
                        <h3 class="dog-breed"><?php echo $breed; ?></h3>
                        <div class="dog-detail-row"><span class="label">ID:</span> <span><?php echo $dogId; ?></span></div>
                        <div class="dog-detail-row"><span class="label">Age:</span> <span><?php echo $age; ?> yrs</span></div>

                    </div>
                    <div class="dog-actions">
                        <a href="edit.php?id=<?php echo $dogId; ?>" class="btn-edit">Edit</a>
                        <button type="button" class="btn-copy"
                            data-breed="<?php echo $breed; ?>"
                            data-id="<?php echo $dogId; ?>"
                            data-age="<?php echo $age; ?>"

                            onclick="copyDogDetails(this)">Copy</button>
                        <button type="button" class="btn-delete" onclick="deleteDog(<?php echo $dogId; ?>)">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-state">No dogs found in the database.</p>
        <?php endif; ?>
    </div>

    <div id="toast"></div>

    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2200);
        }

        // Copy a dog's details to the clipboard
        function copyDogDetails(btn) {
            const text =
                "Breed: " + btn.dataset.breed + "\n" +
                "ID: " + btn.dataset.id + "\n" +
                "Age: " + btn.dataset.age + " yrs\n" +
                "Added: " + btn.dataset.added;

            navigator.clipboard.writeText(text)
                .then(() => showToast("Dog details copied to clipboard"))
                .catch(() => showToast("Failed to copy details"));
        }

        // Delete a dog via AJAX call to delete.php
        function deleteDog(dogId) {
            if (!confirm("Are you sure you want to delete this dog record?")) {
                return;
            }

            fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'dog_id=' + encodeURIComponent(dogId)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const card = document.getElementById('dog-card-' + dogId);
                        if (card) card.remove();
                        showToast("Dog record deleted");
                    } else {
                        showToast("Delete failed: " + (data.message || "Unknown error"));
                    }
                })
                .catch(() => showToast("Delete request failed"));
        }
    </script>

</body>

</html>