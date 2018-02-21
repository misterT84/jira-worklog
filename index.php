<?php

use Zend\Config\Factory as ConfigFactory;

require_once 'vendor/autoload.php';
$config = ConfigFactory::fromFiles(glob(__DIR__ . '/config/{,*.}{global,local}.php', GLOB_BRACE));

if (empty($config['basicAuthString'])) {
    echo "\033[0;31m Keine Credentials hinterlegt\033[0m";
    exit;
}

if (empty($config['jiraFilterId'])) {
    echo "\033[0;31m Keine FilterId hinterlegt\033[0m";
    exit;
}

$url = rtrim($config['jiraUrl'], '/') . '/rest/api/2/search?jql=filter%3D' . $config['jiraFilterId'] . '&fields=worklog&maxResults=100';

$client = new GuzzleHttp\Client();
$response = $client->get($url, [
    'headers' => [
        'Authorization' => 'Basic ' . $config['basicAuthString'],
    ],
]);

$jsonResponse = json_decode($response->getBody(), true);

$result = [];
$today = new DateTime();
$today->setTime(0, 0, 0);
$sprintStartDate = new DateTime($config['startDate']);

if ($today->format('D') === $config['startDay']) {
    $sprintStartDate = $today;
}
$sprintEndDate = clone($sprintStartDate);
$sprintEndDate->modify('+' . $config['sprintDurationInDays'] . ' days');

foreach ($jsonResponse['issues'] as $issue) {
    foreach ($issue['fields']['worklog']['worklogs'] as $worklog) {
        $logTime = new DateTimeImmutable($worklog['created']);

        if ($logTime < $sprintStartDate) {
            continue;
        }
        $authorName = $worklog['author']['displayName'];

        if (!isset($result[$authorName])) {
            $result[$authorName] = 0;
        }

        $result[$authorName] += $worklog['timeSpentSeconds'];
    }
}

$logsByEmployee = array_map('secondsToPrettyTimeString', $result);

printHeader($sprintStartDate, $sprintEndDate);

foreach ($logsByEmployee as $employee => $timeSpent) {
    echo sprintf('%s hat %s geloggt', $employee, $timeSpent);
    echo PHP_EOL;
}

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

    return sprintf('%d Tage %d Stunden %d Minuten', $days, $hours, $minutes);
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
    echo str_repeat('#', 72);
    echo PHP_EOL;
}
