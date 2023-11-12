<?php
/**
 * insurance.php
 *
 * In this example, we use Map/Reduce to ...
 *
 * Sample data downloaded from SpatialKey. See README.
 */

/*
 * Require Composer's autoloader
 */
require_once __DIR__ . '/../../vendor/autoload.php';

/*
 * Dependencies
 */
use JLSalinas\RWGen\Readers\Csv as CsvReader;
use JLSalinas\RWGen\Writers\Csv as CsvWriter;
use JLSalinas\RWGen\Writers\Kml;
use JLSalinas\RWGen\Writers\Console;
use JLSalinas\RWGen\Writers\ConsoleJson;
use MapReduce\MapReduce;
use League\Event\Emitter;
use League\Event\Event;

$mapper = function ($item) {
	return [
		'state'          => $item['statecode'],
		'county'         => preg_replace('/\s+/', ' ', ucwords(strtolower($item['county']))),
		'name'           => preg_replace('/\s+/', ' ', ucwords(strtolower($item['county']))) . ', ' . $item['statecode'],
		'count'          => 1,
		'lat'            => $item['point_latitude'] / 1,
		'lng'            => $item['point_longitude'] / 1,
		'total_tiv_2011' => $item['tiv_2011'] / 1,
		'avg_tiv_2011'   => $item['tiv_2011'] / 1,
		'total_tiv_2012' => $item['tiv_2012'] / 1,
		'avg_tiv_2012'   => $item['tiv_2012'] / 1,
		'diff_2011_2012' => $item['tiv_2012'] / $item['tiv_2011'] - 1,
	];
};

// $new is the new data to be aggregated
// $carry is the previous data or null if this is the first call
// returns the data to be passed to the new call of the function or to be exported if this is the last call
$reducer = function ($carry, $item) {
    if (is_null($carry)) {
        return $item;
    }
	$count = $carry['count'] + $item['count'];
	return [
		'state'          => $carry['state'],
		'county'         => $carry['county'],
		'name'           => $carry['name'],
		'count'          => $count,
		'lat'            => ($carry['lat'] * $carry['count'] + $item['lat'] * $item['count']) / $count,
		'lng'            => ($carry['lng'] * $carry['count'] + $item['lng'] * $item['count']) / $count,
		'total_tiv_2011' => $carry['total_tiv_2011'] + $item['total_tiv_2011'],
		'avg_tiv_2011'   => ($carry['total_tiv_2011'] + $item['total_tiv_2011']) / $count,
		'total_tiv_2012' => $carry['total_tiv_2012'] + $item['total_tiv_2012'],
		'avg_tiv_2012'   => ($carry['total_tiv_2012'] + $item['total_tiv_2012']) / $count,
		'diff_2011_2012' => ($carry['total_tiv_2012'] + $item['total_tiv_2012']) / ($carry['total_tiv_2011'] + $item['total_tiv_2011']) - 1,
	];
};

$group_by = function ($item) {
	return strtolower( $item['state'] . ' ' . $item['county'] );
};

/*
 * The progess notifications
 *
 * See http://github.com/thephpleague/event for details.
 */
$emitter = new Emitter();
$emitter->addListener(MapReduce::EVENT_START, function () {
    echo "Start.\n";
});
$emitter->addListener(MapReduce::EVENT_FINISHED, function () {
    echo "Finished.\n";
});
$emitter->addListener(MapReduce::EVENT_START_INPUT, function ($ev, $name) {
    echo "-- Start input '$name'.\n";
});
$emitter->addListener(MapReduce::EVENT_FINISHED_INPUT, function ($ev, $name) {
    echo "-- Finished input '$name'.\n";
});
$emitter->addListener(MapReduce::EVENT_START_MERGE, function ($ev) {
    echo "-- Start merge.\n";
});
$emitter->addListener(MapReduce::EVENT_FINISHED_MERGE, function ($ev) {
    echo "-- Finished merge.\n";
});
$emitter->addListener(MapReduce::EVENT_START_OUTPUT, function ($ev) {
    echo "-- Start writing to output.\n";
});
$emitter->addListener(MapReduce::EVENT_FINISHED_OUTPUT, function ($ev) {
    echo "-- Finished writing to output.\n";
});
/*
$emitter->addListener(MapReduce::EVENT_MAPPED, function ($ev, $inputname, $original, $mapped) {
    echo "---- Mapped item (input '$inputname').\n";
    //echo "Original: "; var_dump($original);
    //echo "Mapped: "; var_dump($mapped);
});
$emitter->addListener(MapReduce::EVENT_REDUCED, function ($ev, $inputname, $items, $redux) {
    echo '---- ' . count($items) . " items reduced (input '$inputname').\n";
    //echo "Items: "; var_dump($items);
    //echo "Reduced: "; var_dump($redux);
});
*/

$mr = (new MapReduce(new CsvReader(__DIR__ . '/FL_insurance_sample.csv')))
        ->map($mapper)
        ->reduce($reducer, $group_by)
        ->writeTo(new CsvWriter(__DIR__ . '/output.csv', [ 'overwrite' => 1 ]))
        ->writeTo(new Kml(__DIR__ . '/output.kml', [ 'overwrite' => 1 ]))
        ->writeTo(new ConsoleJson())
        ->writeTo(new Console())
        ->run();
