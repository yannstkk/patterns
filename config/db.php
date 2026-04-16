

<?php
//db.php

/**
CONNEXIONS BDD
 **/

//pour la prod
//include_once '/var/config/fonctions.php';
//include_once '/var/config/connexion_all.php';




//pour le local

if (!function_exists('informationBDD')) {
    function informationBDD()
    {

        $user_preprod = "root";
        $mdp_preprod = "";


        $infoBDD = array(


            200 => array(
                'login' => $user_preprod,
                'password' => $mdp_preprod,
                'bd' => 'bddstg' // BO_local
            ),


        );

        return $infoBDD;
    }
}
$acces = informationBDD();


if (!defined('DUPLICATION_OVH'))
    define('DUPLICATION_OVH', false);

if (!function_exists('UrlServeurBDD')) {
    function UrlServeurBDD($provenance)
    {   
       
            return "127.0.0.1:3306"; // 3307
  
    }
}



if (!function_exists('connectBaseExterne_mysqli')) {
    function connectBaseExterne_mysqli($provenance)
    {

        $acces = informationBDD();


        try {

            if (!isset($acces[$provenance]['bd'])) {
                $mysqlconnect = new mysqli(UrlServeurBDD($provenance), $acces[$provenance]['login'], $acces[$provenance]['password']);
            } else {
                $mysqlconnect = new mysqli(UrlServeurBDD($provenance), $acces[$provenance]['login'], null, $acces[$provenance]['bd'], '10075');
            }

            mysqli_set_charset($mysqlconnect, "utf8");

        } catch (mysqli_sql_exception $e) {

            echo "ERROR DATABASE CONNEXION";

        }

        // var_dump($provenance,$mysqlconnect) ;
        return $mysqlconnect;
    }

}


if (!function_exists('connectBaseExterne_PDO')) {

    function connectBaseExterne_PDO($provenance)
    {

        $acces33 = informationBDD();

        try {

            $connect = new PDO('mysql:host=' . UrlServeurBDD($provenance) . ';dbname=' . $acces33[$provenance]['bd'], $acces33[$provenance]['login'], null );
            //$connect->query('SET time_zone = "+1:00"');
            $connect->query("SET NAMES 'utf8'");


        } catch (PDOException $e) {

            echo "ERROR DATABASE CONNEXION";

        }

        return $connect;

    }
}