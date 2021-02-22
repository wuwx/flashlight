<?php
declare(strict_types=1);

namespace HyperfTest\Cases;

use HyperfTest\HttpTestCase;

class AnnounceTest extends HttpTestCase
{
    public function testIndex()
    {
        $this->assertTrue(is_string($this->get('/announce', [
            'info_hash' => random_bytes(20),
            'peer_id' => random_bytes(20),
            'port' => random_int(1025, 65535),
        ])));
    }
}
