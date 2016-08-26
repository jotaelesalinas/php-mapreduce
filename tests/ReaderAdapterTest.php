<?php

namespace JLSalinas\MapReduce\Tests;

use JLSalinas\MapReduce\ReaderAdapter;
use JLSalinas\RWGen\Readers\Csv;

class ReaderAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected static $data = [1, 2, 3];
    
    public function testAcceptsArrayOrTraversable()
    {
        $ra1 = new ReaderAdapter(self::$data, function ($item) {
            return $item;
        });
        $this->assertTrue(!!$ra1);
        
        $f = tempnam('/tmp', 'tmp');
        $ra2 = new ReaderAdapter(new Csv($f), function ($item) {
            return $item;
        });
        $this->assertTrue(!!$ra2);
        @unlink($f);
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ReaderAdapter: $reader is not an array nor Traversable.
     */
    public function testWrongReaderThrowsException()
    {
        $ra = new ReaderAdapter(new \DateTime(), function ($item) {
            return $item;
        });
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Transform function must accept one parameter.
     */
    public function testTransformWithNoParameters()
    {
        $f = tempnam('/tmp', 'tmp');
        $ra = new ReaderAdapter(new Csv($f), function () {
            return [1,2,3];
        });
        @unlink($f);
    }
    
    /**
     * @expectedException Exception
     * @expectedExceptionMessage Input file does not exist: adsfasdf.csv
     */
    public function testReaderFileDoesNotExistThrowsException()
    {
        $ra = new ReaderAdapter(new Csv('adsfasdf.csv'), function ($item) {
            return $item;
        });
    }
    
    public function testEquality()
    {
        $ra = new ReaderAdapter(self::$data, function ($item) {
            return $item;
        });
        $ra2 = [];
        foreach ($ra as $item) {
            $ra2[] = $item;
        }
        $this->assertTrue(count(self::$data) == count($ra2));
        foreach (self::$data as $k => $v) {
            $this->assertTrue($v === $ra2[$k]);
        }
    }
    
    public function testDouble()
    {
        $ra = new ReaderAdapter(self::$data, function ($item) {
            return $item * 2;
        });
        $ra2 = [];
        foreach ($ra as $item) {
            $ra2[] = $item;
        }
        $this->assertTrue(count(self::$data) == count($ra2));
        foreach (self::$data as $k => $v) {
            $this->assertTrue($v === $ra2[$k] / 2);
        }
    }
    
    /**
     * @expectedException TypeError
     */
    public function testSecondArgumentNotCallable()
    {
        $ra = new ReaderAdapter(self::$data, 123);
    }
}
