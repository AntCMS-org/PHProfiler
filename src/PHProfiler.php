<?php

namespace AntCMS;

class PHPProfiler
{
    private int $backTraceLimit;
    private string $mode;

    private array $profiledData;

    public static function globalProfilerOnCallable(callable $callable, array $args = [], string $functionName = ''): mixed
    {
        $profiler = new \AntCMS\PHPProfiler(20, 'global');
        return $profiler->profilerOnCallable($callable, $args, $functionName);
    }

    public static function globalProfilerDumpHtml()
    {
        $profiler = new \AntCMS\PHPProfiler(20, 'global');
        return $profiler->returnProfiledHtml();
    }

    public function __construct(int $backTraceLimit = 20, string $mode = 'scoped')
    {
        $this->backTraceLimit = $backTraceLimit;

        if ($mode === 'global') {
            global $profiledData;
            if (empty($profiledData)) {
                $profiledData = [];
            }
        } else if ($mode === 'scoped') {
        } else {
            throw new \Exception("Unknown profiling mode: $mode. Only 'global' and 'scoped' are acceptable.");
        }
        $this->mode = $mode;
    }

    public function profilerOnCallable(callable $callable, array $args = [], string $functionName = ''): mixed
    {
        if ($this->mode === 'global') {
            global $profiledData;
            $this->profiledData = &$profiledData;
        }

        $functionName = self::getCallableName($callable, $functionName);
        $entryPoint = count($this->profiledData);

        $loggedData = [
            'functionName' => $functionName,
            'startTimePoint' => hrtime(true),
            'calledFunctions' => [],
        ];

        $this->profiledData[$entryPoint] = $loggedData;

        // Only available on PHP 8.2+, but by doing this, we can much more accurately measure the memory usage of a given function.
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }

        $start = hrtime(true);
        $result = call_user_func_array($callable, $args);
        $elapsed = (hrtime(true) - $start) / 1e+6;

        $loggedData['timeElapsed'] = $elapsed;
        $loggedData['peakMemoryUsageReal'] = memory_get_peak_usage(true);
        $loggedData['peakMemoryUsage'] = memory_get_peak_usage();
        $loggedData['endTimePoint'] = hrtime(true);

        $this->profiledData[$entryPoint] = $loggedData;

        return $result;
    }

    public function returnProfiledHtml(): string
    {
        if ($this->mode === 'global') {
            global $profiledData;
            $this->profiledData = $profiledData;
        }

        $this->processProfilerData($this->profiledData);
        /*highlight_string("<?php\n\$data =\n" . var_export($this->profiledData, true) . ";\n?>");*/
        return $this->displayArrayPropertiesAsHTML($this->profiledData);
    }

    private static function getCallableName(callable $callable, string $functionName): string
    {
        if (!empty($functionName)) {
            return $functionName;
        }

        if (is_string($callable) && function_exists($callable)) {
            return trim($callable);
        } elseif (is_array($callable)) {
            switch (strtolower($callable[0])) {
                case 'self':
                    return self::class . '::' . trim($callable[1]);
                    break;
                case 'this':
                    return self::class . '->' . trim($callable[1]);
                    break;
                default:
                    $MethodChecker = new \ReflectionMethod($callable[0], $callable[1]);
                    if ($MethodChecker->isStatic()) {
                        return trim($callable[0]) . '::' . trim($callable[1]);
                    } else {
                        return trim($callable[0]) . '->' . trim($callable[1]);
                    }
            }
        } elseif ($callable instanceof \Closure) {
            return 'Closure.' . bin2hex(random_bytes(8));
        } else {
            return 'Unknown.' . bin2hex(random_bytes(8));
        }
    }

    private function processProfilerData(array &$data)
    {
        $count = count($data);
        for ($i = $count - 1; $i > 0; $i--) {
            $start = $data[$i]['startTimePoint'];
            $end = $data[$i]['endTimePoint'];

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

    private function displayArrayPropertiesAsHTML(array $array)
    {
        $html = '<ul>';

        foreach ($array as $key => $value) {
            $html .= '<li>' . $key . ': ';

            if (is_array($value)) {
                $html .= $this->displayArrayPropertiesAsHTML($value); // Recursive call for nested arrays
            } else {
                $html .= $value;
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }
}
