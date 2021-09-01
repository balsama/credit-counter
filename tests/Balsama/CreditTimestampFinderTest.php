<?php

namespace Balsama;

use PHPHtmlParser\Dom;
use PHPUnit\Framework\TestCase;

class CreditTimestampFinderTest extends TestCase
{
    private $issues = [
        3051766 => [
            'issue number' => 3051766,
            'credit date' => '2021-05-19',
            'credit type' => 'issue',
        ],
        3213012 => [
            'issue number' => 3213012,
            'credit date' => '2021-05-25',
            'credit type' => 'meeting',
        ]
    ];

    public function testGetCreditTimestamp()
    {
        foreach ($this->issues as $testIssue) {
            $issueDom = new Dom();
            $issueDom->loadFromFile(__dir__ . '/../sample-data/example-issue--' . $testIssue['issue number'] . '.html');

            $timestamp = CreditTimestampFinder::getCreditTimestamp($issueDom);
            $creditDate = date('Y-m-d', $timestamp);

            $this->assertEquals($testIssue['credit date'], $creditDate);
        }
    }
}
