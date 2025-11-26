<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redisClient = getRedisClient();

$page = (int)($_GET['page'] ?? "1");
$cacheKey = "tp_element:{$_GET['id']}";
$cacheKeyPage = "tp_page:{$page}";

$manager->selectCollection('tp')->updateOne(
    [ '_id' => new ObjectId($_GET['id']) ],
    [ '$set' => [
        'titre' => $_POST['titre'],
        'auteur' => $_POST['auteur'],
        'siecle' => $_POST['siecle'],
        'edition' => $_POST['edition'],
        'langue' => $_POST['langue'],
        'cote' => $_POST['cote'],
        'objectid' => $_POST['objectid'],
    ]]
);

$redisClient?->setex($cacheKey, 300, json_encode($_POST));
$redisClient?->del($cacheKeyPage);

header('Location: /index.php?page=' . $page);
exit;