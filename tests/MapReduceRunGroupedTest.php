<?php

namespace JLSalinas\MapReduce\Tests;

use JLSalinas\MapReduce\MapReduce;

class MapReduceRunGroupedTest extends MapReduceRunTestBase
{
    public function testGroupByStringArray()
    {
        $result = MapReduce::create()
            ->setInput($this->data1)
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->setGroupBy('gender')
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals(count(array_keys($result)), 2);
        $this->assertArrayHasKey('f', $result);
        $this->assertArrayHasKey('m', $result);
        $this->assertEquals($result['f'], ["count" => 4, "sum" => 110]);
        $this->assertEquals($result['m'], ["count" => 7, "sum" => 220]);
    }
    
    public function testGroupByStringObject()
    {
        $data1 = json_decode(json_encode($this->data1));

        $result = MapReduce::create()
            ->setInput($data1)
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->setGroupBy('gender')
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals(count(array_keys($result)), 2);
        $this->assertArrayHasKey('f', $result);
        $this->assertArrayHasKey('m', $result);
        $this->assertEquals($result['f'], ["count" => 4, "sum" => 110]);
        $this->assertEquals($result['m'], ["count" => 7, "sum" => 220]);
    }
    
    public function testGroupByFunc()
    {
        $funcAdapter = $this->adapterDob2Age;

        $result = MapReduce::create()
            ->setInputMulti([$this->data1, $this->data2, $funcAdapter($this->data3)])
            ->setMapper($this->mapEq)
            ->setReducer($this->reduceAge)
            ->setGroupBy(fn($x) => floor($x['age'] / 10) * 10)
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals(array_keys($result), [20, 30, 40, 50]);
        $this->assertEquals($result[20], ["count" => 5, "sum" => 120]);
        $this->assertEquals($result[50], ["count" => 1, "sum" => 50]);
    }
}
