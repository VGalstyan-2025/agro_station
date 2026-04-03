<?php
include("../config/db.php");

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $conn->query("DELETE FROM historical_weather WHERE id=$id");
}

header("Location: ../admin/historical_form.php");
exit;
?>