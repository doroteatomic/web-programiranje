<?php
session_start();
include 'includes/db.php';

$genre   = trim($_GET['genre']   ?? '');
$year    = trim($_GET['year']    ?? '');
$country = trim($_GET['country'] ?? '');
$sort    = $_GET['sort']  ?? 'title';
$order   = $_GET['order'] ?? 'ASC';

$allowedSorts  = ['title', 'release_year', 'rating'];
$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sort, $allowedSorts))   $sort  = 'title';
if (!in_array($order, $allowedOrders)) $order = 'ASC';

$conditions = [];
$params     = [];
$types      = '';

if ($genre !== '') {
    $conditions[] = "genre = ?";
    $params[]     = $genre;
    $types       .= 's';
}
if ($year !== '' && ctype_digit($year)) {
    $conditions[] = "release_year = ?";
    $params[]     = (int)$year;
    $types       .= 'i';
}
if ($country !== '') {
    $conditions[] = "country LIKE ?";
    $params[]     = "%$country%";
    $types       .= 's';
}

$sql = "SELECT * FROM movies";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY $sort $order";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch distinct genres for dropdown
$genreResult = $conn->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC");
$genres = $genreResult->fetch_all(MYSQLI_ASSOC);

// Fetch IDs already in user cart
$userMovieIds = [];
if (isset($_SESSION['user_id'])) {
    $cartStmt = $conn->prepare("SELECT movie_id FROM user_movies WHERE user_id = ?");
    $cartStmt->bind_param("i", $_SESSION['user_id']);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();
    while ($row = $cartResult->fetch_assoc()) {
        $userMovieIds[] = $row['movie_id'];
    }
}

$message = $_GET['message'] ?? '';
$msgType = $_GET['msg_type'] ?? 'info';
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
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="movies.php">Cart</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main>

    <?php if ($message): ?>
        <?php if ($msgType === 'added'): ?>
            <div class="toast" id="toast">
                <span>&#10003; <?= htmlspecialchars($message) ?></span>
                <a href="movies.php" class="toast-btn">View Cart</a>
                <button class="toast-close" onclick="document.getElementById('toast').style.display='none'">&times;</button>
            </div>
        <?php else: ?>
            <div class="<?= $msgType === 'warning' ? 'warning' : 'success-msg' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="filters">
        <form method="GET">
            <select name="genre">
                <option value="">All Genres</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= htmlspecialchars($g['genre']) ?>"
                        <?= $genre === $g['genre'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['genre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="year"    placeholder="Year"    value="<?= htmlspecialchars($year) ?>" min="1888" max="2100">
            <input type="text"   name="country" placeholder="Country" value="<?= htmlspecialchars($country) ?>">

            <select name="sort">
                <option value="title"        <?= $sort === 'title'        ? 'selected' : '' ?>>Sort: Title</option>
                <option value="release_year" <?= $sort === 'release_year' ? 'selected' : '' ?>>Sort: Year</option>
                <option value="rating"       <?= $sort === 'rating'       ? 'selected' : '' ?>>Sort: Rating</option>
            </select>

            <select name="order">
                <option value="ASC"  <?= $order === 'ASC'  ? 'selected' : '' ?>>ASC</option>
                <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>DESC</option>
            </select>

            <button type="submit">Filter</button>
            <a href="index.php" class="btn-reset">Reset</a>
        </form>
    </section>

    <section class="movies-table">
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
                <tr <?= $movie['rating'] < 5.0 ? 'class="low-rating-row"' : '' ?>>
                    <td><?= htmlspecialchars($movie['title']) ?></td>
                    <td><?= htmlspecialchars($movie['genre']) ?></td>
                    <td><?= htmlspecialchars($movie['country']) ?></td>
                    <td><?= htmlspecialchars($movie['release_year']) ?></td>
                    <td><?= htmlspecialchars($movie['duration']) ?></td>
                    <td>&#9734; <?= htmlspecialchars($movie['rating']) ?></td>
                    <td>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (in_array($movie['id'], $userMovieIds)): ?>
                                <form method="POST" action="delete_movie.php">
                                    <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                    <button type="submit" class="btn-in-cart">
                                        <span class="text-normal">&#10003; In Cart</span>
                                        <span class="text-hover">Remove</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="add_movie.php">
                                    <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                    <button type="submit">Add to Cart</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php">Login to add</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($movie['rating'] < 5.0): ?>
                <tr>
                    <td colspan="7">
                        <div class="warning">&#9888; This movie has a low rating (<?= $movie['rating'] ?>)!</div>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>

</main>

<footer>
    <p>Web Programming LV4</p>
</footer>

</body>
</html>