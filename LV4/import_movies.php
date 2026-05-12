<?php
include "db.php";

if (($handle = fopen("movies.csv", "r")) !== FALSE) {

    fgetcsv($handle, 1000, ","); 

    $stmt = $conn->prepare(
        "INSERT INTO movies (title, genre, country, release_year, duration, rating)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

        $stmt->bind_param(
            "sssisd",
            $data[0],
            $data[1],
            $data[2],
            $data[3],
            $data[4],
            $data[5]
        );

        $stmt->execute();
    }

    fclose($handle);
    $stmt->close();
}
?>