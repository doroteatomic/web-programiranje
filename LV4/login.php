<?php
session_start();
include 'includes/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {

        $message = "All fields are required.";

    } else {

        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header("Location: index.php");
                exit();

            } else {

                $message = "Wrong password.";
            }

        } else {

            $message = "User does not exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Login</title>

    <link rel="stylesheet" href="public/styles/style_auth.css">
</head>

<body>

<header>

    <h1>Login</h1>

    <nav>
        <a href="index.php">Movies</a>
        <a href="gallery.php">Gallery</a>
        <a href="register.php">Register</a>
    </nav>

</header>

<main>

    <section class="login-container">

        <?php if(!empty($message)): ?>

            <div class="warning">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="login-form">

            <input
                type="text"
                name="username"
                placeholder="Username"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

    </section>

</main>

<footer>

    <p>
        Web Programming LV4
    </p>

</footer>

</body>
</html>