<?php

require './vendor/autoload.php';

use Balsama\Helpers;
use Balsama\OrgCredits;

$orgs = [
    'acquia',
    'third & grove',
    'opensense',
    'ci&t',
    'previousnext',
    'qed42',
    'lullabot',
    'mediacurrent',
    'acro media',
    'tag1',
];

foreach ($orgs as $org) {
    $credits = new OrgCredits($org);
    $issueCounts = $credits->getIssueCountsByDay();
    $issues = $credits->getIssues();
    Helpers::csv(['date', $org], $issueCounts, $org . '--2020-2021.csv');
    Helpers::csv(['issue - ' . $org, 'timestamp', 'date'], $issues, $org . '--issues--2020-2021');
    echo "Done with $org\n";
}
