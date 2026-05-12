<?php
session_start();
include 'includes/db.php';

$genre = $_GET['genre'] ?? '';

if (!empty($genre)) {

    $stmt = $conn->prepare(
        "SELECT * FROM movies
         WHERE genre LIKE ?"
    );

    $search = "%$genre%";

    $stmt->bind_param("s", $search);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query(
        "SELECT * FROM movies"
    );
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Movie Database</title>

    <link rel="stylesheet" href="public/styles/style.css">
</head>

<body>

<header>

    <h1>Movie Database</h1>

    <nav>
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

<main>

    <section class="filters">

        <form method="GET">

            <input
                type="text"
                name="genre"
                placeholder="Filter by genre"
                value="<?= htmlspecialchars($genre) ?>"
            >

            <button type="submit">
                Filter
            </button>

        </form>

    </section>

    <section class="movies-table">

        <table>

            <tr>
                <th>Title</th>
                <th>Genre</th>
                <th>Country</th>
                <th>Year</th>
                <th>Duration</th>
                <th>Rating</th>
                <th>Action</th>
            </tr>

            <?php while($movie = $result->fetch_assoc()): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($movie['title']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($movie['genre']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($movie['country']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($movie['release_year']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($movie['duration']) ?>
                </td>

                <td>
                    ⭐ <?= htmlspecialchars($movie['rating']) ?>
                </td>

                <td>

                    <?php if(isset($_SESSION['user_id'])): ?>

                        <form
                            method="POST"
                            action="add_movie.php"
                        >

                            <input
                                type="hidden"
                                name="movie_id"
                                value="<?= $movie['id'] ?>"
                            >

                            <button type="submit">
                                Add
                            </button>

                        </form>

                    <?php else: ?>

                        <p>
                            Login to add
                        </p>

                    <?php endif; ?>

                </td>

            </tr>

            <?php if($movie['rating'] < 5): ?>

                <tr>

                    <td colspan="7">

                        <div class="warning">
                            This movie has a low rating!
                        </div>

                    </td>

                </tr>

            <?php endif; ?>

            <?php endwhile; ?>

        </table>

    </section>

</main>

<footer>

    <p>
        Web Programming LV4
    </p>

</footer>

</body>
</html>