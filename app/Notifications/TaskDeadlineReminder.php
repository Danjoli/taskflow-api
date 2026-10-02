<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task deadline reminder')
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Your task \"{$this->task->title}\" is due tomorrow.")
            ->line("Due date: {$this->task->due_date->toDateString()}")
            ->line('Please review its status in TaskFlow.');
    }
}
