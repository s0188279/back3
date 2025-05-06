<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql204.infinityfree.com";
$username = "if0_38861022";
$password = "Fafafo333yryr";
$dbname = "if0_38861022_form_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $name) || strlen($name) > 150) {
        $errors['name'] = 'ФИО должно содержать только буквы и пробелы (макс. 150 символов)';
    }

    $phone = trim($_POST['phone'] ?? '');
    if (!preg_match('/^\+7\d{10}$/', $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7XXXXXXXXXX';
    }

    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }

    $birthdate = $_POST['birthdate'] ?? '';
    $max_date = date('Y-m-d', strtotime('-10 years'));
    if (empty($birthdate) || $birthdate > $max_date) {
        $errors['birthdate'] = 'Некорректная дата рождения';
    }

    $gender = $_POST['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = 'Укажите пол';
    }

    $languages = $_POST['languages'] ?? [];
    if (empty($languages)) {
        $errors['languages'] = 'Выберите хотя бы один язык';
    } else {
        $valid_langs = $conn->query("SELECT id FROM languages")->fetch_all(MYSQLI_ASSOC);
        $valid_ids = array_column($valid_langs, 'id');
        foreach ($languages as $lang_id) {
            if (!in_array($lang_id, $valid_ids)) {
                $errors['languages'] = 'Некорректный выбор языков';
                break;
            }
        }
    }

    $bio = trim($_POST['bio'] ?? '');
    if (!preg_match('/^[a-zA-Z\s\.,!?-]+$/', $bio)) {
        $errors['bio'] = 'Биография должна содержать только английские буквы';
    }

    $agree = isset($_POST['agree']) ? 1 : 0;
    if (!$agree) {
        $errors['agree'] = 'Необходимо согласие с контрактом';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (name, phone, email, birthdate, gender, bio, agree) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $name, $phone, $email, $birthdate, $gender, $bio, $agree);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();

        foreach ($languages as $lang_id) {
            $stmt = $conn->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $lang_id);
            $stmt->execute();
            $stmt->close();
        }
        echo "Данные успешно сохранены!";
    } else {
        echo "Ошибка: " . $stmt->error;
    }
}

$conn->close();
?>