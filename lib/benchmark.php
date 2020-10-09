<?php

/**
 * @package F3 Benchmark
 * @version 1.2.1
 * @link http://github.com/myaghobi/F3-Benchmark Github
 * @author Mohammad Yaghobi <m.yaghobi.abc@gmail.com>
 * @copyright Copyright (c) 2020, Mohammad Yaghobi
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 */

class Benchmark extends \Prefab {
  private $checkPoints;
  private $ramUsage;
  private $ramUsagePeak;

  /**
   * these properties will help us to save performance
   * imagine to have hundreds of checkpoints, so we don't need to use count() on that big array
   * or there will be no memory usage if you are not in DEBUG mode
   */
  private $isBenchmarkEnable;
  private $lastCheckPointInMS;
  private $lastCheckPointNumber;

  /**
   * Benchmark constructor
   *
   * @return void
   */
  function __construct() {
    $this->isBenchmarkEnable = false;

    $f3 = \Base::instance();
    if ($f3->get('DEBUG') >= 3) {
      $this->isBenchmarkEnable = true;
      $this->checkPoints = array();
      $this->ramUsage = array();
      $this->ramUsagePeak = 0;
      $this->lastCheckPointInMS = 0;
      $this->lastCheckPointNumber = 0;

      // add Start point, $this->lastCheckPointInMS == 0
      $this->checkPoint('Start');
      // add Benchmark Init point, $this->lastCheckPointInMS > 0
      $this->checkPoint('Benchmark Init');

      register_shutdown_function(function () {
        $this->enhanceExecutionTime();
        $this->showBenchmark();
      });
    }

    // you can comment below line if you don't need to call checkPoint()
    $f3->set('benchmark', $this);
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
      $tag = 'Check Point ' . ($this->lastCheckPointNumber+1);
    }

    // just trying to make tag unique, so we can use duplicate tags for our checkPoints();
    $tag.='#'.($this->lastCheckPointNumber+1);

    $currentTime = $this->getCurrentTime();
    $ramUsagePeak = $this->getRamUsagePeak();

    if ($this->lastCheckPointInMS == 0) {
      $currentTime = $this->getRequestTime();
    }

    $this->checkPoints[$tag] = $currentTime;
    $this->ramUsage[$tag] = $ramUsagePeak;
    $this->ramUsagePeak = max($ramUsagePeak, $this->ramUsagePeak);

    $this->lastCheckPointInMS = $currentTime;
    $this->lastCheckPointNumber += 1;
  }

    
  /**
   * calculate execution time for each checkpoint
   *
   * @return void
   */
  public function enhanceExecutionTime() {
    $prevKey ='';
    $prevTime =0;

    foreach ($this->checkPoints as $key => $value) {
      if ($prevTime==0) {
        $prevKey=$key;
        $prevTime=$value;
        continue;
      }
      $this->checkPoints[$prevKey] = $value-$prevTime;
      
      $prevKey=$key;
      $prevTime=$value;
    }
    
    $this->checkPoints[$prevKey] = $this->getCurrentTime()-$prevTime;
  }

    
  /**
   * get real ram usage
   *
   * @return int
   */
  public function getRamUsagePeak() {
    // true => memory_real_usage
    return memory_get_peak_usage(true);
  }


  /**
   * get elapsed times from beginning till now in milliseconds
   *
   * @return int
   */
  public function getExecutionTime() {
    return $this->getCurrentTime()-$this->getRequestTime();
  }

    
  /**
   * get request time in milliseconds
   *
   * @return int
   */
  public function getRequestTime() {
    return round($_SERVER["REQUEST_TIME_FLOAT"] * 1000);
  }


  /**
   * get the count of added points
   *
   * @return int
   */
  public function getCheckPointsCount() {
    return count($this->checkPoints);
  }


  /**
   * clear checkpoints & ram usages
   *
   * @return void
   */
  public function clear() {
    $this->checkPoints = array();
    $this->ramUsage = array();
  }


  /**
   * get current time in milliseconds
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
  public function getFormattedBytes($size) {
    $base = log($size, 1024);
    $suffixes = array('', 'kB', 'mB', 'gB', 'tB');

    return round(pow(1024, $base - floor($base)), 1) . ' ' . $suffixes[floor($base)];
  }

    
  /**
   * get count of all loaded files in project 
   *
   * @return int
   */
  public function getLoadedFilesCount() {
    return count(get_required_files());
  }


  /**
   * generate a log of checkpoints & ram usage
   *
   * @return string
   */
  public function getDetailsLog($fullExecTime=0, $lineDelimiter="<br>") {
    $str = '';
    foreach ($this->checkPoints as $key => $value) {
      $str .= 
      preg_replace('/#([^,]+)$/', '', $key).
      " => ".
      " Time: <b>$value ms</b> (".round($value/$fullExecTime*100)."%)".
      ", Memory: <b>" . $this->getFormattedBytes($this->ramUsage[$key]) . "</b>".
      $lineDelimiter;
    }
    return $str;
  }


  /**
   * print formatted benchmark log
   *
   * @return void
   */
  public function showBenchmark() {
    $fullExecTime = $this->getExecutionTime();
    print '<div class="benchmark-panel" class="benchmark-panel-main">' .
      'Time: <b>' . $fullExecTime . ' ms</b>, ' .
      'Memory: <b>' . $this->getFormattedBytes($this->ramUsagePeak) . '</b>, ' .
      'Included: <b>' . $this->getLoadedFilesCount() . '</b>, ' .
      'Points: <b>' . $this->lastCheckPointNumber . '</b>' .
      '<a href="javascript: void" id="benchmark-toggle">&#8661;</a>'.
      '</div>';

    print '<div class="benchmark-panel" id="benchmark-panel-log" style="display:none">' .
      $this->getDetailsLog($fullExecTime);
      '</div>';

    print '<script>
        document.getElementById("benchmark-toggle").onclick = function () {
          var x = document.getElementById("benchmark-panel-log");
          if (x.style.display == "none") {
              x.style.display = "inline-block";
          } else {
              x.style.display = "none";
          }
          return false;
        };
      </script>';

    print '<style>
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
      #benchmark-panel-log {
        bottom: 45px!important;
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
  }
}
