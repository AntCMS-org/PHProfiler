<?php

namespace AntCMS;

class PHPProfiler
{
    private int $backTraceLimit;
    private float $profilingStartTime;
    private string $mode;

    private array $profiledData;

    public static function globalProfilerOnCallable(callable $callable, array $args = []): mixed
    {
        $profiler = new \AntCMS\PHPProfiler(20, 'global');
        return $profiler->profilerOnCallable($callable, $args);
    }

    public static function globalProfilerDumpHtml()
    {
        $profiler = new \AntCMS\PHPProfiler(20, 'global');
        return $profiler->returnProfiledHtml();
    }

    public function __construct(int $backTraceLimit = 20, string $mode = 'global')
    {
        $this->backTraceLimit = $backTraceLimit;

        if ($mode === 'global') {
            global $profilingStartTime;
            if(empty($profilingStartTime)){
                $profilingStartTime = microtime(true);
                $this->profilingStartTime = $profilingStartTime;
            }
        } elseif ($mode === 'scoped') {
            $this->profilingStartTime = microtime(true);
        } else {
            throw new \Exception("Unknown profiling mode: $mode. Only 'global' and 'scoped' are acceptible.");
        }

        $this->mode = $mode;
    }

    public function profilerOnCallable(callable $callable, array $args = []): mixed
    {
        // Only available on PHP 8.2+, but by doing this we can much more accurately measure the memory usage of a given function.
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }

        $start = hrtime(true);
        $result = call_user_func($callable, $args);
        $elapsed = (hrtime(true) - $start) / 1e+6;
        $funcName = self::getCallableName($callable);

        // We weren't able to extract the actual name of the function, so we add random strings to the end 
        if ($funcName === 'Closure' || $funcName === 'Unknown') {
            $funcName .= bin2hex(random_bytes(8));
        }

        switch ($this->mode) {
            case 'global':
                global $profiledData;
                $profiledData[] = [
                    'functionName' => $funcName,
                    'timeElapsed' => $elapsed,
                    'peakMemoryUsageReal' => memory_get_peak_usage(true),
                    'peakMemoryUsage' => memory_get_peak_usage(),
                    'timeSinceStart' => microtime(true) - $this->profilingStartTime,
                ];
                break;
            case 'scoped':
                $this->profiledData[] = [
                    'functionName' => $funcName,
                    'timeElapsed' => $elapsed,
                    'peakMemoryUsageReal' => memory_get_peak_usage(true),
                    'peakMemoryUsage' => memory_get_peak_usage(),
                    'timeSinceStart' => microtime(true) - $this->profilingStartTime,
                ];
                break;
        }

        return $result;
    }

    public function returnProfiledHtml(): string
    {
        if ($this->mode === 'global') {
            global $profiledData;
            $this->profiledData = $profiledData;
        }

        $html = '<ul>';
        foreach ($this->profiledData as $point => $pointProfiledData) {
            $html .= '<li> <span>' . $pointProfiledData['functionName'] . '</span>';
            $html .= '<ul>';

            $html .= '<li>Function name: ' . $pointProfiledData['functionName'] . '</li>';
            $html .= '<li>Time elapsed: ' . $pointProfiledData['timeElapsed'] . '</li>';
            $html .= '<li>Peak (real) memory usage: ' . $pointProfiledData['peakMemoryUsageReal'] . '</li>';
            $html .= '<li>Peak memory usage: ' . $pointProfiledData['peakMemoryUsage'] . '</li>';
            $html .= '<li>Time since start of profiling: ' . $pointProfiledData['timeSinceStart'] . '</li>';

            $html .= '</ul>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    public function dumpBacktrace(): string
    {
        $bt = debug_backtrace();
        return self::printBackTrace($bt);
    }

    private static function getCallableName(callable $callable): string
    {
        if (is_string($callable) && function_exists($callable)) {
            return trim($callable);
        } elseif (is_array($callable)) {
            return trim($callable[1]);
        } elseif ($callable instanceof \Closure) {
            return 'Closure';
        } else {
            return 'Unknown';
        }
    }

    // TODO: Implement this.
    private static function printBackTrace(array $backtrace)
    {
        $return = '';
        //Array ( [0] => Array ( [file] => /home/hostbybelle/web/phprofiler.somestuffbybelle.com/public_html/index.php [line] => 14 [function] => profileCallable [class] => AntCMS\PHPProfiler [object] => AntCMS\PHPProfiler Object ( [backTraceLimit:AntCMS\PHPProfiler:private] => 20 ) [type] => -> [args] => Array ( [0] => Closure Object ( ) ) ) ) 1
        foreach ($backtrace as $trace => $event) {
            $return .= "Function: " . $event['function'] . '<br>';
        }
        return $return;
    }
}
