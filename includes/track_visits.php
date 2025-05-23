<?php
$counterFile = __DIR__ . '/count_views.txt';
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, '0');
}
$totalVisits = (int) file_get_contents($counterFile);
$totalVisits++;
file_put_contents($counterFile, $totalVisits);
function getTotalVisits() {
    $counterFile = __DIR__ . '/count_views.txt';
    return (int) file_get_contents($counterFile);
}
?>