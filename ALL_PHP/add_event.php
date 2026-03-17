<?php
include 'db.php';

$title = $_POST['title'];
$date = $_POST['event_date'];
$desc = $_POST['description'];

$uploadDir = "../uploads/event_images/";
$imageName = basename($_FILES['image']['name']);
$tmpName = $_FILES['image']['tmp_name'];

if (!file_exists($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

move_uploaded_file($tmpName, $uploadDir . $imageName);

$sql = "INSERT INTO events (title, event_date, description, image_path)
        VALUES ('$title', '$date', '$desc', '$imageName')";

if ($conn->query($sql)) {
  header("Location: events.php");
  exit();
} else {
  echo "Error: " . $conn->error;
}
?>
