<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// @todo implementez la récupération des données dans la variable $list
// petite aide : https://github.com/VSG24/mongodb-php-examples
$pageSize = 20;
$count = $manager->selectCollection('tp')->countDocuments();
$page = (int)($_GET['page'] ?? 1);
$nbPage = (int)ceil($count / $pageSize);

$list = $manager->selectCollection('tp')->find([], [ 'limit' => $pageSize, 'skip' => $pageSize * ($page-1)])->toArray();



// render template
try {
    echo $twig->render('index.html.twig', ['list' => $list, 'nbPage' => $nbPage, 'page' => $page]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}



