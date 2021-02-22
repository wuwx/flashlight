<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://github.com/wuwx/flashlight
 * @document https://github.com/wuwx/flashlight
 * @contact  https://github.com/wuwx/flashlight
 * @license  https://github.com/wuwx/flashlight/blob/master/LICENSE
 */
namespace App\Controller;

use App\Request\AnnounceRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Rych\Bencode\Bencode;

class AnnounceController extends AbstractController
{
    /**
     * @Inject
     * @var Redis
     */
    private $redis;

    public function index(AnnounceRequest $request)
    {
        $infoHash = bin2hex($request->query('info_hash'));
        $peerId = bin2hex($request->query('peer_id'));
        $ip = $request->server('remote_addr');
        $port = $request->query('port');

        $uploaded = $request->query('uploaded');
        $downloaded = $request->query('downloaded');
        $left = $request->query('left');
        $event = $request->query('event');

        $this->redis->hMSet("/topics/{$infoHash}/peers/{$peerId}", [
            'ip' => $ip,
            'port' => $port,
            'uploaded' => $uploaded,
            'downloaded' => $downloaded,
            'left' => $left,
        ]);

        $ipPort = json_encode(['ip' => $ip, 'port' => $port]);
        $timestamp = time();

        if ($event == 'stopped') {
            $this->redis->del("/topics/{$infoHash}/peers/{$peerId}");
            $this->redis->zRem("/topics/{$infoHash}/uploaders", $peerId);
            $this->redis->zRem("/topics/{$infoHash}/downloaders", $peerId);

            $this->redis->zRem("/topics/{$infoHash}/peers", $ipPort);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $this->redis->zRem("/topics/{$infoHash}/uploaders/ipv4", $ipPort);
                $this->redis->zRem("/topics/{$infoHash}/downloaders/ipv4", $ipPort);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $this->redis->zRem("/topics/{$infoHash}/uploaders/ipv6", $ipPort);
                $this->redis->zRem("/topics/{$infoHash}/downloaders/ipv6", $ipPort);
            }

            $this->redis->zRemRangeByScore("/topics/{$infoHash}/peers", 0, $timestamp - 5400);

            $this->redis->zRemRangeByScore("/topics/{$infoHash}/uploaders", 0, $timestamp - 5400);
            $this->redis->zRemRangeByScore("/topics/{$infoHash}/uploaders/ipv6", 0, $timestamp - 5400);
            $this->redis->zRemRangeByScore("/topics/{$infoHash}/uploaders/ipv4", 0, $timestamp - 5400);

            $this->redis->zRemRangeByScore("/topics/{$infoHash}/downloaders", 0, $timestamp - 5400);
            $this->redis->zRemRangeByScore("/topics/{$infoHash}/downloaders/ipv6", 0, $timestamp - 5400);
            $this->redis->zRemRangeByScore("/topics/{$infoHash}/downloaders/ipv4", 0, $timestamp - 5400);
        } else {
            $this->redis->zAdd("/topics/{$infoHash}/peers", $timestamp, $ipPort);

            if ($left == 0) {
                $this->redis->zAdd("/topics/{$infoHash}/uploaders", $timestamp, $peerId);
                $this->redis->zRem("/topics/{$infoHash}/downloaders", $peerId);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $this->redis->zAdd("/topics/{$infoHash}/uploaders/ipv4", $timestamp, $ipPort);
                    $this->redis->zRem("/topics/{$infoHash}/downloaders/ipv4", $ipPort);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $this->redis->zAdd("/topics/{$infoHash}/uploaders/ipv6", $timestamp, $ipPort);
                    $this->redis->zRem("/topics/{$infoHash}/downloaders/ipv6", $ipPort);
                }
            }

            if ($left > 0) {
                $this->redis->zRem("/topics/{$infoHash}/uploaders", $peerId);
                $this->redis->zAdd("/topics/{$infoHash}/downloaders", $timestamp, $peerId);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $this->redis->zRem("/topics/{$infoHash}/uploaders/ipv4", $ipPort);
                    $this->redis->zAdd("/topics/{$infoHash}/downloaders/ipv4", $timestamp, $ipPort);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $this->redis->zRem("/topics/{$infoHash}/uploaders/ipv6", $ipPort);
                    $this->redis->zAdd("/topics/{$infoHash}/downloaders/ipv6", $timestamp, $ipPort);
                }
            }
        }

        $complete = $this->redis->zCard("/topics/{$infoHash}/uploaders");
        $incomplete = $this->redis->zCard("/topics/{$infoHash}/downloaders");
        $peers = collect($this->redis->zRange("/topics/{$infoHash}/peers", 0, -1))->map(function ($peer) {
            return json_decode($peer, true);
        });

        return Bencode::encode([
            'interval' => 300,
            'min interval' => 30,
            'complete' => $complete,
            'incomplete' => $incomplete,
            'peers' => $peers,
        ]);
    }
}
