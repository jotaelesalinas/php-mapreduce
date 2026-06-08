# Insurance example

This example shows a local aggregation workflow over insurance-like data.

It is intentionally self-contained so it can be run without any optional stream
packages.

## What it demonstrates

- mapping raw rows into a reduced shape,
- grouping by a derived key,
- calculating grouped statistics,
- writing the result through a custom `Writer`.

## Run

```bash
php examples/insurance/insurance.php
```
