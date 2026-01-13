<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redisClient = getRedisClient();
$elasticClient = getElasticSearchClient();

// @todo implementez la récupération des données dans la variable $list
// petite aide : https://github.com/VSG24/mongodb-php-examples
$pageSize = 20;
$searchQuery = $_GET['book'] ?? '';
$indexName = 'books';


if ($searchQuery == '' || $elasticClient == null) {

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

} else {
    $response = $elasticClient->search([
        'index' => $indexName,
        'body'  => [
            'query' => [
                'multi_match' => [
                    'query'  => $searchQuery,
                    'fields' => ['titre', 'auteur'],
                    'fuzziness' => 'AUTO',
                ]
            ]
        ]
    ]);

    $hits = $response['hits']['hits'] ?? [];
    $filteredIds = [];
    foreach ($hits as $hit) {
        $filteredIds[] = $hit['_id'];
    }

    $collection = $manager->selectCollection('tp');

    if (!empty($filteredIds)) {
        $list = $collection->find([
            '_id' => ['$in' => array_map(fn($id) => new MongoDB\BSON\ObjectId($id), $filteredIds)]
        ])->toArray();
    } else {
        header('Location: /index.php');
    }

    $nbPage = 1;
    $page = 1;

}


// render template
try {
    echo $twig->render('index.html.twig', ['list' => $list, 'nbPage' => $nbPage, 'page' => $page, 'query' => $searchQuery]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}



