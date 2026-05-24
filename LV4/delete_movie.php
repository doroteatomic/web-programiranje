<?php
include 'includes/auth.php';
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: movies.php");
    exit();
}

$userId  = $_SESSION['user_id'];
$movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;

if (!$movieId) {
    header("Location: movies.php");
    exit();
}

$stmt = $conn->prepare(
    "DELETE FROM user_movies WHERE user_id = ? AND movie_id = ?"
);
$stmt->bind_param("ii", $userId, $movieId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: movies.php?message=Movie+removed+from+your+cart");
} else {
    header("Location: movies.php?message=Movie+not+found+in+your+videoteka&msg_type=warning");
}
exit();
?>
