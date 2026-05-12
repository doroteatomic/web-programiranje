<?php
include 'includes/auth.php';
include 'includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    die('Access denied');
}

if(isset($_FILES['photo'])) {

    $file = $_FILES['photo'];

    $allowed = [
        'image/jpeg',
        'image/png'
    ];

    if(in_array($file['type'], $allowed)) {

        if($file['size'] <= 5 * 1024 * 1024) {

            move_uploaded_file(
                $file['tmp_name'],
                'uploads/' . $file['name']
            );

            $stmt = $conn->prepare(
                "INSERT INTO photos(filename)
                 VALUES (?)"
            );

            $stmt->bind_param("s", $file['name']);
            $stmt->execute();
        }
    }
}

?>

<h1>Admin Dashboard</h1>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="photo">
    <button>Upload</button>
</form>



