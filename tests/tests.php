<?php

class Tests {

  function run($f3) {
    $test = new \Test;

    $f3->set('DEBUG', 2);
    $bm = new Benchmark();
    $bmEnableD2 = $bm->isEnable();

    $f3->set('DEBUG', 3);
    $bm = new Benchmark();
    $bmEnableD3 = $bm->isEnable();

    $test->expect(
      !$bmEnableD2 && $bmEnableD3,
      'Benchmark is enabled just if DEBUG>=3'
    );

    $test->expect(
      $f3->get('benchmark'),
      'Benchmark is available with F3'
    );

    $test->expect(
      count($bm->getCheckPoints()) == 2,
      'getCheckPoints() returns correct value ' . count($bm->getCheckPoints())
    );

    $bm->checkPoint();
    $test->expect(
      count($bm->getCheckPoints()) == 3,
      'checkPoint() added new checkpoint '
    );

    $test->expect(
      $bm->getRamUsageMax() > 1,
      'getRamUsageMax() works fine'
    );

    $test->expect(
      $bm->getTagName('myTag#2') == 'myTag' &&
        $bm->getTagName('myTag#11#22') == 'myTag#11',
      'getTagName() returns the right string to show '
    );


    $requestTimeInMS = round($f3->get('SERVER.REQUEST_TIME_FLOAT') * 1000);
    $test->expect(
      $bm->getRequestTime() == $requestTimeInMS,
      'getRequestTime() returns correct value '
    );

    $currentTimeInMS = round(microtime(true) * 1000);
    $test->expect(
      $bm->getCurrentTime() == $currentTimeInMS,
      'getCurrentTime() returns correct value '
    );

    $test->expect(
      $bm->getExecutionTime() == $currentTimeInMS - $requestTimeInMS,
      'Execution time is correctly calculated'
    );

    $test->expect(
      $bm->getFormattedBytes(0) == '0 B' &&
        $bm->getFormattedBytes(500) == '500 B' &&
        $bm->getFormattedBytes(50000) == '49 kB',
      'getFormattedBytes() works fine '
    );

    $test->expect(
      $bm->getLoadedFilesCount() > 1,
      'getLoadedFilesCount() works fine '
    );

    $test->expect(
      strlen($bm->getDetailsLog()) >= 100,
      'getDetailsLog() works fine '
    );

    $test->expect(
      strlen($bm->getFormattedBenchmark()) >= 1000,
      'getFormattedBenchmark() works fine '
    );

    $bm->checkPoint('duplicateTag');
    $countBeforeDuplicateTag = count($bm->getCheckPoints());

    $bm->checkPoint('duplicateTag');
    $countAfterDuplicateTag = count($bm->getCheckPoints());
    
    $test->expect(
      $countAfterDuplicateTag - $countBeforeDuplicateTag == 1,
      'Using duplicated tags is possible'
    );

    $f3->set('results', $test->results());
  }

  function afterRoute($f3) {
    $f3->set('active', 'Benchmark');
    echo \Preview::instance()->render('tests.htm');
  }
}
