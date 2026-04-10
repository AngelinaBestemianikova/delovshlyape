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

// Теперь берем число из поля ввода, а не считаем чекбоксы
$animator_count = (int) $_POST['animator_count'];

// Чекбоксы используем только для таблицы связей
$selected_animators = isset($_POST['animators']) ? $_POST['animators'] : [];

// Логика изображения (без изменений)
$image_path = isset($_POST['old_image_path']) ? mysqli_real_escape_string($link, $_POST['old_image_path']) : '';
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../img/programs/';
    if (!is_dir($upload_dir))
        mkdir($upload_dir, 0777, true);
    $new_file_name = uniqid('prog_') . '.' . pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $new_file_name)) {
        $image_path = 'img/programs/' . $new_file_name;
    }
}

if ($id > 0) {
    $query = "UPDATE programs SET 
        name='$name', description='$description', included_services='$included_services',
        type_id=$type_id, duration=$duration, max_children=$max_children,
        price=$price, animator_count=$animator_count, image_path='$image_path' WHERE id=$id";
    mysqli_query($link, $query);
} else {
    $query = "INSERT INTO programs 
        (name, description, included_services, type_id, duration, max_children, price, animator_count, image_path)
        VALUES ('$name','$description','$included_services',$type_id,$duration,$max_children,$price,$animator_count,'$image_path')";
    mysqli_query($link, $query);
    $id = mysqli_insert_id($link);
}

// Обновление связей в animator_programs
mysqli_query($link, "DELETE FROM animator_programs WHERE program_id = $id");
foreach ($selected_animators as $anim_id) {
    $anim_id = (int) $anim_id;
    mysqli_query($link, "INSERT INTO animator_programs (team_member_id, program_id) VALUES ($anim_id, $id)");
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);