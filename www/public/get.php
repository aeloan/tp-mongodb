<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redisClient = getRedisClient();

// @todo implementez la récupération des données d'une entité et la passer au template
// petite aide : https://github.com/VSG24/mongodb-php-examples
$cacheKey = "tp_element:{$_GET['id']}";
$page = (int)($_GET['page'] ?? "1");

if ($redisClient && $redisClient->exists($cacheKey))
{
    $entity = json_decode($redisClient->get($cacheKey), true);
}
else
{
    $entity = $manager->selectCollection('tp')->findOne(['_id' => new ObjectId($_GET['id'])]);
    $redisClient?->setex($cacheKey, 300, json_encode($entity));
}


// render template
try {
    echo $twig->render('get.html.twig', ['entity' => $entity, 'page' => $page]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}