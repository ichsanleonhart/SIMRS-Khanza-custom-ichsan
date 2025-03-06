<?php
if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
    $name = $_FILES['file']['name'];
    $size = $_FILES['file']['size'];
    $type = $_FILES['file']['type'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $error = $_FILES['file']['error'];
    $maxsize = "99999999999999";
    $location = $_GET['doc'];
    // $location = "";
    // perform any security check here...

    if ($size <= $maxsize) {
        if (move_uploaded_file($tmp_name, $location . $name)) {
            // perform your operation down here
        }
    }
}
