<?php

namespace JLSalinas\MapReduce\Tests;

use JLSalinas\MapReduce\MapReduce;

class MapReduceRunFilteredTest extends MapReduceRunTestBase
{
    public function testPreFilterByGender()
    {
        $funcAdapter = $this->adapterDob2Age;
        $result = MapReduce::create()
            ->setInputMulti([$this->data1, $this->data2, $funcAdapter($this->data3)])
            ->setPreFilter(fn($x) => $x['gender'] === 'f')
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 7, "sum" => 222]]);
    }
    
    public function testPreFilterByAge()
    {
        $funcAdapter = $this->adapterDob2Age;
        $result = MapReduce::create()
            ->setInputMulti([$this->data1, $this->data2, $funcAdapter($this->data3)])
            ->setMapper($this->mapEq)
            ->setPostFilter(fn($x) => $x['age'] > 40)
            ->setReducer($this->reduceAge)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 3, "sum" => 136]]);
    }
}
