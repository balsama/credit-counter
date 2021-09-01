<?php

namespace Balsama;

use PHPHtmlParser\Dom;
use PHPUnit\Framework\TestCase;

class OrgCreditsTest extends TestCase
{
    private OrgCredits $orgCredits;
    private Dom $exampleResponse;

    public function setup(): void
    {
        $this->orgCredits = new OrgCredits('acquia', '1 july 2021', '26 june 2021');
        $this->exampleResponse = new Dom();
        $this->exampleResponse->loadFromFile('../sample-data/example-response.html');
    }

    public function testFindIssuesUl()
    {
        $issuesUl = $this->orgCredits->findIssuesUl($this->exampleResponse);
        // There are five issues in the `example-response.html`.
        $this->assertEquals(5, $issuesUl->count());
    }

    public function testGetIssueNumberFromLi()
    {
        $issuesUl = $this->orgCredits->findIssuesUl($this->exampleResponse);
        $exampleIssueLi = $issuesUl->offsetGet(0);
        $issueNumber = $this->orgCredits->getIssueNumberFromLi($exampleIssueLi);
        $this->assertEquals(2268787, $issueNumber);
    }
}
