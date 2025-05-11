<?php
    session_start();
    $id = isset($_POST['id']) ? $_POST['id'] : 0;
    if (isset($_SESSION['book'][$id])) {
        $_SESSION['book'][$id]++;
    } else {
        $_SESSION['book'][$id] = 1;
    }
    header("Location: tusach.php");
    exit;
?>