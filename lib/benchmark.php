<?php

/**
 * @package F3 Benchmark
 * @version 1.3.0
 * @link http://github.com/myaghobi/F3-Benchmark Github
 * @author Mohammad Yaghobi <m.yaghobi.abc@gmail.com>
 * @copyright Copyright (c) 2020, Mohammad Yaghobi
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 */

class Benchmark extends \Prefab {
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

  /**
   * Benchmark constructor
   *
   * @param bool $registerShutdown
   * @param bool $force
   * @return void
   */
  function __construct($registerShutdown=true,$force=false) {
    $this->isBenchmarkEnable = false;

    $f3 = \Base::instance();
    if ($f3->get('DEBUG') >= 3 || $force==true) {
      $this->isBenchmarkEnable = true;
      $this->init();

      if ($registerShutdown) {
          register_shutdown_function(function () {
              $this->enhanceExecutionTime();
              print $this->getFormattedBenchmark();
          });
      }
    }

    // you can comment below line if you don't need to call checkPoint()
    $f3->set('benchmark', $this);
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

    // add Start point, $this->lastCheckPointInMS == 0
    $this->checkPoint('Start');

    // add Benchmark Init point, $this->lastCheckPointInMS > 0
    $this->checkPoint('Benchmark Init');
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
  public function makeCheckPointClass($time = 0, $ram = 0, $file, $line = 0) {
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
    $output = '';

    $output .= '<div class="benchmark-panel">' .
      'Time: <b>' . $fullExecTime . ' ms</b>, ' .
      'Memory: <b>' . $this->getFormattedBytes($this->ramUsageMax) . '</b>, ' .
      'Included: <b>' . $this->getLoadedFilesCount() . '</b>, ' .
      'Points: <b>' . $this->lastCheckPointNumber . '</b>' .
      '<a href="javascript: void" id="benchmark-toggle">&#8661;</a>' .
      '</div>';

    $output .= '<div class="benchmark-panel benchmark-panel-log" style="display:none">' .
      $this->getDetailsLog($fullExecTime) .
      '</div>';

    $output .= '<script>
        document.getElementById("benchmark-toggle").onclick = function () {
          var x = document.getElementsByClassName("benchmark-panel-log")[0];
          if (x.style.display == "none") {
              x.style.display = "inline-block";
          } else {
              x.style.display = "none";
          }
          return false;
        };
      </script>';

    $output .= '<style>
      .benchmark-panel {
        position: fixed!important;
        font-family: tahoma!important;
        z-index: 1000000!important;
        bottom: 5px!important;
        left: 5px!important;
        direction: ltr!important;
        text-align: left!important;
        border: 1px solid #dee2e6!important;
        border-radius: 5px!important;
        font-size: 13px!important;
        background: #f8f9fa!important;
        color: #555!important;
        padding: 7px 10px!important;
      }
      .benchmark-panel-log {
        bottom: 45px!important;
      }
      .benchmark-panel-log span[title] {
        text-decoration:underline;
        cursor: pointer
      }
      .benchmark a {
        color: #555;
        text-decoration: underline;
      }
      .benchmark b {
        color: #333;
      }
      #benchmark-toggle {
        display:inline-block;
        font-size: 13px;
        padding: 0px 3px 0 1px;
        margin-top: -1px;
        vertical-align: top;
      }
    </style>';

    return $output;
  }
}
