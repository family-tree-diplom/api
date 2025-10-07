<?php

namespace OpenCCK\Infrastructure\Task;

use Amp\Cancellation;
use Amp\Sync\Channel;

final class DelayTask extends AbstractTask {
    public function __construct(private readonly int $seconds) {
    }

    public function run(Channel $channel, Cancellation $cancellation): mixed {
        \sleep($this->seconds);
        return microtime(true);
    }
}
