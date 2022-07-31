<?php

namespace MapReduce\Tests;

use MapReduce\MapReduce;

$adapterDob2Age = function (iterable $original) {
    foreach ($original as $item) {
        // let´s say we are in the year 2020
        $age = isset($item['birthday']) ? 2020 - explode('-', $item['birthday'])[0] : null;
        if ($age !== null) {
            $item['age'] = $age;
        }
        yield $item;
    }
};

class MapReduceRunTest extends MapReduceRunTestBase
{
    public function testAges()
    {
        $result = MapReduce::create()
            ->setInput($this->data1)
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAgeSum)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 11, "sum" => 330]]);
    }
    
    public function testAgesWithAdapter()
    {
        global $adapterDob2Age;
        $result = MapReduce::create()
            ->setInput($adapterDob2Age($this->data3))
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAgeSum)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 5, "sum" => 190]]);
    }

    public function testAgesMulti()
    {
        global $adapterDob2Age;
        $result = MapReduce::create()
            ->setInputMulti([$this->data1, $this->data2, $adapterDob2Age($this->data3)])
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAgeSum)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 20, "sum" => 689]]);
    }
}
