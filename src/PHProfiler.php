<?php

declare(strict_types=1);

namespace AntCMS;

class PHPProfiler
{
    public static function profilerOnCallable(callable $callable, array $args = [], string $functionName = ''): mixed
    {
        global $profiledData;

        if (empty($profiledData)) {
            $profiledData = [];
        }

        $functionName = self::getCallableName($callable, $functionName);
        $entryPoint = count($profiledData);

        $loggedData = [
            'functionName' => $functionName,
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

    public static function returnProfiledHtml(array $removedProperties = ['startTimePoint', 'endTimePoint']): string
    {
        global $profiledData;

        self::processProfilerData($profiledData, $removedProperties);
        /*highlight_string("<?php\n\$data =\n" . var_export($profiledData, true) . ";\n?>");*/
        return self::displayArrayPropertiesAsHTML($profiledData);
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
            return $callable[0]::class . '->' . $callable[1];
        } elseif ($callable instanceof \Closure) {
            return 'Closure.' . bin2hex(random_bytes(8));
        } else {
            return 'Unknown.' . bin2hex(random_bytes(8));
        }
    }

    private static function processProfilerData(array &$data, array $removeProperties = [])
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
                    $calledFunctions = $data[$i - $positionOffset]['calledFunctions'];
                    array_unshift($calledFunctions, $data[$i]);
                    $data[$i - $positionOffset]['calledFunctions'] = $calledFunctions;
                    unset($data[$i]);
                    continue;
                }
                $positionOffset++;
            }
        }
    }

    private static function displayArrayPropertiesAsHTML(array $array)
    {
        $html = '<ul>';

        foreach ($array as $key => $value) {
            $html .= '<li>' . $key . ': ';

            if (is_array($value)) {
                $html .= self::displayArrayPropertiesAsHTML($value); // Recursive call for nested arrays
            } else {
                $html .= $value;
            }

            $html .= '</li>';
        }

        $html .= '</ul>';
        return $html;
    }
}
