<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use JLSalinas\SimpleMapReduce\MapReduce;
use JLSalinas\SimpleMapReduce\Writer;

final class JsonLineWriter implements Writer
{
    public function write(mixed $item): void
    {
        echo json_encode($item, JSON_THROW_ON_ERROR) . PHP_EOL;
    }

    public function close(): void
    {
        echo "finished" . PHP_EOL;
    }
}

$rows = [
    ['statecode' => 'FL', 'county' => 'Orange', 'point_latitude' => 28.538, 'point_longitude' => -81.379, 'tiv_2011' => 120000, 'tiv_2012' => 135000],
    ['statecode' => 'FL', 'county' => 'Orange', 'point_latitude' => 28.539, 'point_longitude' => -81.380, 'tiv_2011' => 90000, 'tiv_2012' => 94000],
    ['statecode' => 'FL', 'county' => 'Polk', 'point_latitude' => 27.800, 'point_longitude' => -81.710, 'tiv_2011' => 150000, 'tiv_2012' => 160000],
];

$mapper = static function (array $item): array {
    $county = preg_replace('/\s+/', ' ', ucwords(strtolower($item['county'])));

    return [
        'state' => $item['statecode'],
        'county' => $county,
        'name' => $county . ', ' . $item['statecode'],
        'count' => 1,
        'lat' => (float) $item['point_latitude'],
        'lng' => (float) $item['point_longitude'],
        'total_tiv_2011' => (float) $item['tiv_2011'],
        'total_tiv_2012' => (float) $item['tiv_2012'],
    ];
};

$reducer = static function (mixed $carry, array $item): array {
    if ($carry === null) {
        $item['avg_tiv_2011'] = $item['total_tiv_2011'];
        $item['avg_tiv_2012'] = $item['total_tiv_2012'];
        return $item;
    }

    $count = $carry['count'] + $item['count'];
    $total_tiv_2011 = $carry['total_tiv_2011'] + $item['total_tiv_2011'];
    $total_tiv_2012 = $carry['total_tiv_2012'] + $item['total_tiv_2012'];

    return [
        'state' => $carry['state'],
        'county' => $carry['county'],
        'name' => $carry['name'],
        'count' => $count,
        'lat' => ($carry['lat'] * $carry['count'] + $item['lat']) / $count,
        'lng' => ($carry['lng'] * $carry['count'] + $item['lng']) / $count,
        'total_tiv_2011' => $total_tiv_2011,
        'avg_tiv_2011' => $total_tiv_2011 / $count,
        'total_tiv_2012' => $total_tiv_2012,
        'avg_tiv_2012' => $total_tiv_2012 / $count,
        'diff_2011_2012' => $total_tiv_2012 / $total_tiv_2011 - 1,
    ];
};

$result = MapReduce::create()
    ->setInput($rows)
    ->setMapper($mapper)
    ->setGroupBy(static fn (array $item): string => strtolower($item['state'] . ' ' . $item['county']))
    ->setReducer($reducer)
    ->setOutput(new JsonLineWriter())
    ->run();

var_export($result);
