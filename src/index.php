<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

echo PHProfiler::returnProfiledHtml();
//var_dump(PHProfiler::getProfiledData());
