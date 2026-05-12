<?php
include 'includes/auth.php';
include 'includes/db.php';

$userId = $_SESSION['user_id'];
$photoId = $_POST['photo_id'];
$rating = $_POST['rating'];

$stmt = $conn->prepare(
"INSERT INTO ratings(user_id, photo_id, rating)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE rating=?"
);

$stmt->bind_param(
    "iiii",
    $userId,
    $photoId,
    $rating,
    $rating
);

$stmt->execute();

header("Location: gallery.php");
exit();
?>