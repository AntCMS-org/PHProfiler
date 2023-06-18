<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use AntCMS\PHPProfiler;

include "PHProfiler.php";

function calculateFactorial($n)
{
    if ($n <= 1) {
        return 1;
    } else {
        return $n * calculateFactorial($n - 1);
    }
}

function findPrimeNumbers($start, $end)
{
    $primes = [];

    for ($number = $start; $number <= $end; $number++) {
        $isPrime = true;

        for ($i = 2; $i <= sqrt($number); $i++) {
            if ($number % $i == 0) {
                $isPrime = false;
                break;
            }
        }

        if ($isPrime && $number > 1) {
            $primes[] = $number;
        }
    }

    return $primes;
}

function fibonacci($n)
{
    if ($n <= 1) {
        return $n;
    } else {
        return fibonacci($n - 1) + fibonacci($n - 2);
    }
}

function sortArray($array)
{
    $length = count($array);

    for ($i = 0; $i < $length - 1; $i++) {
        for ($j = 0; $j < $length - $i - 1; $j++) {
            if ($array[$j] > $array[$j + 1]) {
                $temp = $array[$j];
                $array[$j] = $array[$j + 1];
                $array[$j + 1] = $temp;
            }
        }
    }

    return $array;
}


function callOtherFunctions()
{
    PHPProfiler::globalProfilerOnCallable('calculateFactorial', [5]);
    PHPProfiler::globalProfilerOnCallable('findPrimeNumbers', [0, 300]);
    PHPProfiler::globalProfilerOnCallable('fibonacci', [10]);
    PHPProfiler::globalProfilerOnCallable('sortArray', [[5, 2, 8, 1, 0]]);
}

function callFuncUnderFunc()
{
    PHPProfiler::globalProfilerOnCallable('callOtherFunctions');
}

//PHPProfiler::globalProfilerOnCallable('calculateFactorial', [5]);
//PHPProfiler::globalProfilerOnCallable('findPrimeNumbers', [0, 300]);
//PHPProfiler::globalProfilerOnCallable('fibonacci', [10]);
//PHPProfiler::globalProfilerOnCallable('sortArray', [[5, 2, 8, 1, 0]]);

//PHPProfiler::globalProfilerOnCallable('callOtherFunctions');
//PHPProfiler::globalProfilerOnCallable('callFuncUnderFunc');

PHPProfiler::globalProfilerOnCallable(['Self', 'globalProfilerOnCallable'], ['callOtherFunctions']);


echo PHPProfiler::globalProfilerDumpHtml();
