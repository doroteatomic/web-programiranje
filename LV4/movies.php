<?php
include 'includes/auth.php';
include 'includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT movies.*
     FROM movies
     JOIN user_movies ON movies.id = user_movies.movie_id
     WHERE user_movies.user_id = ?
     ORDER BY user_movies.created_at DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$message = $_GET['message'] ?? '';
$msgType = $_GET['msg_type'] ?? 'info';
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart</title>
    <link rel="stylesheet" href="public/styles/style.css">
</head>
<body>

<header>
    <h1>My Cart</h1>
    <nav>
        <a href="index.php">Movies</a>
        <a href="gallery.php">Gallery</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
    </nav>
</header>

<main>

    <?php if ($message): ?>
        <div class="<?= $msgType === 'warning' ? 'warning' : 'success-msg' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="movies-table">

        <?php if ($result->num_rows === 0): ?>
            <p style="text-align:center; color:#c55487; margin-top:30px;">
                Your cart is empty. <a href="index.php">Browse movies</a> to add some!
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Country</th>
                        <th>Year</th>
                        <th>Duration</th>
                        <th>Rating</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($movie = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($movie['title']) ?></td>
                        <td><?= htmlspecialchars($movie['genre']) ?></td>
                        <td><?= htmlspecialchars($movie['country']) ?></td>
                        <td><?= htmlspecialchars($movie['release_year']) ?></td>
                        <td><?= htmlspecialchars($movie['duration']) ?></td>
                        <td>&#9734; <?= htmlspecialchars($movie['rating']) ?></td>
                        <td>
                            <form method="POST" action="delete_movie.php"
                                  onsubmit="return confirm('Remove this movie from your cart?')">
                                <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                <button type="submit" class="btn-remove">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </section>

</main>

<footer>
    <p>Web Programming LV4</p>
</footer>

</body>
</html>
