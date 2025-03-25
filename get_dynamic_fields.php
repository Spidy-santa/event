<?php
include '../includes/db.php';

$category = $_GET['category'];
$stmt = $pdo->prepare("SELECT * FROM dynamic_fields WHERE category = ?");
$stmt->execute([$category]);
$fields = $stmt->fetchAll();

foreach ($fields as $field) {
  echo '<div class="mb-3">';
  echo '<label>' . ucfirst(str_replace("_", " ", $field['field_name'])) . '</label>';
  if ($field['field_type'] === 'textarea') {
    echo '<textarea name="' . $field['field_name'] . '" class="form-control"></textarea>';
  } else {
    echo '<input type="' . $field['field_type'] . '" name="' . $field['field_name'] . '" class="form-control">';
  }
  echo '</div>';
}
?>