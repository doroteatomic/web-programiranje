<?php
session_start();
include 'includes/db.php';

$order  = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';
$editId = isset($_GET['edit_photo']) ? (int)$_GET['edit_photo'] : 0;

$result = $conn->query(
    "SELECT photos.*, COALESCE(AVG(ratings.rating), 0) as avg_rating
     FROM photos
     LEFT JOIN ratings ON photos.id = ratings.photo_id
     GROUP BY photos.id
     ORDER BY avg_rating $order
     LIMIT 10"
);

$userRatings = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $rStmt = $conn->prepare("SELECT photo_id, rating FROM ratings WHERE user_id = ?");
    $rStmt->bind_param("i", $uid);
    $rStmt->execute();
    $rRes = $rStmt->get_result();
    while ($row = $rRes->fetch_assoc()) {
        $userRatings[$row['photo_id']] = $row['rating'];
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link rel="stylesheet" href="public/styles/style_slike.css">
    <style>
        .sort-bar {
            text-align: center;
            margin: 20px 0 10px;
        }
        .sort-bar a {
            padding: 8px 18px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0 4px;
            transition: 0.2s;
        }
        .sort-bar a.active {
            background: linear-gradient(135deg, #df7faf, #ff67ad);
            color: white;
        }
        .sort-bar a:not(.active) {
            background: #fff0f6;
            color: #df7faf;
            border: 1px solid #ffd6e7;
        }
        .sort-bar a:not(.active):hover { background: #ffd6e7; }
        .user-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 6px;
            font-size: 0.9rem;
            color: #555;
        }
        .user-rating strong { color: #c55487; }
        .btn-edit-rating {
            padding: 5px 12px;
            border-radius: 20px;
            border: 1.5px solid #df7faf;
            background: white;
            color: #df7faf;
            font-size: 0.8rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-edit-rating:hover { background: #fff0f6; }
    </style>
</head>
<body>

<header>
    <h1>Gallery</h1>
    <nav>
        <a href="index.php">Movies</a>
        <a href="gallery.php">Gallery</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="movies.php">Cart</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="sort-bar">
    <a href="gallery.php?sort=desc" class="<?= $order === 'DESC' ? 'active' : '' ?>">&#9660; Highest Rated</a>
    <a href="gallery.php?sort=asc"  class="<?= $order === 'ASC'  ? 'active' : '' ?>">&#9650; Lowest Rated</a>
</div>

<div class="gallery">

<?php while ($photo = $result->fetch_assoc()): ?>

<?php
    $avg          = round($photo['avg_rating'], 1);
    $photoId      = (int)$photo['id'];
    $alreadyRated = isset($userRatings[$photoId]);
    $myRating     = $alreadyRated ? $userRatings[$photoId] : null;
    $showForm     = !$alreadyRated || $editId === $photoId;
?>

<div class="card">

    <img src="public/images/<?= htmlspecialchars($photo['filename']) ?>" width="300">

    <p>&#9734; <?= $avg > 0 ? $avg : '-' ?>/5
        <small style="color:#aaa;">(avg)</small>
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>

        <?php if ($showForm): ?>
            <form method="POST" action="rate_photo.php">
                <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                <select name="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $myRating == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <button><?= $alreadyRated ? 'Save' : 'Rate' ?></button>
                <?php if ($alreadyRated): ?>
                    <a href="gallery.php?sort=<?= strtolower($order) ?>" class="btn-edit-rating" style="margin-top:4px;">Cancel</a>
                <?php endif; ?>
            </form>

        <?php else: ?>
            <div class="user-rating">
                Your rating: <strong><?= $myRating ?>/5</strong>
                <a href="gallery.php?sort=<?= strtolower($order) ?>&edit_photo=<?= $photoId ?>" class="btn-edit-rating">Edit</a>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php endwhile; ?>

</div>

</body>
</html>