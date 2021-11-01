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

$generated = date('Y-m-d G:i:s');
foreach ($orgs as $org) {
    $credits = new OrgCredits($org, '1 day ago', '30 june 2021');
    $credits->run();
    $issueCounts = $credits->getIssueCountsByDay();
    $issues = $credits->getIssues();
    Helpers::csv(
        ['date', $org],
        $issueCounts,
        "$org--counts--july-oct-2021--generated-$generated.csv"
    );
    Helpers::csv(
        ['issue - ' . $org, 'timestamp', 'date'],
        $issues,
        "$org--issues--july-oct-2021--generated-$generated.csv"
    );
    echo "Done with $org\n";
}
