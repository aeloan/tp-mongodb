<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;

$manager = getMongoDbManager();
$redisClient = getRedisClient();

$cacheKey = "tp_element:{$_GET['id']}";
$cacheKeyPage = "tp_page";
$cacheKeyCount = "tp_count";
$page = (int)($_GET['page'] ?? "1");

$manager->selectCollection('tp')->deleteOne(['_id' => new ObjectId($_GET['id'])]);
$redisClient?->del($cacheKey);
$redisClient?->del($cacheKeyCount);

# Dévalider toutes les pages suivantes
$cursor = 0;

do {
    [$cursor, $keys] = $redisClient?->scan($cursor, ['MATCH' => 'tp_page:*']);

    $toDelete = array_filter($keys, function($k) use ($page) {
        return preg_match('/tp_page:(\d+)/', $k, $m) && (int)$m[1] >= $page;
    });

    if ($toDelete) {
        $redisClient?->del($toDelete);
    }

} while ($cursor);

header('Location: /index.php?page=' . $page);
exit;