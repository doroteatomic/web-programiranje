<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$errors  = [];
$message = $_GET['message'] ?? '';

// Handle add movie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title   = trim($_POST['title']        ?? '');
    $genre   = trim($_POST['genre']        ?? '');
    $country = trim($_POST['country']      ?? '');
    $year    = trim($_POST['release_year'] ?? '');
    $dur     = trim($_POST['duration']     ?? '');
    $rating  = trim($_POST['rating']       ?? '');

    if (empty($title))                        $errors[] = "Title is required.";
    if (empty($genre))                        $errors[] = "Genre is required.";
    if (empty($country))                      $errors[] = "Country is required.";
    if (!ctype_digit($year) || (int)$year < 1888 || (int)$year > 2100)
                                              $errors[] = "Year must be between 1888 and 2100.";
    if (empty($dur))                          $errors[] = "Duration is required.";
    if (!is_numeric($rating) || $rating < 0 || $rating > 10)
                                              $errors[] = "Rating must be a number between 0 and 10.";

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO movies (title, genre, country, release_year, duration, rating)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $yearInt    = (int)$year;
        $ratingFloat = (float)$rating;
        $stmt->bind_param("sssisi", $title, $genre, $country, $yearInt, $dur, $ratingFloat);
        if ($stmt->execute()) {
            $message = "Movie added successfully.";
        } else {
            $errors[] = "Failed to add movie.";
        }
    }
}

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $handle  = fopen($tmpPath, 'r');
        if ($handle) {
            fgetcsv($handle); // skip header row
            $stmt = $conn->prepare(
                "INSERT IGNORE INTO movies (title, genre, country, release_year, duration, rating)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $imported = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 6) continue;
                $t = trim($row[0]); $g = trim($row[1]); $c = trim($row[2]);
                $y = (int)$row[3];  $d = trim($row[4]); $r = (float)$row[5];
                if (empty($t) || $y < 1888 || $y > 2100) continue;
                $stmt->bind_param("sssisi", $t, $g, $c, $y, $d, $r);
                $stmt->execute();
                $imported++;
            }
            fclose($handle);
            $message = "CSV import complete — $imported rows processed.";
        }
    } else {
        $errors[] = "Please select a valid CSV file.";
    }
}

// Fetch all movies
$movies = $conn->query("SELECT * FROM movies ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <style>
        .section-card {
            background: rgba(255,255,255,0.75);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(255,105,180,0.10);
        }
        .section-card h2 { color: #c55487; margin-top: 0; }
        .add-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .add-form input, .add-form select { flex: 1; min-width: 130px; }
        .add-form button { flex: 0 0 auto; }
        .import-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .success-msg {
            background: #d4edda; color: #155724;
            border-radius: 10px; padding: 10px 15px; margin-bottom: 15px;
        }
        .btn-edit {
            padding: 6px 14px; border-radius: 20px; border: none;
            background: linear-gradient(135deg, #7fb8df, #67a8ff);
            color: white; cursor: pointer; font-family: inherit; font-size: 0.85rem;
        }
        .btn-danger {
            padding: 6px 14px; border-radius: 20px; border: none;
            background: linear-gradient(135deg, #df7f7f, #ff6767);
            color: white; cursor: pointer; font-family: inherit; font-size: 0.85rem;
        }
        td form { display: inline; }
    </style>
</head>
<body>

<header>
    <h1>Admin Dashboard</h1>
    <nav>
        <a href="index.php">Movies</a>
        <a href="gallery.php">Gallery</a>
        <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
    </nav>
</header>

<main>

    <?php if ($message): ?>
        <div class="success-msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="warning">
            <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ADD MOVIE -->
    <div class="section-card">
        <h2>Add New Movie</h2>
        <form method="POST" class="add-form">
            <input type="hidden" name="action" value="add">
            <input type="text"   name="title"        placeholder="Title *"    required>
            <input type="text"   name="genre"        placeholder="Genre *"    required>
            <input type="text"   name="country"      placeholder="Country *"  required>
            <input type="number" name="release_year" placeholder="Year *"     min="1888" max="2100" required>
            <input type="text"   name="duration"     placeholder="Duration *" required>
            <input type="number" name="rating"       placeholder="Rating (0-10)" step="0.1" min="0" max="10" required>
            <button type="submit">Add Movie</button>
        </form>
    </div>

    <!-- IMPORT CSV -->
    <div class="section-card">
        <h2>Import from CSV</h2>
        <p style="color:#888; font-size:0.9rem; margin:0 0 12px;">
            Expected columns: <code>title, genre, country, release_year, duration, rating</code>
        </p>
        <form method="POST" enctype="multipart/form-data" class="import-form">
            <input type="hidden" name="action" value="import">
            <input type="file" name="csv_file" accept=".csv" required style="flex:1;">
            <button type="submit">Import</button>
        </form>
    </div>

    <!-- MOVIE LIST -->
    <div class="section-card">
        <h2>All Movies (<?= count($movies) ?>)</h2>
        <div class="movies-table">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Country</th>
                        <th>Year</th>
                        <th>Duration</th>
                        <th>Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($movies as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['genre']) ?></td>
                        <td><?= htmlspecialchars($m['country']) ?></td>
                        <td><?= htmlspecialchars($m['release_year']) ?></td>
                        <td><?= htmlspecialchars($m['duration']) ?></td>
                        <td>⭐ <?= htmlspecialchars($m['rating']) ?></td>
                        <td>
                            <a href="edit_movie.php?id=<?= $m['id'] ?>">
                                <button type="button" class="btn-edit">Edit</button>
                            </a>
                            <form method="POST" action="delete_movie_admin.php"
                                  onsubmit="return confirm('Delete this movie permanently?')">
                                <input type="hidden" name="movie_id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<footer>
    <p>Web Programming LV4</p>
</footer>

</body>
</html>
