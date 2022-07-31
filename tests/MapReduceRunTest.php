<?php

namespace JLSalinas\MapReduce\Tests;

use JLSalinas\MapReduce\MapReduce;

class MapReduceRunTest extends MapReduceRunTestBase
{
    public function testAges()
    {
        $result = MapReduce::create()
            ->setInput($this->data1)
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 11, "sum" => 330]]);
    }
    
    public function testAgesWithAdapter()
    {
        $funcAdapter = $this->adapterDob2Age;
        $result = MapReduce::create()
            ->setInput($funcAdapter($this->data3))
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 5, "sum" => 190]]);
    }

    public function testAgesMulti()
    {
        $funcAdapter = $this->adapterDob2Age;
        $result = MapReduce::create()
            ->setInputMulti([$this->data1, $this->data2, $funcAdapter($this->data3)])
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals($result, [["count" => 20, "sum" => 689]]);
    }
}
