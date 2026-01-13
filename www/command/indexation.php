<?php

include_once __DIR__.'/../init.php';


echo "\nFichier à compléter pour indexer dans ElasticSearch les documents\n";


$client = getElasticsearchClient();

$indexName = 'books';

$client->indices()->delete([
    'index' => $indexName
]);
$client->indices()->create([
    'index' => $indexName,
]);

$manager = getMongoDbManager();
$list = $manager->selectCollection('tp')->find()->toArray();


$i = 0;
foreach ($list as $document) {


    $params['body'][] = [
        'index' => [
            '_index' => $indexName,
            '_id'    => (string) $document['_id'],
        ]
    ];

    $params['body'][] = [
        'titre'    => $document['titre'],
        'auteur' => $document['auteur'],
        'edition' => $document['edition'],
        'langue' => $document['langue'],
        'cote' => $document['cote'],
        'siecle' => $document['siecle'],
        'objectid' => $document['objectid'],
    ];

    if ($i % 1000 == 0) {
        $responses = $client->bulk($params);

        $params = ['body' => []];

        unset($responses);
    }

    $i = $i + 1;
}

if (!empty($params['body'])) {
    $responses = $client->bulk($params);
}

echo "\nTerminé\n";


return 1;