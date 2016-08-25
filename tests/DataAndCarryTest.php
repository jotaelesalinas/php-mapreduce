<?php

namespace JLSalinas\MapReduce\Tests;
use JLSalinas\MapReduce\DataAndCarry;

class DataAndCarryTest extends \PHPUnit_Framework_TestCase
{
    protected static $data = [1, 2, 3];
    protected static $carry = ['a', 'b'];
    
    public function testDataWithNull ()
    {
        $dc1 = new DataAndCarry(1);
        $this->assertTrue($dc1->data === 1);
        $this->assertTrue($dc1->carry === null);
        
        $dc2 = new DataAndCarry(2, null);
        $this->assertTrue($dc2->data === 2);
        $this->assertTrue($dc2->carry === null);
    }
    
    public function testDataWithSomething ()
    {
        $dc1 = new DataAndCarry(self::$data, self::$carry);
        $this->assertTrue(is_array($dc1->data));
        $this->assertTrue(count($dc1->data) == 3);
        $this->assertTrue($dc1->data[0] === 1);
        $this->assertTrue($dc1->data[1] === 2);
        $this->assertTrue($dc1->data[2] === 3);
        $this->assertTrue(is_array($dc1->carry));
        $this->assertTrue(count($dc1->carry) == 2);
        $this->assertTrue($dc1->carry[0] === 'a');
        $this->assertTrue($dc1->carry[1] === 'b');
    }
    
}
