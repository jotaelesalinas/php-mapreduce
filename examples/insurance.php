<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run `composer install` from the repository root before executing this example.\n");
    exit(1);
}

require_once $autoload;

use JLSalinas\SimpleMapReduce\MapReduce;
use JLSalinas\DataStreams\Json\JsonLineWriter;

// Goal: an insurer wants to aggregate policy data by county to compare
// exposure metrics without loading the full source file into memory.
$policies = [
    ['statecode' => 'FL', 'county' => 'Orange', 'point_latitude' => 28.538, 'point_longitude' => -81.379, 'tiv_2011' => 120000, 'tiv_2012' => 135000],
    ['statecode' => 'FL', 'county' => 'Orange', 'point_latitude' => 28.539, 'point_longitude' => -81.380, 'tiv_2011' => 90000, 'tiv_2012' => 94000],
    ['statecode' => 'FL', 'county' => 'Polk', 'point_latitude' => 27.800, 'point_longitude' => -81.710, 'tiv_2011' => 150000, 'tiv_2012' => 160000],
];

// Normalize the raw row into the shape that the reducer will aggregate.
$mapper = static function (array $item): array {
    $county = preg_replace('/\s+/', ' ', ucwords(strtolower($item['county'])));

    return [
        'state' => $item['statecode'],
        'county' => $county,
        'name' => $county . ', ' . $item['statecode'],
        'lat' => (float) $item['point_latitude'],
        'lng' => (float) $item['point_longitude'],
        'total_tiv_2011' => (float) $item['tiv_2011'],
        'total_tiv_2012' => (float) $item['tiv_2012'],
    ];
};

// Aggregate per state/county pair, keeping totals and derived metrics together.
$reducer = static function (mixed $carry, array $item): array {
    $count = ($carry['count'] ?? 0) + 1;
    $total_tiv_2011 = ($carry['total_tiv_2011'] ?? 0) + $item['total_tiv_2011'];
    $total_tiv_2012 = ($carry['total_tiv_2012'] ?? 0) + $item['total_tiv_2012'];

    return [
        'state' => $carry['state'] ?? $item['state'],
        'county' => $carry['county'] ?? $item['county'],
        'name' => $carry['name'] ?? $item['name'],
        'count' => $count,
        'lat' => (($carry['lat'] ?? 0) * (($carry['count'] ?? 0)) + $item['lat']) / $count,
        'lng' => (($carry['lng'] ?? 0) * (($carry['count'] ?? 0)) + $item['lng']) / $count,
        'total_tiv_2011' => $total_tiv_2011,
        'avg_tiv_2011' => $total_tiv_2011 / $count,
        'total_tiv_2012' => $total_tiv_2012,
        'avg_tiv_2012' => $total_tiv_2012 / $count,
        'diff_2011_2012' => $total_tiv_2012 / $total_tiv_2011 - 1,
    ];
};

// The writer is responsible for rendering the reduced groups.
$result = MapReduce::create()
    ->setInput($policies)
    ->setMapper($mapper)
    ->setGroupBy(static fn (array $item): string => strtolower($item['state'] . ' ' . $item['county']))
    ->setReducer($reducer)
    ->setOutput(new JsonLineWriter())
    ->run();

var_export($result);
