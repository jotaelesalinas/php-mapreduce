<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use JLSalinas\SimpleMapReduce\MapReduce;
use JLSalinas\SimpleMapReduce\Writer;

final class ConsoleWriter implements Writer
{
    public function write(mixed $item): void
    {
        echo json_encode($item, JSON_THROW_ON_ERROR) . PHP_EOL;
    }

    public function close(): void
    {
        echo "done" . PHP_EOL;
    }
}

$pets = [
    ['name' => 'Bono', 'species' => 'dog', 'revenue' => 90.00],
    ['name' => 'Lenny', 'species' => 'cat', 'revenue' => 65.00],
    ['name' => 'Bruce', 'species' => 'dog', 'revenue' => 115.00],
    ['name' => 'Sting', 'species' => 'cat', 'revenue' => 70.00],
];

$result = MapReduce::crear()
    ->entrada($pets)
    ->map(static fn (mixed $pet): mixed => [
        'species' => $pet['species'],
        'revenue' => $pet['revenue'],
        'count' => 1,
    ])
    ->agrupar(static fn (mixed $item): string => $item['species'])
    ->reduce(static fn (mixed $carry, mixed $item): mixed => $carry === null
        ? $item
        : [
            'species' => $carry['species'],
            'revenue' => $carry['revenue'] + $item['revenue'],
            'count' => $carry['count'] + $item['count'],
        ])
    ->salida(new ConsoleWriter())
    ->ejecutar();

var_dump($result);
