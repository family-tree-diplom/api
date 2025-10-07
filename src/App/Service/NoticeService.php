<?php

namespace OpenCCK\App\Service;

use OpenCCK\Domain\Entity\User;
use OpenCCK\Infrastructure\Task\MailTask;
use OpenCCK\Message;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Throwable;
use Exception;

use function Amp\Parallel\Worker\createWorker;
use function OpenCCK\dbg;
use function OpenCCK\getEnv;

final class NoticeService implements ServiceInterface {
    public function __construct(private readonly User $user) {
    }

    /**
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return bool
     * @throws Throwable
     */
    public function notice(string $subject, string $body, array $options = []): bool {
        if ($this->user->getEmail()) {
            return $this->noticeByEmail($subject, $body, $options);
        }
        throw new Exception(Message::USER_NO_NOTIFICATION);
    }

    /**
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return bool
     * @throws Exception
     */
    private function noticeByEmail(string $subject, string $body, array $options): bool {
        $worker = createWorker();
        $task = new MailTask($this->user->getEmail(), $subject, $body);
        $execution = $worker->submit($task);

        $result = $execution->await();
        $taskResult = json_decode($result);

        if (isset($taskResult->error)) {
            throw new Exception($taskResult->error->message, $taskResult->error->code);
        }

        return $taskResult->result;
    }
}
