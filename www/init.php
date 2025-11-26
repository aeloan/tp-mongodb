<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/vendor/autoload.php';

use MongoDB\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Prepend a base path if Predis is not available in your "include_path".


// env configuration
(Dotenv\Dotenv::createImmutable(__DIR__))->load();

function getTwig(): Environment
{
    // twig configuration
    return new Environment(new FilesystemLoader('../templates'));
}

function getMongoDbManager(): Database
{
    $client = new MongoDB\Client("mongodb://{$_ENV['MDB_USER']}:{$_ENV['MDB_PASS']}@{$_ENV['MDB_SRV']}:{$_ENV['MDB_PORT']}");
    return $client->selectDatabase($_ENV['MDB_DB']);
}

function getRedisClient():  ?Predis\Client
{
    return $_ENV['REDIS_ENABLE'] ? new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $_ENV['REDIS_HOST'],
        'port'   => $_ENV['REDIS_PORT'],
    ]) : null;
}
