<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();



$manager->selectCollection('tp')->updateOne(
    [ '_id' => new ObjectId($_GET['id']) ],
    [ '$set' => [ 'titre' => $_POST['title'], 'auteur' => $_POST['author'], 'siecle' => $_POST['century'] ]]
);

header('Location: /index.php');
exit;