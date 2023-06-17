<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use AntCMS\PHPProfiler;

include "PHProfiler.php";

function aFunc()
{
    sleep(0.25);
}

function anotherFunc()
{
    sleep(0.25);
}

class testClass
{
    public static function wait()
    {
        sleep(1);
    }
}

//$profiler = new PHPProfiler(10, 'scoped');

//$profiler->profilerOnCallable(function () {
//    aFunc();
//    anotherFunc();
//});

//$profiler->profilerOnCallable(['testClass', 'wait']);

//echo $profiler->returnProfiledHtml();
//echo $profiler->dumpBacktrace();

PHPProfiler::globalProfilerOnCallable(function () {
    aFunc();
    anotherFunc();
});

PHPProfiler::globalProfilerOnCallable(['testClass', 'wait']);

echo PHPProfiler::globalProfilerDumpHtml();