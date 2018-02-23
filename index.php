<?php

use Jira\JiraClient;
use Zend\Config\Factory as ConfigFactory;

require_once 'vendor/autoload.php';
$config = ConfigFactory::fromFiles(glob(__DIR__ . '/config/{,*.}{global,local}.php', GLOB_BRACE));

if (empty($config['jiraFilterId'])) {
    echo "\033[0;31m Keine FilterId hinterlegt\033[0m";
    exit;
}

$jiraClient = new JiraClient(
    $config['jiraUrl'],
    $config['jiraFilterId'],
    $config['auth']
);

$jsonResponse = $jiraClient->getWorklog();

$logs = [];
$today = new DateTime();
$today->setTime(0, 0, 0);
$sprintStartDate = new DateTime($config['startDate']);

if ($today->format('D') === $config['startDay']) {
    $sprintStartDate = $today;
}
$sprintEndDate = clone($sprintStartDate);
$sprintEndDate->modify('+' . $config['sprintDurationInDays'] . ' days');

$totalTimeEmployee = [];

foreach ($jsonResponse['issues'] as $issue) {
    foreach ($issue['fields']['worklog']['worklogs'] as $worklog) {
        $logTime = new DateTimeImmutable($worklog['started']);
        $logDate = $logTime->format('Ymd');

        if ($logTime < $sprintStartDate) {
            continue;
        }
        $authorName = $worklog['author']['displayName'];

        if (!isset($logs[$logDate][$authorName])) {
            $logs[$logDate][$authorName] = 0;
        }
        if (!isset($totalTimeEmployee[$authorName])) {
            $totalTimeEmployee[$authorName] = 0;
        }

        $logs[$logDate][$authorName] += $worklog['timeSpentSeconds'];
        $totalTimeEmployee[$authorName] += $worklog['timeSpentSeconds'];
    }
}

printHeader($sprintStartDate, $sprintEndDate);

$totalEmployee = [];

foreach ($logs as $date => $employeeLog) {
    $logDate = DateTimeImmutable::createFromFormat('Ymd', $date);
    echo str_repeat('#', 72);
    echo PHP_EOL;
    echo $logDate->format('d.m.Y');
    echo PHP_EOL;
    echo str_repeat('#', 72);
    echo PHP_EOL;
    ksort($employeeLog);
    foreach ($employeeLog as $employee => $timeSpent) {
        echo sprintf('%s hat %s geloggt', str_pad($employee, 30), secondsToPrettyTimeString($timeSpent));
        echo PHP_EOL;
    }
}

echo str_repeat('#', 72);
echo PHP_EOL;
echo 'GESAMT';
echo PHP_EOL;
echo str_repeat('#', 72);
echo PHP_EOL;

ksort($totalTimeEmployee);
$timeTotal = 0;
foreach ($totalTimeEmployee as $employee => $timeSpent) {
    echo sprintf('%s hat %s geloggt', str_pad($employee, 30), secondsToPrettyTimeString($timeSpent));
    echo PHP_EOL;
    $timeTotal += $timeSpent;
}

echo str_repeat('#', 72);
echo PHP_EOL;
echo PHP_EOL;
echo sprintf('Summe %s', secondsToPrettyTimeString($timeTotal));

/**
 * @param int $seconds
 * @return string
 */
function secondsToPrettyTimeString(int $seconds): string
{
    $minutesTotal = $seconds / 60;
    $hoursTotal = $minutesTotal / 60;
    $days = $hoursTotal / 8;
    $hours = $hoursTotal % 8;
    $minutes = $minutesTotal % 60;

    return sprintf('%d Tage %d Stunden %3$02d Minuten', $days, $hours, $minutes);
}

/**
 * @param DateTime $sprintStartDate
 * @param DateTime $sprintEndDate
 */
function printHeader(DateTime $sprintStartDate, DateTime $sprintEndDate): void
{
    echo str_repeat('#', 72);
    echo PHP_EOL;
    echo sprintf('Aktueller Sprint: %s - %s', $sprintStartDate->format('d.m.Y'), $sprintEndDate->format('d.m.Y'));
    echo PHP_EOL;
}
