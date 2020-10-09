# F3-Benchmark
 A benchmark helper plugin for Fat-Free framework.

![screenshot](screenshots/shot1.jpg?raw=true "F3 Benchmark Screentshot")
![screenshot-full](screenshots/shot2.jpg?raw=true "F3 Benchmark Screentshot Full")

### Install

If you use composer, run the below code:

```
composer require myaghobi/f3-benchmark
```
or

Just copy `benchmark.php` into your `lib/` folder.

### Usage

The plugin works if `DEBUG>=3`, otherwise, it goes disable with no resource usage.
Initiate `Benchmark` after your config file:

``` php
$f3->config('config.ini');
Benchmark::instance();
...
```
or
``` php
$f3->set('DEBUG', YOUR_DEBUG_LEVEL);
Benchmark::instance();
```
Then you can make your checkpoint:
``` php
$f3->get('benchmark')->checkPoint('myTag');
```
## License

You are allowed to use this plugin under the terms of the GNU General Public License version 3 or later.

Copyright (C) 2020 Mohammad Yaghobi
