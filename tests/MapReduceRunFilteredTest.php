<?php

namespace MapReduce\Tests;

use MapReduce\MapReduce;

class MapReduceRunFilteredTest extends MapReduceRunTestBase
{
    public function testPreFilterByGender()
    {
        $funcAdapter = $this->adapterDob2Age;
        $result = MapReduce::create()
            ->setInput($this->data1, $this->data2)
            ->setPreFilter(fn($x) => $x['gender'] === 'f')
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAgeSum)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 5, "sum" => 146]]);
    }
    
    public function testPreFilterByAge()
    {
        $funcAdapter = $this->adapterDob2Age;
        $result = MapReduce::create()
            ->setInput($this->data1, $this->data2)
            ->setMapper($this->mapEq)
            ->setPostFilter(fn($x) => $x['age'] >= 40)
            ->setReducer($this->reduceAgeSum)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 3, "sum" => 134]]);
    }
}
