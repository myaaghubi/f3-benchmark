# F3-Benchmark
 A benchmark helper plugin for Fat-Free framework.

![screenshot](screenshot/shot.PNG?raw=true "F3 Benchmark Screentshot")

### Install

Just copy `benchmark.php` into your `lib/` folder.

### Usage

This plugin will work if `DEBUG>=3`, so just initiate it after your config file or after `$f3->set('DEBUG', YOUR_DEBUG_LEVEL);`.

``` php
$f3->config('config.ini');
Benchmark::instance();
...
```

## License

You are allowed to use this plugin under the terms of the GNU General Public License version 3 or later.

Copyright (C) 2020 Mohammad Yaghobi