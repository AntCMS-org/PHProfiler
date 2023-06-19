<?php

declare(strict_types=1);

namespace AntCMS;

class PHProfiler
{
    /**
     * Used to profile any callable object. Will profile the memory usage and time spent on the callable.
     *      - Memory profiling is only properly functional on PHP 8.2 and newer. On older versions, it will only record the peak memory usage since the start of the script.
     *      - Memory profiling may not be accurate when profiling functions inside of functions for the parent function.
     * 
     * @param callable $callable The callable object you wish to profile.
     * @param array $args Any arguments you wish to pass to the callable object.
     * @param string $functionName (optional) PHProfiler will attempt to automatically detect the name of the callable, but you can manually specify it here.
     * @return mixed Returns whatever the callable object returned.
     * @throws \Exception If the callable threw an exception
     */
    public static function profilerOnCallable(callable $callable, array $args = [], string $functionName = ''): mixed
    {
        // Skip profiling if 'PHProfilerSkip' is defined.
        if (defined('PHProfilerSkip')) {
            return call_user_func_array($callable, $args);
        }

        global $profiledData;
        $profiledData ??= [];
        $entryPoint = count($profiledData);

        // Create an initial profile entry for the current callable. This way, the callable itself can call the profilier on something else and the final result will still be in order.
        $loggedData = [
            'functionName' => self::getCallableName($callable, $functionName),
            'startTimePoint' => hrtime(true),
        ];
        $profiledData[$entryPoint] = $loggedData;

        // Only available on PHP 8.2+, but by doing this, we can much more accurately measure the memory usage of a given function.
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }

        $start = hrtime(true);
        $result = call_user_func_array($callable, $args);
        $elapsed = (hrtime(true) - $start) / 1e+6;

        $loggedData = [
            'functionName' => $profiledData[$entryPoint]['functionName'],
            'timeElapsed' => $elapsed,
            'startTimePoint' => $profiledData[$entryPoint]['startTimePoint'],
            'endTimePoint' => hrtime(true),
            'peakMemoryUsageReal' => memory_get_peak_usage(true),
            'peakMemoryUsage' => memory_get_peak_usage(),
            'calledFunctions' => [],

        ];

        $profiledData[$entryPoint] = $loggedData;

        return $result;
    }

    public static function getProfiledData(array $removedProperties = ['startTimePoint', 'endTimePoint']): array
    {
        global $profiledData;
        return self::processProfilerData($profiledData, $removedProperties);
    }

    /**
     * Returns an HTML list of the profiled data.
     * 
     * @param array $removedProperties (optional) What profiled properties to remove from the returned HTML. Defaults to 'startTimePoint' and 'endTimePoint'. 
     */
    public static function returnProfiledHtml(array $removedProperties = ['startTimePoint', 'endTimePoint']): string
    {
        global $profiledData;

        $processedData = self::processProfilerData($profiledData, $removedProperties);
        /*highlight_string("<?php\n\$data =\n" . var_export($processedData, true) . ";\n?>");*/
        return self::displayArrayPropertiesAsHTML($processedData);
    }

    private static function getCallableName(callable $callable, string $functionName): string
    {
        if (!empty($functionName)) {
            return $functionName;
        }

        if (is_string($callable) && function_exists($callable)) {
            return trim($callable);
        } elseif (is_array($callable)) {
            $seperator = '->';
            try {
                $MethodChecker = new \ReflectionMethod($callable[0], $callable[1]);
                if ($MethodChecker->isStatic()) {
                    $seperator = '::';
                }
            } catch (\Exception $e) {
            }

            if (is_object($callable[0])) {
                return $callable[0]::class . $seperator . $callable[1];
            } else {
                return $callable[0] . $seperator . $callable[1];
            }
        } elseif ($callable instanceof \Closure) {
            return 'Closure.' . bin2hex(random_bytes(8));
        } else {
            return 'Unknown.' . bin2hex(random_bytes(8));
        }
    }

    private static function processProfilerData(array $data, array $removeProperties = []): array
    {
        $count = count($data);
        for ($i = $count - 1; $i >= 0; $i--) {
            $start = $data[$i]['startTimePoint'];
            $end = $data[$i]['endTimePoint'];

            foreach ($removeProperties as $key) {
                unset($data[$i][$key]);
            }

            $positionOffset = 1;
            while (($i - $positionOffset) >= 0 && isset($data[$i]) && $start > $data[$i - $positionOffset]['startTimePoint']) {
                if ($end <= $data[$i - $positionOffset]['endTimePoint']) {
                    // Create a new copy of the 'calledFunctions' of the current functions caller, then add this function to the first element in it's list (otherwise the result will be backwards)
                    $calledFunctions = $data[$i - $positionOffset]['calledFunctions'];
                    array_unshift($calledFunctions, $data[$i]);
                    $data[$i - $positionOffset]['calledFunctions'] = $calledFunctions;

                    //Then remove the current function from it's outdated position.
                    unset($data[$i]);
                    continue;
                }
                $positionOffset++;
            }
        }
        return $data;
    }

    private static function displayArrayPropertiesAsHTML(array $array)
    {
        $html = '<ul>';

        foreach ($array as $key => $value) {
            $html .= '<li>' . $key . ': ';

            if (is_array($value)) {
                $html .= self::displayArrayPropertiesAsHTML($value);
            } else {
                $html .= $value;
            }

            $html .= '</li>';
        }

        $html .= '</ul>';
        return $html;
    }
}
