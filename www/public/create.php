<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// petite aide : https://github.com/VSG24/mongodb-php-examples

if (!empty($_POST)) {
    // @todo coder l'enregistrement d'un nouveau livre en lisant le contenu de $_POST
    $doc = [
        "titre" => $_POST["title"],
        "auteur" => $_POST["author"],
        "edition" => $_POST["edition"],
        "langue" => $_POST["langue"],
        "cote" => $_POST["cote"],
        "siecle" => $_POST["century"],
        "objectid" => $_POST["objectid"],
    ];
    $manager->selectCollection('tp')->insertOne($doc);

    header('Location: /index.php');
    exit;
} else {
// render template
    try {
        echo $twig->render('create.html.twig');
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
}

