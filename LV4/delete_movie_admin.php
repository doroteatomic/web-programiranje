<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
if (!$movieId) {
    header("Location: dashboard.php");
    exit();
}

// Cascading delete via FK will also remove entries in user_movies
$stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();

header("Location: dashboard.php?message=Movie+deleted+successfully");
exit();
?>
