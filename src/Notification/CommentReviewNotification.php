<?php

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackActionsBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackDividerBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackSectionBlock;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class CommentReviewNotification extends Notification implements EmailNotificationInterface, ChatNotificationInterface
{
    public function __construct(
        private Comment $comment,
        private string $reviewUrl,
    ) {
        parent::__construct('New comment posted');
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $email = (new TemplatedEmail())
            ->to($recipient->getEmail())
            ->subject('New comment posted')
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->context([
                'comment' => $this->comment,
                'importance' => 'high',
                'content' => sprintf('New comment from %s', $this->comment->getAuthor()),
                'exception' => false,
            ]);

        return new EmailMessage($email);
    }

    public function asChatMessage(RecipientInterface $recipient, string $transport = null): ?ChatMessage
    {
        if ('slack' !== $transport) {
            return null;
        }

        // Создаем ChatMessage напрямую вместо fromNotification()
        $message = new ChatMessage($this->getSubject());
        
        // Устанавливаем получателя (Slack channel)
        // В Slack Recipient - это обычно email или ID пользователя/канала
        // Но для Slack через DSN канал уже указан, поэтому можно пропустить
        // Если нужно отправлять разным каналам, используйте:
        // $message->recipient($recipient);
        
        $message->options((new SlackOptions())
            ->iconEmoji('tada')
            ->iconUrl('https://guestbook.example.com')
            ->username('Guestbook')
            ->block((new SlackSectionBlock())->text($this->getSubject()))
            ->block(new SlackDividerBlock())
            ->block((new SlackSectionBlock())
                ->text(sprintf('%s (%s) says: %s', 
                    $this->comment->getAuthor(), 
                    $this->comment->getEmail(), 
                    $this->comment->getText()
                ))
            )
            ->block((new SlackActionsBlock())
                ->button('Accept', $this->reviewUrl, 'primary')
                ->button('Reject', $this->reviewUrl.'?reject=1', 'danger')
            )
        );

        return $message;
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        if (preg_match('{\b(great|awesome)\b}i', $this->comment->getText())) {
            return ['email', 'chat/slack'];
        }

        $this->importance(Notification::IMPORTANCE_LOW);

        return ['email'];
    }
}