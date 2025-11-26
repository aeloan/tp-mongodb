<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redisClient = getRedisClient();

// @todo implementez la récupération des données dans la variable $list
// petite aide : https://github.com/VSG24/mongodb-php-examples
$pageSize = 20;

$cacheKeyCount = "tp_count";
if ($redisClient && $redisClient->exists($cacheKeyCount))
{
    $count = (int)$redisClient->get($cacheKeyCount);
}
else
{
    $count = $manager->selectCollection('tp')->countDocuments();
    $redisClient?->setex($cacheKeyCount, 300, $count);
}

$page = (int)($_GET['page'] ?? "1");
$nbPage = (int)ceil($count / $pageSize);


$cacheKey = "tp_page:$page";

if ($redisClient && $redisClient->exists($cacheKey))
{
    $list = json_decode($redisClient->get($cacheKey), true);

    # Déserialisation de l'id
    foreach ($list as &$item) {
        if (isset($item['_id']['$oid'])) {
            $item['_id'] = $item['_id']['$oid'];
        }
    }
}
else
{
    $list = $manager->selectCollection('tp')->find([], [ 'limit' => $pageSize, 'skip' => $pageSize * ($page-1)])->toArray();
    $redisClient?->setex($cacheKey, 300, json_encode($list));
}


// render template
try {
    echo $twig->render('index.html.twig', ['list' => $list, 'nbPage' => $nbPage, 'page' => $page]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}



