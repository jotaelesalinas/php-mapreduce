<?php
/**
 * pets.php
 *
 * In this example, we use Map/Reduce to generate first a single result and then a grouped result.
 *
 * Let's imagine we work at a veterinary and we want to do some simple statistics.
 */

/*
 * Require Composer's autoloader
 */
require_once __DIR__ . '/../../vendor/autoload.php';

/*
 * Dependencies
 */
use JLSalinas\MapReduce\MapReduce;
use JLSalinas\MapReduce\ReaderAdapter;
use League\Event\Emitter;
use League\Event\Event;

/*
 * Datasources
 *
 * Recently, the owner purchased a new POS in the cloud, but he still stores all previous data in a local CSV file,
 * so we need two different datasources.
 * 
 * Here we use two arrays of arrays, but they should be one custom JLSalinas\RWGen\Reader for the external API and
 * one JLSalinas\RWGen\Readers\Csv for the local CSV file.
 */
$pets_cloud = [
	  [ 'name' => 'Bono',  'species' => 'dog',     'birthday' => '2010-01-01', 'visits' => '3', 'revenue' =>  90.00 ]
	, [ 'name' => 'Lenny', 'species' => 'cat',     'birthday' => '2005-02-12', 'visits' => '2', 'revenue' =>  65.00 ]
	, [ 'name' => 'Bruce', 'species' => 'dog',     'birthday' => '2008-03-31', 'visits' => '4', 'revenue' => 115.00 ]
];
$pets_csv = [
	  [ 'name' => 'Sting', 'species' => 'cat',     'birthday' => '2010-04-06', 'visits' => '2', 'revenue' =>  70.00 ]
	, [ 'name' => 'Jay',   'species' => 'papagay', 'birthday' => '2012-05-16', 'visits' => '3', 'revenue' =>  80.00 ]
	, [ 'name' => 'Ben',   'species' => 'dog',     'birthday' => '2009-08-16', 'visits' => '4', 'revenue' => 120.00 ]
	, [ 'name' => 'Willy', 'species' => 'cat',     'birthday' => '2010-04-06', 'visits' => '2', 'revenue' =>  70.00 ]
	, [ 'name' => 'Matt',  'species' => 'papagay', 'birthday' => '2012-05-16', 'visits' => '3', 'revenue' =>  80.00 ]
	, [ 'name' => 'Bobby', 'species' => 'dog',     'birthday' => '2009-08-16', 'visits' => '5', 'revenue' => 120.00 ]
	, [ 'name' => 'Rocky', 'species' => 'turtle',  'birthday' => '2012-05-16', 'visits' => '2', 'revenue' =>  90.00 ]
];

/*
 * The mapping function
 * 
 * This function accepts one item from the datasource and returns another item with the strictly necessary
 * data to be reduced.
 */
$mapper = function ($pet) {
    $species       = $pet['species']; // only used when grouping
    $num_animals   = 1;
    $total_age     = (@time('Europe/Vienna') - strtotime($pet['birthday'])) / 60 / 60 / 24 / 365.25;
    $total_visits  = $pet['visits'];
    $total_revenue = $pet['revenue'];
	return compact('species', 'num_animals', 'total_age', 'total_visits', 'total_revenue');
};

/*
 * The reduce function
 * 
 * This function an array of already mapped items and returns one.
 *
 * It has to be possible to apply the reduce function on the value returned by this function
 * (possibly together with other mapped items or other values returned also by this function).
 * 
 * If R is the value returned by this function, this condition mst alway be true: reduce(R) === R
 */
$reducer = function ($pets) {
    return array_reduce($pets, function ($carry, $item) {
        if ( is_null($carry) ) {
            $item['avg_age']            = $item['total_age'] / $item['num_animals'];
            $item['avg_visits']         = $item['total_visits'] / $item['num_animals'];
            $item['avg_revenue_animal'] = $item['total_revenue'] / $item['num_animals'];
            $item['avg_revenue_visit']  = $item['total_revenue'] / $item['total_visits'];
            return $item;
        }
        
        $species            = $carry['species'];
        $num_animals        = $carry['num_animals'] + $item['num_animals'];
        $total_age          = $carry['total_age'] + $item['total_age'];
        $avg_age            = $total_age / $num_animals;
        $total_visits       = $carry['total_visits'] + $item['total_visits'];
        $avg_visits         = $total_visits / $num_animals;
        $total_revenue      = $carry['total_revenue'] + $item['total_revenue'];
        $avg_revenue_animal = $total_revenue / $num_animals;
        $avg_revenue_visit  = $total_revenue / $total_visits;
        
        return compact('species', 'num_animals', 'total_age', 'avg_age', 'total_visits', 'avg_visits', 'total_revenue', 'avg_revenue_animal', 'avg_revenue_visit');
    });
};

/*
 * The output
 *
 * It has to be a Generator, or behave like one (just having a send() method).
 */
class LogToConsole {
	public function send ($data) {
		if ( !is_null($data) ) {
			print_r($data);
		}
	}
}

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

/*
 * The incompatible datasource
 *
 * Now that we have our map and reduce functions tailored for the data we were provided with,
 * our boss shows up with some really old CSV files and, of course, wants that data integrated
 * in the statistics.
 *
 * Note that in this new file some columns have different names (type for species, birth for birthday and amount
 * for revenue) while some columns have different format (birthday using English date notation MM/DD/YYYY instead
 * of standard YYYY-MM-DD and revenue being a string with the dollar sign instead of just as a number.)
 */
$pets_ancient = [
	  [ 'name' => 'Sid',   'type' => 'dog', 'birth' => '1/1/2002',  'visits' => '2', 'amount' => '$90.00' ]
	, [ 'name' => 'Anne',  'type' => 'cat', 'birth' => '2/12/2005', 'visits' => '1', 'amount' => '$65.00' ]
	, [ 'name' => 'Andy',  'type' => 'cat', 'birth' => '3/31/2004', 'visits' => '4', 'amount' => '$115.00' ]
	, [ 'name' => 'Spock', 'type' => 'dog', 'birth' => '4/20/2003', 'visits' => '4', 'amount' => '$100.00' ]
	, [ 'name' => 'Frodo', 'type' => 'dog', 'birth' => '5/4/2004',  'visits' => '4', 'amount' => '$80.00' ]
];

/*
 * The adapter
 *
 * Instead of modifying the data in the origin (it may not be possible if we talk about remote datasources instead of
 * a local CSV file), what we do is "adapting" the data to the structure we expect.
 *
 * To do so, we create a new JLSalinas\MapReduce\ReaderAdapter object with two arguments: the datasource and a
 * transformation function. Think of it as pre-mapping the data.
 */
$adapter_ancient = new ReaderAdapter($pets_ancient, function ($item) {
    // sample line from the other sources:
    // [ 'name' => 'Sting', 'species' => 'cat', 'birthday' => '2010-04-06', 'visits' => '2', 'revenue' => 70.00 ]
    
    preg_match('/\d+\.\d+?/', $item['amount'], $m); // extract the digits with the dot and ignore everything else
    return [
        // we know that we don't need 'name', so we can safely skip it
          'species'  => $item['type']
        , 'birthday' => date('Y-m-d', strtotime($item['birth']))
        , 'visits'   => $item['visits']
        , 'revenue'  => isset($m[0]) ? $m[0] / 1 : 0
    ];
});

echo "===============================================================\n";
echo "CLASSIC REDUCE - THERE CAN BE ONLY ONE\n";
echo "===============================================================\n";
$mapreducer = (new MapReduce($mapper, $reducer))
                ->readFrom($pets_cloud)
                ->readFrom($pets_csv)
                // ->readFrom($pets_ancient) would fail because the structure is not the expected
                ->readFrom($adapter_ancient) // that's why we use an adapter
                ->writeTo(new LogToConsole())
                ->notifyEventsTo($emitter)
                ->run();
echo "\n";

echo "===============================================================\n";
echo "REDUCE GROUPING BY FIRST COLUMN\n";
echo "===============================================================\n";
$mapreducer = (new MapReduce($mapper, $reducer, true))
                ->readFrom($pets_cloud)
                ->readFrom($pets_csv)
                ->readFrom($adapter_ancient)
                ->writeTo(new LogToConsole())
                ->notifyEventsTo($emitter)
                ->run();
echo "\n";
