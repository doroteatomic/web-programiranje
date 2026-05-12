<?php
include 'includes/auth.php';
include 'includes/db.php';

$userId = $_SESSION['user_id'];
$movieId = $_POST['movie_id'];

$stmt = $conn->prepare(
    "INSERT INTO user_movies(user_id, movie_id)
     VALUES (?, ?)"
);

$stmt->bind_param("ii", $userId, $movieId);
$stmt->execute();

header("Location: index.php");
exit();
?>

