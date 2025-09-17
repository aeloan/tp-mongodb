<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;

$manager = getMongoDbManager();
$manager->selectCollection('tp')->deleteOne(['_id' => new ObjectId($_GET['id'])]);

header('Location: /index.php');
exit;