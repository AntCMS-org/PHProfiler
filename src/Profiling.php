<?php
function profileFunctionStart(string $funcName = '')
{
    if (empty($funcName)) {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $funcName = $dbt[1]['function'] ?? "Unknown";
    }

    global $prof_timing;
    $prof_timing[$funcName] = microtime(true);

    if (function_exists('memory_reset_peak_usage')) {
        memory_reset_peak_usage();
    }
}

function profileFunctionComplete(string $funcName = '')
{
    if (empty($funcName)) {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $funcName = $dbt[1]['function'] ?? "Unknown";
    }

    global $prof_timing, $prof_memory;
    $elapsed = microtime(true) - $prof_timing[$funcName];
    $prof_memory[$funcName] = memory_get_peak_usage(true);
    $prof_timing[$funcName] = $elapsed;
}

function profilePrint()
{
    global $prof_timing, $prof_memory;

    echo '<table style="border-collapse: collapse; width: 100%; background-color: #333; color: #fff;">';
    echo '<tr>
            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #555;">Function</th>
            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #555;">Time</th>
            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #555;">Peak Memory</th>
          </tr>';

    foreach ($prof_timing as $funcName => $elapsed) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: left;'>{$funcName}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: left;'>" . sprintf("%f", $elapsed) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: left;'>" . sprintf("%d bytes", $prof_memory[$funcName]) . "</td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td colspan='3' style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #555;'><b>Profiling complete.</b></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #555;'><b>Total Time:</b></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: left;'>" . sprintf("%f", array_sum($prof_timing)) . "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' style='border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #555;'><b>Peak Memory:</b></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: left;'>" . sprintf("%d bytes", memory_get_peak_usage(true)) . "</td>";
    echo "</tr>";

    echo "</table>";
}