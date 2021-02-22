<?php
declare(strict_types=1);

namespace HyperfTest\Cases;

use HyperfTest\HttpTestCase;

class ScrapeTest extends HttpTestCase
{
    public function testIndex()
    {
        $this->assertTrue(is_array($this->get('/scrape')));
    }
}
