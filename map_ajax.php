<?php
require __DIR__."/vendor/autoload.php";

$conn_mgr = new \Amsat\FoxDb\ConnectionManager();
$lb = new \Amsat\Telemetry\LeaderBoard($conn_mgr, true);

$last_x = $_REQUEST['last_x'];
if(!in_array($last_x, ['-90 minutes', '-24 hours', '-30 days'])) {
    header('Content-Type: application/json');
    print(json_encode(['status' => 'error', 'error' => 'Invalid last_x']));
    die();
}

$spacecraft = $_REQUEST['spacecraft'];

if($spacecraft < 0 || $spacecraft > 6) {
    header('Content-Type: application/json');
    print(json_encode(['status' => 'error', 'error' => 'Invalid spacecraft']));
    die();
}

$start = clone $lb->getDefaultEndDateTime();
$start->modify($last_x);

$map_result = $lb->passMapSearch($spacecraft, $start);

header('Content-Type: application/json');
print(json_encode($map_result));
