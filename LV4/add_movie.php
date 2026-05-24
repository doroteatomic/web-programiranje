<?php
include 'includes/auth.php';
include 'includes/db.php';

$userId    = $_SESSION['user_id'];
$movieId   = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === '1';

if (!$movieId) {
    header("Location: index.php");
    exit();
}

// Check for duplicate
$check = $conn->prepare("SELECT id FROM user_movies WHERE user_id = ? AND movie_id = ?");
$check->bind_param("ii", $userId, $movieId);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?message=Movie+is+already+in+your+videoteka&msg_type=warning");
    exit();
}

// Get movie info
$stmt = $conn->prepare("SELECT title, rating FROM movies WHERE id = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    header("Location: index.php");
    exit();
}

// If low rating and not yet confirmed — show warning page
if ($movie['rating'] < 5.0 && !$confirmed) {
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Rating Warning</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <style>
        .confirm-box {
            max-width: 500px;
            margin: 60px auto;
            background: rgba(255,255,255,0.8);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.12);
            text-align: center;
        }
        .confirm-box h2 { color: #c0392b; margin-bottom: 10px; }
        .confirm-box .movie-title { font-weight: 600; color: #333; font-size: 1.1rem; }
        .btn-group { display: flex; justify-content: center; gap: 15px; margin-top: 25px; }
        .btn-cancel {
            padding: 10px 22px; border-radius: 25px; border: 2px solid #df7faf;
            background: white; color: #df7faf; font-family: inherit;
            font-weight: 500; cursor: pointer; transition: 0.3s; text-decoration: none;
            display: inline-flex; align-items: center;
        }
        .btn-cancel:hover { background: #fff0f6; }
    </style>
</head>
<body>

<header>
    <h1>Movie Database</h1>
    <nav>
        <a href="index.php">Movies</a>
        <a href="movies.php">Cart</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="confirm-box">
        <div class="warning">
            <h2>&#9888; Low Rating Warning</h2>
            <p>
                <span class="movie-title"><?= htmlspecialchars($movie['title']) ?></span>
                has an average rating of <strong><?= $movie['rating'] ?>/10</strong>, which is below 5.0.
            </p>
            <p>Are you sure you want to add this movie to your videoteka?</p>
        </div>

        <div class="btn-group">
            <form method="POST" action="add_movie.php">
                <input type="hidden" name="movie_id"  value="<?= $movieId ?>">
                <input type="hidden" name="confirmed" value="1">
                <button type="submit">Yes, add anyway</button>
            </form>
            <a href="index.php" class="btn-cancel">Cancel</a>
        </div>
    </div>
</main>

<footer><p>Web Programming LV4</p></footer>

</body>
</html>
<?php
    exit();
}

// Insert into user_movies
$insert = $conn->prepare("INSERT INTO user_movies (user_id, movie_id) VALUES (?, ?)");
$insert->bind_param("ii", $userId, $movieId);
$insert->execute();

header("Location: index.php?message=Movie+added+to+your+cart&msg_type=added");
exit();
?>
