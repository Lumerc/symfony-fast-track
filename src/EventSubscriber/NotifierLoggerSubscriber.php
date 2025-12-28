<?php
// src/EventSubscriber/NotifierLoggerSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Event\SentMessageEvent;
use Symfony\Component\Notifier\Event\FailedMessageEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Mime\Email;

class NotifierLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}
    
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
            SentMessageEvent::class => 'onSentMessage',
            FailedMessageEvent::class => 'onFailedMessage',
        ];
    }
    
    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        
        $this->logger->debug('Notifier: Message event triggered', [
            'message_class' => get_class($message),
            'event_class' => get_class($event),
        ]);
    }
    
public function onSentMessage(SentMessageEvent $event): void
{
    try {
        $sentMessage = $event->getMessage();
        $originalMessage = $sentMessage->getOriginalMessage();
        
        // Простое логирование без сложного извлечения данных
        $this->logger->info('Notifier: Notification sent', [
            'sent_message_class' => get_class($sentMessage),
            'original_message_class' => get_class($originalMessage),
            'has_email' => method_exists($originalMessage, 'getMessage') ? 'yes' : 'no',
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
        
    } catch (\Exception $e) {
        $this->logger->error('Error in NotifierLoggerSubscriber', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
    
    public function onFailedMessage(FailedMessageEvent $event): void
    {
        $message = $event->getMessage();
        $recipientInfo = $this->extractRecipientInfo($message);
        $transport = $this->getTransportName($event);
        
        $this->logger->error('Notifier: Failed to send message', [
            'recipient' => $recipientInfo['email'] ?? $recipientInfo['phone'] ?? 'unknown',
            'error' => $event->getError()->getMessage(),
            'transport' => $transport,
        ]);
    }
    
    private function getTransportName($event): string
    {
        // Пытаемся получить имя транспорта через рефлексию
        try {
            $reflection = new \ReflectionClass($event); // ДОБАВЬ use \ReflectionClass;
            if ($reflection->hasProperty('transport')) {
                $property = $reflection->getProperty('transport');
                $property->setAccessible(true);
                return (string) $property->getValue($event);
            }
        } catch (\ReflectionException $e) { // ДОБАВЬ use \ReflectionException;
            // ignore
        }
        
        return 'unknown';
    }
    
    private function extractRecipientInfo(MessageInterface $message): array
    {
        $info = ['email' => null, 'phone' => null];
        
        // Для EmailMessage
        if (method_exists($message, 'getMessage')) {
            $rawMessage = $message->getMessage();
            
            if ($rawMessage instanceof Email && method_exists($rawMessage, 'getTo')) {
                $to = $rawMessage->getTo();
                if (!empty($to)) {
                    $firstRecipient = $to[0];
                    $info['email'] = method_exists($firstRecipient, 'getAddress') 
                        ? $firstRecipient->getAddress() 
                        : 'unknown';
                }
            }
        }
        
        // Для SmsMessage
        if (method_exists($message, 'getPhone')) {
            $info['phone'] = $message->getPhone();
        }
        
        return $info;
    }
}