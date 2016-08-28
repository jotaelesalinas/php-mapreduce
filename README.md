# php-local-mapreduce

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

PSR-4 compliant library to easily do map-reduce locally, the old-fashioned utterly-unsexy non-distributed way.

## Install

Via Composer

``` bash
$ composer require jotaelesalinas/php-local-mapreduce
```

## Usage

A very simple example were we have a CSV with the full order history of an hypothetical online shop
and we want to know the average order value.

``` php
use JLSalinas\MapReduce\MapReduce;
use JLSalinas\RWGen\Readers\Csv;

$mapper = function($order) {
    return [
        'orders'  => 1,
        'revenue' => $order['total_amount']
    ];
};

$reducer = function ($carry, $item) {
    if ( is_null($carry) ) {
        $item['avg_order_value'] = $item['revenue'] / $item['orders'];
        return $item;
    }
    
    $orders          = $carry['orders'] + $item['orders'];
    $revenue         = $carry['revenue'] + $item['revenue'];
    $avg_order_value = $revenue / $orders;
    
    return compact('orders', 'revenue', 'avg_order_value');
};

$mapreducer = (new MapReduce(new Csv('/path/to/file.csv')))
                ->map($mapper)
                ->reduce($reducer)
                ->run();
```

Now an example where we also read from a CSV with the order history of an online shop,
writing the output to another CSV, and we want to know _for each customer_:
- date of the last order
- number of orders since the beginning
- amount spent since the beginning
- average order value since the beginning
- number of orders in the last 12 months
- amount spent in the last 12 months
- average order value in the last 12 months


``` php
use JLSalinas\MapReduce\MapReduce;
use JLSalinas\RWGen\Readers\Csv;
use JLSalinas\RWGen\Writers\Csv;

$mapper = function($order) {
    return [
        'customer_id'      => $order['customer_id'],
        'date_last_order'  => $order['date'],
        'orders'           => 1,
        'orders_last_12m'  => strtotime($order['date']) > strtotime('-12 months') ? 1 : 0,
        'revenue'          => $order['total_amount'],
        'revenue_last_12m' => strtotime($order['date']) > strtotime('-12 months') ? $order['total_amount'] : 0
    ];
};

$reducer = function ($carry, $item) {
    if ( is_null($carry) ) {
        $item['avg_revenue'] = $item['revenue'] / $item['orders'];
        $item['avg_revenue_last_12m'] = $item['orders_last_12m'] ? $item['revenue_last_12m'] / $item['orders_last_12m'] : 0;
        return $item;
    }
    
    $date_last_order      = max($carry['date_last_order'], $item['date_last_order']);
    $orders               = $carry['orders'] + $item['orders'];
    $orders_last_12m      = $carry['orders_last_12m'] + $item['orders_last_12m'];
    $revenue              = $carry['revenue'] + $item['revenue'];
    $revenue_last_12m     = $carry['revenue_last_12m'] + $item['revenue_last_12m'];
    $avg_revenue          = $revenue / $orders;
    $avg_revenue_last_12m = $orders_last_12m > 0 ? $revenue_last_12m / $orders_last_12m : 0;
    
    return compact('date_last_order', 'orders', 'orders_last_12m', 'revenue', 'revenue_last_12m', 'avg_revenue', 'avg_revenue_last_12m');
};

$mapreducer = (new MapReduce(new Csv('/path/to/input_file.csv')))
                ->map($mapper)
                ->reduce($reducer, true)
                ->writeTo(new Csv('/path/to/output_file.csv'))
                ->run();
```

You can see more elaborated examples under the folder [docs](docs).

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please DM me to [@jotaelesalinas](http://twitter.com/jotaelesalinas) instead of using the issue tracker.

## To do

- [ ] Tests events in MapReduce
- [ ] Add docs
    - [ ] input
    - [ ] creation of a custom reader
        - [ ] Mention that it is possible to work both with local and cloud data by implementing the right Reader/Writer, possibly using [Flysystem by Frank de Jonge](https://github.com/thephpleague/flysystem).
    - [ ] readeradapter
    - [ ] map function
    - [ ] reduce function
    - [ ] grouping
    - [ ] event handling
    - [ ] output
    - [ ] creation of a custom writer
- [ ] Insurance example
    - [ ] adapt to new library
    - [ ] add insured values
    - [ ] improve kml output (info, markers)
- [ ] (Enhancement) `withBuffer(int $max_size)` to allow mapping and reducing in batches
    - [ ] (Enhancement) Multithread (requires pthreads)
        - [ ] (Enhancement) Pipelining: map while reading, reduce while mapping
- [ ] Move this to-do list to [Issues](https://github.com/jotaelesalinas/php-local-mapreduce/issues)
- [ ] Create milestones in GitHub for: sequential (v1.0), buffered (v1.1), multithreaded (v1.2), pipelined (v1.3).

## Credits

- [José Luis Salinas][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/jotaelesalinas/php-local-mapreduce.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/jotaelesalinas/php-local-mapreduce/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/jotaelesalinas/php-local-mapreduce.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/jotaelesalinas/php-local-mapreduce.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jotaelesalinas/php-local-mapreduce.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/jotaelesalinas/php-local-mapreduce
[link-travis]: https://travis-ci.org/jotaelesalinas/php-local-mapreduce
[link-scrutinizer]: https://scrutinizer-ci.com/g/jotaelesalinas/php-local-mapreduce/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/jotaelesalinas/php-local-mapreduce
[link-downloads]: https://packagist.org/packages/jotaelesalinas/php-local-mapreduce
[link-author]: https://github.com/jotaelesalinas
[link-contributors]: ../../contributors
