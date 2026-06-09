<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\DataStreams\Json\JsonLinesReader;
use JLSalinas\DataStreams\Json\JsonLinesWriter;
use JLSalinas\SimpleMapReduce\MapReduce;
use JLSalinas\SimpleMapReduce\Writer;
use PHPUnit\Framework\Assert;

use function expect;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class JsonLinesWriterAdapter implements Writer
{
    public function __construct(private readonly JsonLinesWriter $writer)
    {
    }

    public function write(mixed $item): void
    {
        $this->writer->write($item);
    }

    public function close(): void
    {
        $this->writer->close();
    }
}

it('works with php-data-streams readers and writers', function (): void {
    $inputFile = tempnam(sys_get_temp_dir(), 'mapreduce-input-');
    $outputFile = tempnam(sys_get_temp_dir(), 'mapreduce-output-');

    if ($inputFile === false || $outputFile === false) {
        Assert::fail('Could not create temporary files.');
    }

    try {
        file_put_contents($inputFile, <<<'JSONL'
{"type":"a","value":1}
{"type":"b","value":2}
{"type":"a","value":3}
JSONL);

        $result = MapReduce::create()
            ->input(new JsonLinesReader($inputFile))
            ->map(fn (mixed $item): mixed => $item)
            ->groupBy('type')
            ->reduce(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item['value'])
            ->output(new JsonLinesWriterAdapter(new JsonLinesWriter($outputFile)))
            ->run();

        expect($result)->toBe([
            'a' => 4,
            'b' => 2,
        ]);

        expect(preg_replace("/\r\n|\r/", "\n", (string) file_get_contents($outputFile)))->toBe(<<<'JSONL'
4
2

JSONL);
    } finally {
        unlink($inputFile);
        unlink($outputFile);
    }
});
