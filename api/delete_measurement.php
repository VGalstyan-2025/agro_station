<?php
include("../config/db.php");

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $conn->query("DELETE FROM measurements WHERE id=$id");
}

header("Location: ../admin/measurements_form.php");
exit;
?>