<?php

session_start();

$_SESSION["reponses"] = [];
$errors = [];

$_SESSION["reponses"] = $_POST;
$_SESSION["errors"] = [];




$launch_date = ($_POST["launching_date"]);
$format_date_launch =  new DateTime($launch_date);

$result_date = ($_POST["result_date"]);
$format_date_result =  new DateTime($result_date);


$end_date = ($_POST["end_date"]);
$format_date_end =  new DateTime($end_date);


$today_date = new DateTime();

// var_dump($today_date > $format_date_launch);
// exit;


if ($today_date > $format_date_launch ) {
    $errors["launching_date"] = "The launch date can't be before today";
    
}
if ($format_date_launch >= $format_date_result) {
    $errors["result_date"] = "The result date must be after the launching date";
}

if ($format_date_result > $format_date_end) {
    $errors["end_date"] = "The end date must be after the result date";
}

if (!isset($_POST["pays_list"]) || empty($_POST["pays_list"])) {
    $errors["pays_list[]"] = "At least one country must be selected";
}




if ($_POST["action"] === "pre-publish") {
    if ($_POST["statut_actuel"] === "draft") {
        $_SESSION["statut"] = "pre-prod";
    } else if ($_POST["statut_actuel"] === "pre-prod") {
        $_SESSION["statut"] = "prod";
    }
}


if (!isset($_SESSION['images'])) {
    $_SESSION['images'] = [];
}

if (isset($_FILES['images'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['images']['name'] as $pays => $slots) {
        foreach ($slots as $index => $name) {
            if ($_FILES['images']['error'][$pays][$index] === UPLOAD_ERR_OK) {
                $filename = $pays . '_' . $index . '_' . basename($name);
                $dest = $uploadDir . $filename;
                move_uploaded_file($_FILES['images']['tmp_name'][$pays][$index], $dest);
                $_SESSION['images'][$pays][$index] = $filename;
            } elseif (isset($_SESSION['images'][$pays][$index])) {
            }
        }
    }
}



foreach($_POST as $key => $value) {
 if ($key === 'pays_list') continue;
if(empty($value)) {
$errors[$key] = true;


}}




if(!empty($errors)) {
$_SESSION["errors"] = $errors;

    }

header("location:".$_SERVER["HTTP_REFERER"]);


?>