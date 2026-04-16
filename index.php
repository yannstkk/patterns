<?php
//index.php
$page = isset($_GET['page']) ? $_GET['page'] : 'AddEvent';

$pages_autorisees = ['AddEvent'];


if (!in_array($page, $pages_autorisees)) {
    $page = 'AddEvent';
}

include($page . '.php');