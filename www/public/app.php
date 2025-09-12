<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// @todo implementez la récupération des données dans la variable $list
// petite aide : https://github.com/VSG24/mongodb-php-examples
$list = [];
$books = $manager->selectCollection('tp')->find([])->toArray();
foreach ($books as $book) {
    $list[] = [$book['objectid'], $book['titre'], $book['auteur']];
}

//$list = [['name' => 'test']];

// render template
try {
    echo $twig->render('index.html.twig', ['list' => $list]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}



