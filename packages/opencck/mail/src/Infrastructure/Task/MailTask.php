<?php

namespace OpenCCK\Infrastructure\Task;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use function OpenCCK\dbg;
use function OpenCCK\getEnv;

final class MailTask extends AbstractTask {
    public function __construct(
        private readonly string $email,
        private readonly string $subject,
        private readonly string $body
    ) {
    }

    /**
     * @throws Exception
     */
    public function run(Channel $channel, Cancellation $cancellation): string {
        $this->init();

        $mail = new PHPMailer(true);
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
        $mail->isSMTP(); //Send using SMTP
        $mail->Host = getEnv('SMTP_HOST'); //Set the SMTP server to send through
        $mail->SMTPAuth = true; //Enable SMTP authentication
        $mail->Username = getEnv('SMTP_USERNAME'); //SMTP username
        $mail->Password = getEnv('SMTP_PASSWORD'); //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
        $mail->Port = getEnv('SMTP_PORT') ?? 465; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(getEnv('SMTP_FROM'), getEnv('SMTP_FROM_NAME'));
        $mail->addAddress($this->email); //Add a recipient

        $mail->isHTML(true);
        $mail->Subject = $this->subject;
        $mail->Body = $this->body;

        try {
            return json_encode(['result' => $mail->send()]);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return json_encode([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
            ]);
        }
    }
}
