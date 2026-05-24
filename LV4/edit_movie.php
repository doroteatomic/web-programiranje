<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];

// ── Shared validation ────────────────────────────────────────────────────────
function validateMovieFields(array $post): array {
    $errors      = [];
    $currentYear = (int)date('Y');

    $title   = trim($post['title']        ?? '');
    $genre   = trim($post['genre']        ?? '');
    $country = trim($post['country']      ?? '');
    $year    = trim($post['release_year'] ?? '');
    $dur     = trim($post['duration']     ?? '');
    $rating  = trim($post['rating']       ?? '');

    // Title
    if ($title === '') {
        $errors[] = "Title is required.";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title must not exceed 255 characters.";
    }

    // Genre
    if ($genre === '') {
        $errors[] = "Genre is required.";
    } elseif (!preg_match('/^[\p{L}\s\-\/,]+$/u', $genre)) {
        $errors[] = "Genre may only contain letters, spaces, hyphens, slashes and commas.";
    }

    // Country
    if ($country === '') {
        $errors[] = "Country is required.";
    } elseif (!preg_match('/^[\p{L}\s\-]+$/u', $country)) {
        $errors[] = "Country may only contain letters, spaces and hyphens.";
    }

    // Year — 4-digit, 1888 to current year + 2
    if ($year === '') {
        $errors[] = "Year is required.";
    } elseif (!preg_match('/^\d{4}$/', $year)) {
        $errors[] = "Year must be a 4-digit number (e.g. 2023).";
    } elseif ((int)$year < 1888 || (int)$year > $currentYear + 2) {
        $errors[] = "Year must be between 1888 and " . ($currentYear + 2) . ".";
    }

    // Duration — 1 to 600 minutes
    $durRaw = preg_replace('/\s*min\s*$/i', '', $dur);
    if ($dur === '') {
        $errors[] = "Duration is required.";
    } elseif (!ctype_digit($durRaw) || (int)$durRaw < 1 || (int)$durRaw > 600) {
        $errors[] = "Duration must be a whole number of minutes between 1 and 600 (e.g. 120).";
    }

    // Rating
    if ($rating === '') {
        $errors[] = "Rating is required.";
    } elseif (!is_numeric($rating) || (float)$rating < 0 || (float)$rating > 10) {
        $errors[] = "Rating must be a number between 0.0 and 10.0.";
    }

    return $errors;
}

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateMovieFields($_POST);

    if (empty($errors)) {
        $title   = trim($_POST['title']);
        $genre   = trim($_POST['genre']);
        $country = trim($_POST['country']);
        $yearInt = (int)trim($_POST['release_year']);
        $durNum  = (int)preg_replace('/\s*min\s*$/i', '', trim($_POST['duration']));
        $dur     = $durNum . ' min';
        $ratingF = (float)trim($_POST['rating']);

        $stmt = $conn->prepare(
            "UPDATE movies
             SET title=?, genre=?, country=?, release_year=?, duration=?, rating=?
             WHERE id=?"
        );
        $stmt->bind_param("sssissi", $title, $genre, $country, $yearInt, $dur, $ratingF, $id);
        $stmt->execute();
        header("Location: dashboard.php?message=Movie+updated+successfully");
        exit();
    }

    // Sticky: keep POST values on error
    $movie = [
        'id'           => $id,
        'title'        => $_POST['title']        ?? '',
        'genre'        => $_POST['genre']        ?? '',
        'country'      => $_POST['country']      ?? '',
        'release_year' => $_POST['release_year'] ?? '',
        'duration'     => $_POST['duration']     ?? '',
        'rating'       => $_POST['rating']       ?? '',
    ];
} else {
    // GET — load existing movie
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    if (!$movie) {
        header("Location: dashboard.php");
        exit();
    }
    // Normalise duration to plain number for the input
    $movie['duration'] = preg_replace('/\s*min\s*$/i', '', $movie['duration']);
}

$currentYear = (int)date('Y');
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie</title>
    <link rel="stylesheet" href="public/styles/style_auth.css">
    <style>
        .field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 4px; }
        .field label { font-size: 0.82rem; color: #c55487; font-weight: 600; text-align: left; }
        .field-hint { font-size: 0.72rem; color: #aaa; }
        .error-list { margin: 0; padding-left: 18px; text-align: left; }
        .error-list li { margin: 3px 0; }
    </style>
</head>
<body>

<header>
    <h1>Edit Movie</h1>
    <nav>
        <a href="index.php">Movies</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <section class="login-container" style="max-width:560px;">

        <?php if (!empty($errors)): ?>
            <div class="warning">
                <strong>Please fix the following errors:</strong>
                <ul class="error-list">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form" novalidate>

            <div class="field">
                <label>Title *</label>
                <input type="text" name="title" maxlength="255"
                       value="<?= htmlspecialchars($movie['title']) ?>"
                       placeholder="e.g. Inception">
            </div>

            <div class="field">
                <label>Genre *</label>
                <input type="text" name="genre"
                       value="<?= htmlspecialchars($movie['genre']) ?>"
                       placeholder="e.g. Sci-Fi">
            </div>

            <div class="field">
                <label>Country *</label>
                <input type="text" name="country"
                       value="<?= htmlspecialchars($movie['country']) ?>"
                       placeholder="e.g. USA">
            </div>

            <div class="field">
                <label>
                    Year *
                    <span class="field-hint">(1888 – <?= $currentYear + 2 ?>)</span>
                </label>
                <input type="number" name="release_year"
                       min="1888" max="<?= $currentYear + 2 ?>"
                       value="<?= htmlspecialchars($movie['release_year']) ?>"
                       placeholder="e.g. 2010">
            </div>

            <div class="field">
                <label>
                    Duration *
                    <span class="field-hint">(minutes, 1–600)</span>
                </label>
                <input type="number" name="duration"
                       min="1" max="600"
                       value="<?= htmlspecialchars($movie['duration']) ?>"
                       placeholder="e.g. 148">
            </div>

            <div class="field">
                <label>
                    Rating *
                    <span class="field-hint">(0.0 – 10.0)</span>
                </label>
                <input type="number" name="rating"
                       step="0.1" min="0" max="10"
                       value="<?= htmlspecialchars($movie['rating']) ?>"
                       placeholder="e.g. 8.8">
            </div>

            <button type="submit">Save Changes</button>
            <a href="dashboard.php"
               style="text-align:center; color:#df7faf; margin-top:5px; display:block;">
               Cancel
            </a>

        </form>

    </section>
</main>

<footer>
    <p>Web Programming LV4</p>
</footer>

</body>
</html>
