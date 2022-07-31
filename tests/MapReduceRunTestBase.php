<?php

namespace JLSalinas\MapReduce\Tests;

use PHPUnit\Framework\TestCase;

class MapReduceRunTestBase extends TestCase
{
    protected $data1;
    protected $data2;
    protected $data3;

    protected $adapterDob2Age;

    protected $mapEq;
    protected $reduceAge;

    protected function setUp(): void
    {
        $this->data1 = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'people1.json'), true);
        // 11, 330
        $this->data2 = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'people2.json'), true);
        // 4, 169
        $this->data3 = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'people3.json'), true);
        // 5, 190
        // total: 20, 689

        $this->adapterDob2Age = function (iterable $original) {
            foreach ($original as $item) {
                // let´s say we are in the year 2020
                $age = isset($item['birthday']) ? 2020 - explode('-', $item['birthday'])[0] : null;
                if ($age !== null) {
                    if (is_object($item)) {
                        $item->age = $age;
                    } else {
                        $item['age'] = $age;
                    }
                }
                yield $item;
            }
        };

        $this->mapEq = function ($item) {
            if (is_object($item)) {
                return isset($item->age) ? $item : null;
            }
            return isset($item['age']) ? $item : null;
        };

        $this->reduceAge = function ($carry, $item) {
            $age = is_object($item) ? $item->age : $item['age'];
            if (is_null($carry)) {
                return [
                    'count' => 1,
                    'sum' => $age / 1,
                ];
            }
            
            $count = $carry['count'] + 1;
            $sum = $carry['sum'] + ($age / 1);
            
            return compact('count', 'sum');
        };
    }
}
