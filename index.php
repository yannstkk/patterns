<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);
 
$page = isset($_GET['page']) ? $_GET['page'] : 'ListEvent';

$pages_autorisees = ['AddEvent', 'ListEvent', 'AddEventGift'];

if (!in_array($page, $pages_autorisees)) {
    $page = 'ListEvent';
}



include($page.'.php');