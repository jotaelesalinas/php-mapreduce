<?php

namespace JLSalinas\MapReduce\Tests;

use JLSalinas\MapReduce\MapReduce;
use JLSalinas\RWGen\Readers\Csv;
use JLSalinas\RWGen\Writers;

class MapReduceMethodsTest extends \PHPUnit_Framework_TestCase
{
    protected static $data = [
        [ 'first_name' => 'susanna', 'last_name' => 'connor',  'gender' => 'f', 'age' => '20'],
        [ 'first_name' => 'adrian',  'last_name' => 'smith',   'gender' => 'm', 'age' => '22'],
        [ 'first_name' => 'mike',    'last_name' => 'mendoza', 'gender' => 'm', 'age' => '24'],
        [ 'first_name' => 'linda',   'last_name' => 'duguin',  'gender' => 'f', 'age' => '26'],
        [ 'first_name' => 'bob',     'last_name' => 'svenson', 'gender' => 'm', 'age' => '28'],
        [ 'first_name' => 'nancy',   'last_name' => 'potier',  'gender' => 'f', 'age' => '30'],
        [ 'first_name' => 'pete',    'last_name' => 'adams',   'gender' => 'm', 'age' => '32'],
        [ 'first_name' => 'susana',  'last_name' => 'zommers', 'gender' => 'f', 'age' => '34'],
        [ 'first_name' => 'adrian',  'last_name' => 'deville', 'gender' => 'm', 'age' => '36'],
        [ 'first_name' => 'mike',    'last_name' => 'cole',    'gender' => 'm', 'age' => '38'],
        [ 'first_name' => 'mike',    'last_name' => 'angus',   'gender' => 'm', 'age' => '40'],
    ];
    
    public function testInputAcceptsArrayOrTraversable()
    {
        $mr1 = new MapReduce(self::$data);
        $this->assertTrue(!!$mr1);
        
        $f = tempnam('/tmp', 'tmp');
        $mr2 = new MapReduce(new Csv($f));
        $this->assertTrue(!!$mr2);
        @unlink($f);
    }
    
    public function testInputAcceptsMixedArrayAndTraversable()
    {
        $f = tempnam('/tmp', 'tmp');
        $mr1 = new MapReduce(self::$data, new Csv($f));
        $this->assertTrue(!!$mr1);
        @unlink($f);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Input is not an array nor Traversable.
     */
    public function testInputFailsOnNotArrayOrTraversable()
    {
        $mr = new MapReduce(new \DateTime());
    }
    
    public function testMapAcceptsCallableWithOneParameter()
    {
        $mr1 = new MapReduce();
        $mr1->map(function ($item) {
        });
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Mapper function must accept one parameter.
     */
    public function testMapFailsOnCallableNotOneParameter()
    {
        $mr1 = new MapReduce();
        $mr1->map(function () {
        });
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException TypeError
     */
    public function testMapFailsOnNonCallable()
    {
        $mr1 = new MapReduce();
        $mr1->map(self::$data);
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException TypeError
     */
    public function testMapFailsOnNoArgument()
    {
        $mr1 = new MapReduce();
        $mr1->map();
        $this->assertTrue(!!$mr1);
    }
    
    public function testReduceAcceptsCallableWithTwoParameters()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function ($carry, $item) {
        });
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Reducer function must accept two parameters.
     */
    public function testReduceFailsOnCallableWithOneParameter()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function ($a) {
        });
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Reducer function must accept two parameters.
     */
    public function testReduceFailsOnCallableWithThreeParameters()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function ($a, $b, $c) {
        });
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Reducer function must accept two parameters.
     */
    public function testReduceFailsOnCallableWithoutParameters()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function () {
        });
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException TypeError
     */
    public function testReduceFailsOnNonCallable()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(self::$data);
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException TypeError
     */
    public function testReduceFailsOnNoArgument()
    {
        $mr1 = new MapReduce();
        $mr1->reduce();
        $this->assertTrue(!!$mr1);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Group_by must be bool, callable, numeric or string.
     */
    public function testReduceWrongGroupType()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function ($a, $b) {
        }, [123]);
    }
    
    public function testReduceGoodGroupType()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function ($a, $b) {
        }, true);
        $mr1->reduce(function ($a, $b) {
        }, 'asdf');
        $mr1->reduce(function ($a, $b) {
        }, '1');
        $mr1->reduce(function ($a, $b) {
        }, 2);
        $mr1->reduce(function ($a, $b) {
        }, function ($item) {
        });
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Group_by, when callable, must accept one parameter.
     */
    public function testReduceWrongGroupParametersWhenCallable()
    {
        $mr1 = new MapReduce();
        $mr1->reduce(function ($a, $b) {
        }, function () {
        });
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Output does not have a send() method.
     */
    public function testOutputNonClass()
    {
        $mr1 = new MapReduce();
        $mr1->writeTo(123);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Output does not have a send() method.
     */
    public function testOutputNoSendMethod()
    {
        $mr1 = new MapReduce();
        $mr1->writeTo(new \DateTime());
    }
    
    public function testOutputOk()
    {
        $mr1 = new MapReduce();
        $mr1->writeTo(new Writers\Csv('__asdf.qwre'));
    }
}
