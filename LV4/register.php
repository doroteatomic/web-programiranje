<?php
session_start();
include 'includes/db.php';

$message = "";
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username)) $errors[] = "Username is required.";
    if (empty($email))    $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($password !== '' && strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            header("Location: login.php?message=Registration+successful%2C+please+log+in");
            exit();
        } else {
            $errors[] = "Username or email already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="public/styles/style_auth.css">
</head>
<body>

<header>
    <h1>Register</h1>
    <nav>
        <a href="index.php">Movies</a>
        <a href="gallery.php">Gallery</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<main>
    <section class="login-container">

        <?php if (!empty($errors)): ?>
            <div class="warning">
                <?php foreach ($errors as $err): ?>
                    <p><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">

            <input
                type="text"
                name="username"
                placeholder="Username"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                required
            >

            <input
                type="email"
                name="email"
                placeholder="Email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Password (min. 6 characters)"
                required
            >

            <button type="submit">Register</button>

        </form>

        <p style="margin-top:15px; color:#888; font-size:0.9rem;">
            Already have an account? <a href="login.php" style="color:#df7faf;">Login here</a>
        </p>

    </section>
</main>

<footer>
    <p>Web Programming LV4</p>
</footer>

</body>
</html>
