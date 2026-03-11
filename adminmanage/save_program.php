<?php
require_once '../includes/db.php';

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = mysqli_real_escape_string($link, $_POST['name']);
$description = mysqli_real_escape_string($link, $_POST['description']);
$included_services = mysqli_real_escape_string($link, $_POST['included_services']);
$type_id = (int) $_POST['type_id'];
$duration = (int) $_POST['duration'];
$max_children = (int) $_POST['max_children'];
$price = (float) $_POST['price'];
$animator_count = (int) $_POST['animator_count'];
$image_path = mysqli_real_escape_string($link, $_POST['image_path']);

if ($id > 0) {
    // обновление
    mysqli_query($link, "UPDATE programs SET 
        name='$name', description='$description', included_services='$included_services',
        type_id=$type_id, duration=$duration, max_children=$max_children,
        price=$price, animator_count=$animator_count, image_path='$image_path' WHERE id=$id");
} else {
    // добавление
    mysqli_query($link, "INSERT INTO programs 
        (name, description, included_services, type_id, duration, max_children, price, animator_count, image_path)
        VALUES ('$name','$description','$included_services',$type_id,$duration,$max_children,$price,$animator_count,'$image_path')");
}

// возвращаем JSON для проверки
header('Content-Type: application/json');
echo json_encode(['success' => true]);