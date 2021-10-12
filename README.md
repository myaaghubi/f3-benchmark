# F3-Benchmark
 A benchmark & debug helper plugin for Fat-Free framework.

![screenshot](screenshots/shot1.jpg?raw=true "F3 Benchmark Screentshot")

### Install

Just run the below code:

```
composer require myaghobi/f3-benchmark
```

### Usage

The plugin works if `DEBUG>=3`, otherwise, it goes disable to prevent security issues and with no resource usage.
Initiate `Benchmark` after your config file:

``` php
$f3->config('config.ini');
\Benchmark::instance();
...
```
or
``` php
// $f3->set('UI', 'ui/');
$f3->set('UI', YOUR_UI_PATH);
// $f3->set('DEBUG', 3);
$f3->set('DEBUG', YOUR_DEBUG_LEVEL);

\Benchmark::instance();
```
The plugin reserve `benchmark` in `f3`, after initiate, you can make your checkpoints:
``` php
$f3->get('benchmark')->checkPoint('myTag');
...
```
For `DEBUG<3`, the plugin goes disable with no resource usage so it's not necessary to remove your checkpoints in production mode.

## License

You are allowed to use this plugin under the terms of the GNU General Public License version 3 or later.

Copyright (C) 2021 Mohammad Yaghobi
