<?php

error_reporting(-1); //Rasmus Lerdorf said to use this
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
gc_enable(); //enables Garbage Collector
/*IMPORTANT - after each include of external file get the memory allocated by OS
 with memory_get_peak_usage(true) to get correct memory usage*/

//try { }
//catch(Exception $e) { echo 'Message: ' . $e->getMessage(); }

//global vars
$phpVersionNumber = PHP_VERSION_ID;
$phpVersionType = PHP_INT_SIZE * 8 . "bit";
$funcVersion = 0; //this variable holds version number of the currently benchmarked function
$testResults = []; //this array holds the results
$passedTests = []; //this array holds results of the tests that passed
$failedTests = []; //this array holds results of the tests that failed
$tableResults = "<table><tr class='h' style='text-align: center'>
<th>Date Finished</th>
<th>Function Name</th>
<th>Steps (runs)</th>
<th>Time (seconds/run)</th>
<th>Memory Used (bytes/run)</th>
<th>Function Version</th>
<th>PHP Version Number</th>
<th>PHP Version Type</th></tr>";
$style = "<style type='text/css'>
        body {background-color: #fff; color: #222; font-family: sans-serif;}
        pre {margin: 0; font-family: monospace;}
        a:link {color: #009; text-decoration: none; background-color: #fff;}
        a:hover {text-decoration: underline;}
        table {border-collapse: collapse; border: 0; width: auto; box-shadow: 1px 2px 3px #ccc;}
        .center {text-align: center;}
        .center table {margin: 1em auto; text-align: left;}
        .center th {text-align: center !important;}
        td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
        h1 {font-size: 150%;}
        h2 {font-size: 125%;}
        .p {text-align: left;}
        .e {background-color: #ccf; width: auto; font-weight: bold;}
        .h {background-color: #99c; font-weight: bold;}
        .v {background-color: #ddd; width: auto; overflow-x: auto; font-weight: bold;}
        .v i {color: #999;}
        .tPassed {width: 64px; background-color: lime;}
        .tFailed {width: 64px; background-color: red;}
        img {float: right; border: 0;}
        hr {width: auto; background-color: #ccc; border: 0; height: 1px;}
        .stats {margin: 0; padding: 0; display: inline-block; list-style-type: none;}
        </style>";
$div = "{{style}}<div class='center'>{{content}}{{stats}}</div>";

//Outputs Garbage Collector state
//function checkGC() { $gcState = gc_enabled();

//Outputs PHP configuration options
/*function getOpts() {
  global $funcVersion; $funcVersion = 1;
  ini_get_all();
  //prints all options in a table; for debugging purposes
  //$confOps = ini_get_all();
  //$legend = "<table><tr class='h' style='text-align: center'><th>Constant</th><th>Value</th><th>Meaning</th></tr>
  //<tr><td class='e'>PHP_INI_USER</td><td class='v' style='background-color: green'>1</td><td class='v'>Entry can be set in user scripts</td></tr>
  //<tr><td class='e'>PHP_INI_PERDIR</td><td class='v' style='background-color: red'>2</td><td class='v'>Entry can be set in php.ini, .htaccess or httpd.conf</td></tr>
  //<tr><td class='e'>PHP_INI_SYSTEM</td><td class='v' style='background-color: red'>4</td><td class='v'>Entry can be set in php.ini or httpd.conf</td></tr>
  //<tr><td class='e'>PHP_INI_ALL</td><td class='v' style='background-color: green'>7</td><td class='v'>Entry can be set anywhere</td></tr></table>";
  //$optStr = "<table><tr class='h' style='text-align: center'><th>Option</th><th>Global Value</th><th>Local Value</th><th>Access Level</th></tr>";
  //foreach ($confOps as $key => $elem) {
    //$optStr .= "<tr><td class='e'>{$key}</td><td class='v'>{$elem['global_value']}</td><td class='v'>{$elem['local_value']}</td>";
    //if ($elem['access'] == 2 || $elem['access'] == 4) { $optStr .= "<td class='v' style='background-color: red'>{$elem['access']}</td>"; }
    //else { $optStr .= "<td class='v' style='background-color: green'>{$elem['access']}</td>"; }
    //$optStr .= "</tr>";
  //}
  //$optStr .= "</table>";
  //global $style; global $div;
  //$tempDiv = str_ireplace('{{content}}', $legend . $optStr, $div);
  //echo $style . $tempDiv;
}*/

//Peak memory usage for output, see http://stackoverflow.com/a/2510468/1196983
function getMemoryUsageUnits($bytes) {
  if ($bytes > 0) {
    $base = log($bytes) / log(1024);
    $suffix = array("", "KB", "MB", "GB", "TB")[(int)floor($base)];
    return number_format(round(pow(1024, $base - floor($base)), 3), 3)  . " {$suffix}";
  } else return 0;
}

//Dummy function
function dummyFunc() { global $funcVersion; $funcVersion = 1; }

//Dummy function with errors
function dummyErrorFunc() { }

//Draws Sun with ASCII chars
function createSun() {
  global $funcVersion; $funcVersion = 1;
  $n = 3;
  $r='';
  $e=2*$n+1; //edge length
  for($i=0;$i<$e*$e;$i++) {
    $h = floor($e/2); // half point of square
    $x = floor($i%$e); // current x coordinate
    $y = floor($i/$e); // current y coordinate

    if ($y==$h&&$x==$h) {
      // center of square
      $r.='O';
    }
    else if ($y==$h) {
      // horizontal line
      $r.='-';
    }
    else if ($x==$h) {
      // vertical line
      $r.='|';
    }
    else if (($y-$h)==($x-$h)) {
      // diagonal line from top-left to bottom right
      $r.='\\';
    }
    else if (($y-$h)==($h-$x)) {
      // diagonal line from bottom-left to top-right
      $r.='/';
    }
    else {
      // empty space
      $r.=' ';
    }
    if ($x==$e-1) {
      // add new line for the end of the row
      $r.="\n";
    }
  }
  //echo "<pre>"; echo $r; echo "</pre>";
}

//Creates dummy file with N size
/*function createFile($fileName, $fileSize) {
  $f = fopen($fileName, 'wb');
  fseek($f, $fileSize, SEEK_SET);
  fwrite($f, "after {$fileSize} bytes");
  fclose($f);
}*/

//Records memory usage and runtime of an internal/user function
function getMetrics($userFuncName, $steps) {
  global $testResults; global $passedTests; global $failedTests; global $memoryUsage; global $phpVersionNumber; global $phpVersionType; global $funcVersion;
  if (function_exists($userFuncName)) {
    //if ((int)$steps != $steps) { $steps = 1; } //shorter int check but idk if faster or better
    if (filter_var($steps, FILTER_VALIDATE_INT) === false || $steps < 1) { //shorter int check but idk if faster or better
      $steps = 1; //instead of reporting an error, just set it to default value
    }
    $memoryUsage = memory_get_usage(false); //false for PHP 7, true for PHP 5.x
    $runTime = microtime(true); //start timer
    for ($i = 0; $i < $steps; $i++) { $userFuncName(); } //variable functions are ~2x faster than call_user_func()
    usleep(10);
    $runTime = (microtime(true) - $runTime) / $steps; //end timer, total time for ONE step will be saved in the database
    $memoryUsage = abs((memory_get_peak_usage(false) - $memoryUsage) / $steps);
    //$totalMem = abs((memory_get_usage(false) - $memoryUsage) / $steps);
    $funcVersion = 1; //put function version here, AFTER memory usage detection
    $passedTests[] = array('function' => $userFuncName);
  } else {
    $runTime = 0; $memoryUsage = 0; $funcVersion = 0;
    $failedTests[] = array('function' => $userFuncName, 
                            'error' => "Function was not found");
  }

  $testResults[] = array('function' => $userFuncName, 
                          'date' => date('d M Y H-i-s'), 
                          'steps' => $steps, 
                          'time' => $runTime, 
                          'memory' => $memoryUsage, 
                          'func_version' => $funcVersion, 
                          'php_version_number' => $phpVersionNumber, 
                          'php_version_type' => $phpVersionType);
  unset($memoryUsage, $runTime, $userFuncName, $steps); //should I do this clean-up or not???
}

//list of the functions to be measured
$funcList = ["dummyFunc", "dummyErrorFunc", "buggyFuncName", "createSun",
"anotherBuggyFuncName", "getOpts"];

//run all tests
foreach ($funcList as $func) { getMetrics($func, "1"); }

//save test results to database
foreach ($testResults as $res) {
  try {
    $columns = "date TEXT, steps TEXT, time TEXT, memory TEXT, func_version TEXT, php_version_number TEXT, php_version_type TEXT";
    $pdo = new PDO('sqlite:metrics.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //disable emulated prepares to prevent injection, see http://stackoverflow.com/a/12202218/1196983
    //$statement = $pdo->query("SELECT some_field FROM some_table");
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$res['function']}({$columns})");
    $pdo = $pdo->prepare("INSERT INTO {$res['function']} (date, steps, time, memory, func_version, php_version_number, php_version_type) values (?, ?, ?, ?, ?, ?, ?)");
    //PHP bug: if you don't specify PDO::PARAM_INT for INT values, PDO may enclose the argument in quotes. This can mess up some MySQL queries that don't expect integers to be quoted.
    $tempIncr = 0;
    foreach ($res as $key => $value) {
      if ($key === "function") continue; //don't save function name, anyway there is no column for that
      if (filter_var($value, FILTER_VALIDATE_INT) === false) { $pdo->bindValue(++$tempIncr, $value, PDO::PARAM_STR); } //preincrement!!!important
      else { $pdo->bindValue(++$tempIncr, $value, PDO::PARAM_INT); } //preincrement!!!important
    }
    $pdo->execute();
    //unset($pdo); //close database handler
    //$row = $pdo->fetch(PDO::FETCH_ASSOC);
    //echo "Done!<br />"; //just for testing, leave it commented to stop pollution with echoes
  } catch(PDOException $e) { echo $e->getMessage(); unset($pdo); /*and close database handler*/ }
}

//print all results
$pageStart = microtime(true);
global $testResults; global $div; global $style; global $passedList; global $failedList;
$tempDiv = null; $passedCount = 0; $failedCount = 0; $passed = ""; $failed = "";
foreach ($testResults as $elem) {
  if ($elem['time'] > 0 || $elem['memory'] > 0) { $checkState = 1; $passedCount++; } else { $checkState = 0; $failedCount++; }
  if ($checkState > 0) { $color = "style='background-color: lime'"; } else { $color = "style='background-color: red'"; }
  $tableResults .= "<tr style='text-align: right'>";
  $tableResults .= "<td class='e'>{$elem['date']}</td>";
  $tableResults .= "<td class='v' {$color}>{$elem['function']}()</td>";
  $tableResults .= "<td class='v' {$color}>{$elem['steps']}</td>";
  $tableResults .= "<td class='v' {$color}>" . number_format(round($elem['time'], 3), 3) . "</td>";
  $tableResults .= "<td class='v' {$color}>" . getMemoryUsageUnits($elem['memory']) . "</td>";
  $tableResults .= "<td class='v' {$color}>{$elem['func_version']}</td>";
  $tableResults .= "<td class='v' {$color}>{$elem['php_version_number']}</td>";
  $tableResults .= "<td class='v' {$color}>{$elem['php_version_type']}</td></tr>";
}
$tableResults .= "</table>";

foreach ($passedTests as $pTest) { $passed .= "<tr style='text-align: center'><td class='v'>{$pTest['function']}</td></tr>"; }
$passedList = "<table><tr class='h' style='text-align: center'><th class='tPassed'>{$passedCount} passed</th>{$passed}</table>";
//--
foreach ($failedTests as $fTest) { $failed .= "<tr style='text-align: center'><td class='v'>{$fTest['function']} - {$fTest['error']}</td></tr>"; }
$failedList = "<table><tr class='h' style='text-align: center'><th class='tFailed'>{$failedCount} failed</th>{$failed}</table>";

$stats = "{$passedList}{$failedList}";

$tempDiv = str_ireplace('{{style}}', $style, $div);
$tempDiv = str_ireplace('{{content}}', $tableResults, $tempDiv);
$tempDiv = str_ireplace('{{stats}}', $stats, $tempDiv);
//echo htmlentities($tempDiv, ENT_QUOTES, 'UTF-8');
echo $tempDiv . "<br />";

//show which web-server you're using
$webserver = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : phpversion();

echo "<p style='text-align: center'>Webserver: PHP {$webserver} ... PHP Version: " . phpversion() . "<br />
Report table generated for " . number_format(microtime(true) - $pageStart, 3) . " seconds on " . date('d M Y @ H:i:s');

exit();

?>
