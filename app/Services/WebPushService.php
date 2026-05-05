<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\PushSubscription as StoredSubscription;
use App\Models\Task;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function configured(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'))
            && filled(AppSetting::publicAppUrl());
    }

    public function sendTask(Task $task, array $payload): array
    {
        if (! $this->configured()) {
            return ['attempted' => 0, 'sent' => 0, 'expired' => 0];
        }

        $subscriptions = $task->user->pushSubscriptions;
        $attempted = 0;
        $sent = 0;
        $expired = 0;

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => AppSetting::publicAppUrl(),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], [
            'TTL' => 3600,
            'urgency' => $payload['status'] === 'escalated' ? 'high' : 'normal',
            'batchSize' => 100,
        ]);

        foreach ($subscriptions as $storedSubscription) {
            $attempted++;

            try {
                $report = $webPush->sendOneNotification(
                    $this->subscriptionFrom($storedSubscription),
                    json_encode($payload, JSON_THROW_ON_ERROR),
                    ['topic' => 'task-'.$task->id],
                );
            } catch (\Throwable) {
                $storedSubscription->update(['failed_at' => now()]);

                continue;
            }

            if ($report->isSuccess()) {
                $sent++;
                $storedSubscription->update([
                    'last_seen_at' => now(),
                    'failed_at' => null,
                ]);

                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $expired++;
                $storedSubscription->delete();
            } else {
                $storedSubscription->update(['failed_at' => now()]);
            }
        }

        return compact('attempted', 'sent', 'expired');
    }

    private function subscriptionFrom(StoredSubscription $storedSubscription): Subscription
    {
        return Subscription::create([
            'endpoint' => $storedSubscription->endpoint,
            'keys' => [
                'p256dh' => $storedSubscription->public_key,
                'auth' => $storedSubscription->auth_token,
            ],
            'contentEncoding' => $storedSubscription->content_encoding,
        ]);
    }
}
