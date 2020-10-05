<?php

/**
 * @package F3 Benchmark
 * @version 1.0.0
 * @link http://github.com/myaghobi/F3-Benchmark Github
 * @author Mohammad Yaghobi <m.yaghobi.abc@gmail.com>
 * @copyright Copyright (c) 2020, Mohammad Yaghobi
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 */

class Benchmark extends \Prefab {


  /**
   * Benchmark constructor
   *
   * @return void
   */
  function __construct() {
    $f3 = \Base::instance();
    if ($f3->get('DEBUG') >= 3) {
      register_shutdown_function(function () use ($f3) {
        $this->showBenchmark();
      });
    }
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
   * get sum of all elapsed times
   *
   * @return int
   */
  public function getElapsedTime() {
    // return array_sum($this->checkPoints); sum of all checkpoints
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
   * print formatted benchmark log
   *
   * @return void
   */
  public function showBenchmark() {
    print '<div class="benchmark-panel">' .
      'Time: <b>' . $this->getElapsedTime() . ' ms</b>, ' .
      'Memory: <b>' . $this->getFormattedBytes($this->getRamUsagePeak()) . '</b> ' .
      '</div>';

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
    </style>';
  }
}
