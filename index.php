<?php
// index.php
$page = isset($_GET['page']) ? $_GET['page'] : 'ListEvent';

$pages_autorisees = ['AddEvent', 'ListEvent'];

if (!in_array($page, $pages_autorisees)) {
    $page = 'ListEvent';
}

include($page.'.php');