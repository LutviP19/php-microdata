<?php 

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/meiliserach.php

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';

use Meilisearch\Client;

$client = new Client(env('MEILISEARCH_URL', 'http://localhost:7700'), env('MEILISEARCH_KEY', 'ms'));

// $movies_json = file_get_contents('movies.json');
$movies_json = '[{ "id": 1564, "title": "Kung Fu Panda", "genres": "Children\'s Animation", "release-year": 2008, "cast": [{ "Jack Black": "Po" }, { "Jackie Chan": "Monkey" }] }]';

$movies = json_decode($movies_json);

$client->index('movies')->addDocuments($movies);
