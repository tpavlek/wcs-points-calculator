<?php

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Collection;

require 'vendor/autoload.php';

$client = new \Goutte\Client();

$crawler = $client->request('GET', 'http://wiki.teamliquid.net/starcraft2/2015_StarCraft_II_World_Championship_Series/Standings');

/** @var Crawler $table */
$table = $crawler->filter('table.sortable.wikitable')->first();

$points = [];
$table->filter('tr')->each(function(\Symfony\Component\DomCrawler\Crawler $row) use (&$points) {

    $first_td = clean($row->children()->first()->html());
    if (!is_numeric($first_td)) {
        // We only want the player lines, which start with an integer.
        return true;
    }

    $cols = $row->children();

    $name = clean($cols->getNode(3)->textContent);
    $wcs_s1 = clean($cols->getNode(5)->textContent);
    $gsl_s1 = clean($cols->getNode(6)->textContent);
    $ssl_s1 = clean($cols->getNode(7)->textContent);

    $others = 0;
    for ($i = 15; $i < 22; $i++) {
        $others += clean($cols->getNode($i)->textContent);
    }

    $points[$name]['all'] = $wcs_s1 + $gsl_s1 + $ssl_s1 + $others;
    $points[$name]['no_ssl'] = $wcs_s1 + $gsl_s1 + $others;
    return true;
});

$no_ssl = (new Collection($points))->sort(function($first, $second) {
    if ($first['no_ssl'] == $second['no_ssl']) return 0;
    return ($first['no_ssl'] > $second['no_ssl']) ? -1 : 1;
});

$all = (new Collection($points))->sort(function($first, $second) {
    if ($first['all'] == $second['all']) return 0;
    return ($first['all'] > $second['all']) ? -1 : 1;
});

outputCollectionAsCSV($no_ssl, "no_ssl");
outputCollectionAsCSV($all, "all");



function outputCollectionAsCSV(Collection $collection, $type) {
    $writer = \League\Csv\Writer::createFromFileObject(new SplTempFileObject());

    $writer->insertOne(['name', 'points' ]);
    foreach ($collection as $name => $value) {
        $writer->insertOne([ $name, $value[$type] ]);
    };

    file_put_contents($type . ".csv", (string)$writer);
}


function clean($string) {
    return trim(strip_tags($string));
}


