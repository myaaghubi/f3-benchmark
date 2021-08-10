<?php

/**
 * @package F3 Benchmark
 * @version 1.5.0
 * @link http://github.com/myaghobi/F3-Benchmark Github
 * @author Mohammad Yaghobi <m.yaghobi.abc@gmail.com>
 * @copyright Copyright (c) 2020, Mohammad Yaghobi
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 */

class Benchmark extends \Prefab {
  private $blockedExtensions;

  private $checkPoints;
  private $ramUsageMax;

  /**
   * these properties will help us to save performance
   * imagine to have hundreds of checkpoints, so we don't need to use count() on that big array for each new checkpoint
   * or there will be no memory usage if you are not in DEBUG mode
   */
  private $isBenchmarkEnable;
  private $lastCheckPointInMS;
  private $lastCheckPointNumber;

  private $f3;

  /**
   * Benchmark constructor
   *
   * @return void
   */
  function __construct() {
    $this->isBenchmarkEnable = false;
    $this->f3 = \Base::instance();

    $this->blockedExtensions = $this->f3->get('benchmark.BLOCKED_EXTENSIONS')?:array('css', 'js');

    if ($this->f3->get('DEBUG') >= 3) {
      $this->isBenchmarkEnable = true;
      $this->init();

      register_shutdown_function(function () {
        $url = $this->f3->get('PARAMS.0')?:'';
        $path = $url?parse_url($url)['path']:'';
        $extension = @pathinfo($path)['extension']?:'';

        if (
          (!$extension || 
            !in_array($extension, $this->blockedExtensions)) && 
          strpos($url, 'benchmark/panel-stat') === false
        ) {
          $this->enhanceExecutionTime();
          print $this->getFormattedBenchmark();
        }
      });
    }

    // you can comment below line if you don't need to call checkPoint()
    $this->f3->set('benchmark', $this);
  }


  /**
   * just initiate
   *
   * @return void
   */
  public function init() {
    // CheckPoint[]
    $this->checkPoints = array();
    $this->lastCheckPointInMS = 0;
    $this->lastCheckPointNumber = 0;

    $this->f3->route('POST /benchmark/panel-stat/ [ajax]', 
      function($f3) {
        $post = $f3->get('POST');
        $f3->set('COOKIE.benchmark_panel_last', $post['panel'], 86400);
        $f3->set('COOKIE.benchmark_panel_main', $post['main'], 86400);
      }
    );

    // this route will help if you have stored the UI dir in non-web-accessible path
    // the route works if plugin works (DEBUG>=3), so there is no security or performance concern
    $this->f3->route('GET /benchmark/theme/@type/@file',
      function($f3, $args) {
        $web = \Web::instance();
        $file = $f3->UI.'benchmark/theme/'.$args['type'].'/'.$args['file'];
        $mime = $web->mime($file);

        header('Content-Type: '.$mime);
        echo $f3->read($file);
      }
    );

    // add Start point, $this->lastCheckPointInMS == 0
    // don't worry about the place of this check point, it will fill with $_SERVER["REQUEST_TIME_FLOAT"]
    $this->checkPoint('Start');

    // check UI dir
    $this->checkUI();

    // add Benchmark Init point, $this->lastCheckPointInMS > 0
    $this->checkPoint('Benchmark Init');
  }


  /**
   * copy the template from ui dir into your UI dir if not exists
   *
   * @return void
   */
  function checkUI() {
    if (!is_dir($this->f3->UI . 'benchmark')) {
      $this->copyDir(dirname(__FILE__,1).'/ui', $this->f3->UI . '/' . 'benchmark');
    }
  }


  /**
   * add a new checkpoint
   * 
   * @return void
   */
  public function checkPoint($tag = '') {
    if (!$this->isBenchmarkEnable) {
      return;
    }

    if (empty($tag)) {
      $tag = 'Check Point ' . ($this->lastCheckPointNumber + 1);
    }

    // just trying to separate duplicate tags from each other
    $tag .= '#' . ($this->lastCheckPointNumber + 1);

    $currentTime = $this->getCurrentTime();
    $ramUsage = $this->getRamUsagePeak();

    if ($this->lastCheckPointInMS == 0) {
      $currentTime = $this->getRequestTime();
    }


    // generates a backtrace
    $backtrace = debug_backtrace();
    // shift an element off the beginning of array
    $caller = array_shift($backtrace);

    // get the file address that checkPoint() called from
    $file = $caller['file'];
    // get the line number that checkPoint() called from
    $line = $caller['line'];

    // specify calls from self class
    if (strrpos($caller['file'], __FILE__) !== false) {
      $line = 0;
      $file = '';
    }

    $this->checkPoints[$tag] = $this->makeCheckPointClass($currentTime, $ramUsage, $file, $line);

    $this->ramUsageMax = max($ramUsage, $this->ramUsageMax);

    $this->lastCheckPointInMS = $currentTime;
    $this->lastCheckPointNumber += 1;
  }


  /**
   * calculate elapsed time for each checkpoint
   *
   * @return void
   */
  public function enhanceExecutionTime() {
    // may the below loop take some time
    $currentTime = $this->getCurrentTime();

    $prevKey = '';
    $prevCP = null;
    foreach ($this->checkPoints as $key => $cp) {
      if (!empty($prevKey) && $prevCP != null) {
        $this->checkPoints[$prevKey]->time = $cp->time - $prevCP->time;
      }

      $prevKey = $key;
      $prevCP = $cp;
    }

    $this->checkPoints[$prevKey]->time = $currentTime - $prevCP->time;
  }


  /**
   * is benchmark enable
   *
   * @return bool
   */
  public function isEnable() {
    return $this->isBenchmarkEnable;
  }


  /**
   * get the last checkpoint in milliseconds
   *
   * @return int
   */
  public function getLastCheckPointInMS() {
    return $this->lastCheckPointInMS;
  }


  /**
   * get the last checkpoint number
   *
   * @return int
   */
  public function getLastCheckPointNumber() {
    return $this->lastCheckPointNumber;
  }


  /**
   * get checkpoints array
   *
   * @return array<string,object>
   */
  public function getCheckPoints() {
    return $this->checkPoints;
  }


  /**
   * get the max value of ram usage happened till now
   *
   * @return int
   */
  public function getRamUsageMax() {
    return $this->ramUsageMax;
  }


  /**
   * get the real ram usage
   *
   * @return int
   */
  public function getRamUsagePeak() {
    // true => memory_real_usage
    return memory_get_peak_usage(true);
  }


  /**
   * get the elapsed time from beginning till now in milliseconds
   *
   * @return int
   */
  public function getExecutionTime() {
    return $this->getCurrentTime() - $this->getRequestTime();
  }


  /**
   * get the request time in milliseconds
   *
   * @return int
   */
  public function getRequestTime() {
    return round($_SERVER["REQUEST_TIME_FLOAT"] * 1000);
  }


  /**
   * get the current time in milliseconds
   *
   * @return int
   */
  public function getCurrentTime() {
    $microtime = microtime(true) * 1000;
    return round($microtime);
  }


  /**
   * format bytes with kB, mB, etc. as mentioned in https://en.wikipedia.org/wiki/KB
   *
   * @param  int $size
   * @return string
   */
  public function getFormattedBytes($size = 0) {
    if ($size == 0) {
      return '0 B';
    }

    $base = log($size, 1024);
    $suffixes = array('B', 'kB', 'mB', 'gB', 'tB');

    return round(pow(1024, $base - floor($base))) . ' ' . $suffixes[floor($base)];
  }


  /**
   * get the count of all loaded files in project 
   *
   * @return int
   */
  public function getLoadedFilesCount() {
    return count(get_required_files());
  }


  /**
   * get the right tag name to show, just remove the '#' with checkpoint number
   *
   * @return int
   */
  public function getTagName($tag = '') {
    return substr($tag, 0, strrpos($tag, '#'));
  }


  /**
   * generate a log of checkpoints
   *
   * @return string
   */
  public function getDetailsLog($fullExecTime = 0) {
    if ($fullExecTime <= 0) {
      // prevent of "Division by zero"
      $fullExecTime = 1;
    }

    $str = '';
    foreach ($this->checkPoints as $key => $cp) {
      $name = $this->getTagName($key);
      if ($cp->line > 0) {
        $name = "<span title='$cp->file:$cp->line'>$name</span>";
      }

      $str .=
      "<div>" .
      $name . " => " .
      " Time: <b>$cp->time ms</b> (" . round($cp->time / $fullExecTime * 100) . "%)" .
      ", Memory: <b>" . $this->getFormattedBytes($cp->ram) . "</b>" .
      '</div>';
    }
    return $str;
  }


  /**
   * will make an anonymous class to hold data
   *
   * @param  int $time
   * @param  int $ram
   * @param  int $file
   * @param  int $line
   * @return object
   */
  public function makeCheckPointClass($time = 0, $ram = 0, $file = '', $line = 0) {
    return new class ($time, $ram, $file, $line) {
      public $time;
      public $ram;
      public $file;
      public $line;

      function __construct($time, $ram, $file, $line) {
        $this->time = $time;
        $this->ram = $ram;
        $this->file = $file;
        $this->line = $line;
      }
    };
  }


  /**
   * get full formatted benchmark log
   *
   * @return string
   */
  public function getFormattedBenchmark() {
    $fullExecTime = $this->getExecutionTime();

    // prevent of "Division by zero"
    if ($fullExecTime <= 0) {
      $fullExecTime = 1;
    }

    $this->f3->set('benchmark.output.fullExecTime',$fullExecTime);
    $this->f3->set('benchmark.output.ramUsageMax',$this->getFormattedBytes($this->ramUsageMax));
    $this->f3->set('benchmark.output.includedFilesCount',$this->getLoadedFilesCount());
    $this->f3->set('benchmark.output.points',$this->lastCheckPointNumber);
    $this->f3->set('benchmark.output.detailsLog',$this->getDetailsLog($fullExecTime));

    $this->f3->set('benchmark.output.panel.last', $this->f3->get('COOKIE.benchmark_panel_last'));
    $this->f3->set('benchmark.output.panel.main', $this->f3->get('COOKIE.benchmark_panel_main'));
    
    $template = \Template::instance()->render('benchmark/widget.htm');
    return $template;
  }


  /**
   * copy folder
   *
   * @param  string $from
   * @param  string $to
   * @return void
   */
  function copyDir($from, $to) {
    // open the source directory
    $dir = opendir($from);

    // Make the destination directory if not exist
    @mkdir($to);

    // Loop through the files in source directory
    while ($file = readdir($dir)) {
      if (($file != '.') && ($file != '..')) {
        if (is_dir($from . DIRECTORY_SEPARATOR . $file)) {
          // for sub directory 
          $this->copyDir($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file);
        } else {
          copy($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file);
        }
      }
    }

    closedir($dir);
  }
}
