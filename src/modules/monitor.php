<?php

use VnBiz\VnBizError;
use League\OAuth2\Client\Provider\Google;

function vnbiz_init_module_monitor() {}

function vnbiz_monitor_request() {
    $app_name = vnbiz()->getAppName();
    $models = vnbiz()->models();
    $hostname = gethostname();
    ?>
        <table border=1 style="width: 500px; margin-top: 20px">
            <tbody>
                <?php 
                    foreach ($models as $model_name=>$model) {
                        foreach(['model_find', 'model_create', 'model_update', 'model_delete'] as $action) {
                        
                            $key = "$app_name.$hostname." . $action . '.' . $model_name;
                            echo '<tr>';
                            echo "<td>$key</td>";
                            echo "<td>". vnbiz_redis()->get($key) ."</td>";
                            echo '</tr>';
                        }
                    }
                ?>
            </tbody>
        </table>
    <?php

}
function vnbiz_monitor()
{
    /**
     * Redis info
     */
    $number_of_keys = vnbiz_redis()->dbSize();



    $load = sys_getloadavg()[0];
    $hostname = gethostname();
    $totalMemoryMB = '';
    $availableMemoryMB = '';
    $os = '';
    $cores = 0;
    $cpuModel = '';
    $currentMemoryUsageMB = 0;
    // Read memory info
    $meminfo = file_get_contents('/proc/meminfo');

    if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $matches)) {
        $totalMemoryMB = (int) ($matches[1] / 1024);
    }

    if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $matches)) {
        $availableMemoryMB = (int) ($matches[1] / 1024);
    }

    $os = php_uname();

    $cpuinfo = file_get_contents('/proc/cpuinfo');
    $cores = substr_count($cpuinfo, 'processor'); // Counts "processor" occurrences

    if (preg_match('/model name\s+:\s+(.+)/', $cpuinfo, $matches)) {
        $cpuModel = $matches[1];
    }

    $currentMemoryUsageMB = memory_get_usage(true) / 1024 / 1024;

    $peakMemoryUsageMB = memory_get_peak_usage(true) / 1024 / 1024;

?>
    <style>
            .vnbiz-value { font-weight: bold; text-align: right;}
    </style>
    <table border=1 style="width: 500px">
        <tbody>
            <tr>
                <td>hostname</td>
                <td class="vnbiz-value"><?php echo $hostname; ?></td>
            </tr>
            <tr>
                <td>os</td>
                <td class="vnbiz-value"><?php echo $os; ?></td>
            </tr>
            <tr>
                <td>cpuModel</td>
                <td class="vnbiz-value"><?php echo $cpuModel; ?></td>
            </tr>
            <tr>
                <td>cores</td>
                <td class="vnbiz-value"><?php echo $cores; ?></td>
            </tr>
            <tr>
                <td>1-minute load average</td>
                <td class="vnbiz-value"><?php echo $load; ?></td>
            </tr>
            <tr>
                <td>totalMemoryMB</td>
                <td class="vnbiz-value"><?php echo $totalMemoryMB; ?></td>
            </tr>
            <tr>
                <td>availableMemoryMB</td>
                <td class="vnbiz-value"><?php echo $availableMemoryMB; ?></td>
            </tr>
            <tr>
                <td>currentMemoryUsageMB</td>
                <td class="vnbiz-value"><?php echo $currentMemoryUsageMB; ?></td>
            </tr>
            <tr>
                <td>peakMemoryUsageMB</td>
                <td class="vnbiz-value"><?php echo $peakMemoryUsageMB; ?></td>
            </tr>
            <tr>
                <td>Redis[Number Of Keys]</td>
                <td class="vnbiz-value"><?php echo $number_of_keys; ?></td>
            </tr>
        </tbody>
    </table>
<?php
    vnbiz_monitor_request();
}
