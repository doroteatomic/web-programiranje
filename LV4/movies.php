<?php
include 'includes/auth.php';
include 'includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
"SELECT movies.*
FROM movies
JOIN user_movies ON movies.id = user_movies.movie_id
WHERE user_movies.user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
?>

<h1>My Movies</h1>

<?php while($movie = $result->fetch_assoc()): ?>

<div>
    <h3><?= $movie['title'] ?></h3>
    <p><?= $movie['genre'] ?></p>
</div>

<?php endwhile; ?>