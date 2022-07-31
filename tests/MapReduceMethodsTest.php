<?php
namespace MapReduce\Tests;

use PHPUnit\Framework\TestCase;
use MapReduce\MapReduce;

function accessProtected($obj, $prop)
{
    $reflection = new \ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue($obj);
}

class MapReduceMethodsTest extends TestCase
{
    public function testInputAcceptsIterable()
    {
        $arr1 = [1,2,3,4,5];
        $arr2 = [6,7,8];

        $mr = new MapReduce();
        $mr->setInput($arr1);
        $input = accessProtected($mr, 'input');
        $this->assertNotNull($input);
        $this->assertIsArray($input);
        $this->assertTrue(count($input) === 1);
        $this->assertEquals($input[0], $arr1);

        $mr = new MapReduce();
        $mr->setInput($arr1, $arr2);
        $input = accessProtected($mr, 'input');
        $this->assertNotNull($input);
        $this->assertIsArray($input);
        $this->assertTrue(count($input) === 2);
        $this->assertEquals($input[0], $arr1);
        $this->assertEquals($input[1], $arr2);
    }
    
    public function testGroupByToFunction()
    {
        // groupBy can be int|string|callable|null
        $mr = new MapReduce();

        $mr->setGroupBy(1);
        $groupBy = accessProtected($mr, 'groupBy');
        $this->assertIsCallable($groupBy);

        $mr->setGroupBy('key1');
        $groupBy = accessProtected($mr, 'groupBy');
        $this->assertIsCallable($groupBy);

        $mr->setGroupBy(null);
        $groupBy = accessProtected($mr, 'groupBy');
        $this->assertIsCallable($groupBy);
    }
}
