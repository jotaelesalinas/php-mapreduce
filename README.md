# php-mapreduce

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

PHP PSR-4 compliant library to easily do non-distributed local map-reduce.

## Install

Via Composer

``` bash
$ composer require jotaelesalinas/php-mapreduce
```

## Basic usage

```php
require_once __DIR__ . '/vendor/autoload.php';

$source = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$mapper = fn($item) => $item * 2;
$reducer = fn($carry, $item) => ($carry ?? 0) + $item;

$result = MapReduce\MapReduce::create()
    ->setInput($source)
    ->setMapper($mapper)
    ->setReducer($reducer)
    ->run();

print_r($result);
```

The output is:

```
Array
(
    [0] => 110
)
```

### Filters

```php
$odd_numbers = fn($item) => $item % 2 === 0;
$greater_than_10 = fn($item) => $item > 10;

$result = MapReduce\MapReduce::create([
        "input" => $source, 
        "mapper" => $mapper, 
        "reducer" => $reducer, 
    ])
    // only odd numbers are passed to the mapper function
    ->setPreFilter($odd_numbers)
    // only numbers greater than 10 are passed to the reducer function
    ->setPostFilter($greater_than_10)
    ->run();

print_r($result);
```

The output is:

```
Array
(
    [0] => 48
)
```

### Groups

Group by the value of a field (valid for arrays and objects):

```php
$source = [
    [ "first_name" => "Susanna", "last_name" => "Connor",  "member" => "y", "age" => 20],
    [ "first_name" => "Adrian",  "last_name" => "Smith",   "member" => "n", "age" => 22],
    [ "first_name" => "Mike",    "last_name" => "Mendoza", "member" => "n", "age" => 24],
    [ "first_name" => "Linda",   "last_name" => "Duguin",  "member" => "y", "age" => 26],
    [ "first_name" => "Bob",     "last_name" => "Svenson", "member" => "n", "age" => 28],
    [ "first_name" => "Nancy",   "last_name" => "Potier",  "member" => "y", "age" => 30],
    [ "first_name" => "Pete",    "last_name" => "Adams",   "member" => "n", "age" => 32],
    [ "first_name" => "Susana",  "last_name" => "Zommers", "member" => "y", "age" => 34],
    [ "first_name" => "Adrian",  "last_name" => "Deville", "member" => "n", "age" => 36],
    [ "first_name" => "Mike",    "last_name" => "Cole",    "member" => "n", "age" => 38],
    [ "first_name" => "Mike",    "last_name" => "Angus",   "member" => "n", "age" => 40],
];

// mapper does nothing
$mapper = fn($x) => $x;

// number of persons and sum of ages
$reduceAgeSum = function ($carry, $item) {
    if (is_null($carry)) {
        return [
            'count' => 1,
            'age_sum' => $item['age'],
        ];
    }
    
    $count = $carry['count'] + 1;
    $age_sum = $carry['age_sum'] + $item['age'];
    
    return compact('count', 'age_sum');
};

$result = MapReduce\MapReduce::create([
        "input" => $source, 
        "mapper" => $mapper, 
        "reducer" => $reduceAgeSum, 
    ])
    // group by field 'member'
    ->setGroupBy('member')
    ->run();

print_r($result);
```

The output is:

```
Array
(
    [y] => Array
        (
            [count] => 4
            [age_sum] => 110
        )

    [n] => Array
        (
            [count] => 7
            [age_sum] => 220
        )

)
```

Group by a custom value generated from each item:

```php
$closestTen = fn($x) => floor($x['age'] / 10) * 10;

$result = MapReduce\MapReduce::create([
        "input" => $source, 
        "mapper" => $mapper, 
        "reducer" => $reduceAgeSum, 
    ])
    // group by age ranges of 10
    ->setGroupBy($closestTen)
    ->run();

print_r($result);
```

The output is:

```
Array
(
    [20] => Array
        (
            [count] => 5
            [age_sum] => 120
        )

    [30] => Array
        (
            [count] => 5
            [age_sum] => 170
        )

    [40] => Array
        (
            [count] => 1
            [age_sum] => 40
        )

)
```

### Input

`MapReduce` accepts as input any data of type `iterable`. That means, arrays and traversables, e.g. generators.

This is very handy when reading from big files that do not fit in memory.

```php
$result = MapReduce\MapReduce::create([
        "mapper" => $mapper, 
        "reducer" => $reducer, 
    ])
    ->setInput(csvReadGenerator('myfile.csv'))
    ->run();
```

Multiple inputs can be specified, passing several arguments to `setInput()`, as long as all of them are iterable:

```php
$result = MapReduce\MapReduce::create([
        "mapper" => $mapper, 
        "reducer" => $reducer, 
    ])
    ->setInput($arrayData, csvReadGenerator('myfile.csv'))
    ->run();
```

### Output

`MapReduce` can be configured to write the final data to one or more destinations.

Each destination has to be a `Generator`:

```php
$result = MapReduce\MapReduce::create([
        "mapper" => $mapper, 
        "reducer" => $reducer, 
    ])
    ->setOutput(csvWriteGenerator('results.csv'))
    ->run();
```

Multiple outputs can be specified as well:

```php
$result = MapReduce\MapReduce::create([
        "mapper" => $mapper, 
        "reducer" => $reducer, 
    ])
    ->setOutput(csvWriteGenerator('results.csv'), consoleGenerator())
    ->run();
```

To help working with input and output generators, it is recommended to use the package [`jotaelesalinas/php-generators`](http://github.com/jotaelesalinas/php-generators), but it is not mandatory.

You can see more elaborated examples under the folder [examples](examples).

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

- [ ] Add events to help see progress in large batches
- [ ] Add docs
- [ ] Insurance example
    - [ ] adapt to new library
    - [ ] add insured values
    - [ ] improve kml output (info, markers)

## Credits

- [José Luis Salinas][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/jotaelesalinas/php-mapreduce.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/jotaelesalinas/php-mapreduce/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/jotaelesalinas/php-mapreduce.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/jotaelesalinas/php-mapreduce.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jotaelesalinas/php-mapreduce.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/jotaelesalinas/php-mapreduce
[link-travis]: https://travis-ci.org/jotaelesalinas/php-mapreduce
[link-scrutinizer]: https://scrutinizer-ci.com/g/jotaelesalinas/php-mapreduce/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/jotaelesalinas/php-mapreduce
[link-downloads]: https://packagist.org/packages/jotaelesalinas/php-mapreduce
[link-author]: https://github.com/jotaelesalinas
[link-contributors]: ../../contributors
