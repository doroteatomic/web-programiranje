<?php
session_start();
include 'includes/db.php';

$result = $conn->query("SELECT * FROM photos LIMIT 10");
?>

<!DOCTYPE html>
<html>

<header>

    <h1>Gallery</h1>

    <nav>
        <a href="movies.php">Movies</a>
        <a href="gallery.php">Gallery</a>

        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="movies.php">My Movies</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

</header>
<head>
    <title>Gallery</title>
    <link rel="stylesheet" href="public/styles/style_slike.css">
</head>
<body>

<div class="gallery">

<?php while($photo = $result->fetch_assoc()): ?>

<div class="card">

    <img
        src="public/images/<?= $photo['filename'] ?>"
        width="300"
    >

<?php
$stmt = $conn->prepare(
"SELECT AVG(rating) as avgRating
FROM ratings
WHERE photo_id = ?"
);

$stmt->bind_param("i", $photo['id']);
$stmt->execute();

$ratingResult = $stmt->get_result();
$ratingData = $ratingResult->fetch_assoc();
?>

<p>
⭐ <?= round($ratingData['avgRating'], 1) ?>/5
</p>

<?php if(isset($_SESSION['user_id'])): ?>


<form method="POST" action="rate_photo.php">

    <input
        type="hidden"
        name="photo_id"
        value="<?= $photo['id'] ?>"
    >

    <select name="rating">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
    </select>

    <button>Rate</button>
</form>

<?php endif; ?>

</div>

<?php endwhile; ?>

</div>

</body>


</html>