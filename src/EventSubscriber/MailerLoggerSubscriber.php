<?php
// src/EventSubscriber/MailerLoggerSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

class MailerLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SentMessageEvent::class => 'onSentMessage',
            FailedMessageEvent::class => 'onFailedMessage',
        ];
    }

    public function onSentMessage(SentMessageEvent $event): void
    {
        try {
            // Получаем отправленное сообщение
            $sentMessage = $event->getMessage();
            
            // Получаем оригинальное письмо
            $originalMessage = $sentMessage->getOriginalMessage();
            
            if (!$originalMessage instanceof Email) {
                return;
            }
            
            // Получаем получателей и тему
            $recipients = array_map(
                fn($address) => $address->getAddress(),
                $originalMessage->getTo()
            );
            
            // Для получения ID можно использовать debug info или object hash
            $messageId = method_exists($sentMessage, 'getDebug') 
                ? $sentMessage->getDebug() 
                : spl_object_hash($sentMessage);
            
            $this->logger->info('✅ Email sent successfully', [
                'recipients' => $recipients,
                'subject' => $originalMessage->getSubject() ?? 'No subject',
                'message_id' => $messageId,
                'sent_at' => date('Y-m-d H:i:s'),
                'transport' => $this->getTransportName($sentMessage),
            ]);
            
        } catch (\Exception $e) {
            // Логируем ошибку логирования
            $this->logger->warning('Failed to log sent email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    public function onFailedMessage(FailedMessageEvent $event): void
    {
        try {
            $sentMessage = $event->getMessage();
            $error = $event->getError();
            
            $this->logger->error('❌ Failed to send email', [
                'error' => $error->getMessage(),
                'message_id' => spl_object_hash($sentMessage),
                'failed_at' => date('Y-m-d H:i:s'),
                'transport' => $this->getTransportName($sentMessage),
            ]);
            
        } catch (\Exception $e) {
            // Игнорируем ошибки в логгере
        }
    }
    
    /**
     * Получение имени транспорта из SentMessage
     */
    private function getTransportName($sentMessage): string
    {
        if (method_exists($sentMessage, 'getDebug')) {
            $debug = $sentMessage->getDebug();
            if (is_string($debug) && preg_match('/via\s+(\S+)/', $debug, $matches)) {
                return $matches[1];
            }
        }
        
        // Альтернативный способ через рефлексию
        try {
            $reflection = new \ReflectionClass($sentMessage);
            if ($reflection->hasProperty('transport')) {
                $property = $reflection->getProperty('transport');
                $property->setAccessible(true);
                return (string) $property->getValue($sentMessage);
            }
        } catch (\ReflectionException $e) {
            // ignore
        }
        
        return 'unknown';
    }
}