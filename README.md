# PHProfiler

A simple package to help you profile functions within your PHP applictation. (Still a WIP)

## Usage

The following code:
```PHP
<?php
include "PHProfiler.php";
use AntCMS\PHProfiler;

function calculateFactorial($n)
{
    if ($n <= 1) {
        return 1;
    } else {
        return $n * calculateFactorial($n - 1);
    }
}

function fibonacci($n)
{
    if ($n <= 1) {
        return $n;
    } else {
        return fibonacci($n - 1) + fibonacci($n - 2);
    }
}


function callOtherFunctions()
{
    PHProfiler::profilerOnCallable('calculateFactorial', [5]);
    PHProfiler::profilerOnCallable('fibonacci', [10]);
}

function callFuncUnderFunc()
{
    PHProfiler::profilerOnCallable('callOtherFunctions');
}

PHProfiler::profilerOnCallable('callFuncUnderFunc');
PHProfiler::profilerOnCallable(function () {
    sleep(1);
});
PHProfiler::profilerOnCallable('calculateFactorial', [10]);

var_dump(PHProfiler::getProfiledData());
```

Will output the following profiled data:
```PHP
array(3) {
  [0]=>
  array(5) {
    ["functionName"]=>
    string(17) "callFuncUnderFunc"
    ["timeElapsed"]=>
    float(0.0243)
    ["peakMemoryUsageReal"]=>
    int(2097152)
    ["peakMemoryUsage"]=>
    int(419392)
    ["calledFunctions"]=>
    array(1) {
      [0]=>
      array(5) {
        ["functionName"]=>
        string(18) "callOtherFunctions"
        ["timeElapsed"]=>
        float(0.0196)
        ["peakMemoryUsageReal"]=>
        int(2097152)
        ["peakMemoryUsage"]=>
        int(419392)
        ["calledFunctions"]=>
        array(2) {
          [0]=>
          array(5) {
            ["functionName"]=>
            string(18) "calculateFactorial"
            ["timeElapsed"]=>
            float(0.0036)
            ["peakMemoryUsageReal"]=>
            int(2097152)
            ["peakMemoryUsage"]=>
            int(419016)
            ["calledFunctions"]=>
            array(0) {
            }
          }
          [1]=>
          array(5) {
            ["functionName"]=>
            string(9) "fibonacci"
            ["timeElapsed"]=>
            float(0.0057)
            ["peakMemoryUsageReal"]=>
            int(2097152)
            ["peakMemoryUsage"]=>
            int(419392)
            ["calledFunctions"]=>
            array(0) {
            }
          }
        }
      }
    }
  }
  [4]=>
  array(5) {
    ["functionName"]=>
    string(24) "Closure.0b7f121875d9cb0f"
    ["timeElapsed"]=>
    float(1009.0092)
    ["peakMemoryUsageReal"]=>
    int(2097152)
    ["peakMemoryUsage"]=>
    int(420208)
    ["calledFunctions"]=>
    array(0) {
    }
  }
  [5]=>
  array(5) {
    ["functionName"]=>
    string(18) "calculateFactorial"
    ["timeElapsed"]=>
    float(0.0022)
    ["peakMemoryUsageReal"]=>
    int(2097152)
    ["peakMemoryUsage"]=>
    int(420200)
    ["calledFunctions"]=>
    array(0) {
    }
  }
}
```
