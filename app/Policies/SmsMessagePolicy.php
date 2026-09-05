<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmsMessage;
use App\Models\User;

/** Service SMS (§35) : consultation de l'historique et envoi manuel. */
class SmsMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('sms.view');
    }

    public function view(User $user, SmsMessage $message): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('sms.send');
    }

    public function retry(User $user, SmsMessage $message): bool
    {
        return $this->create($user) && $message->isRetryable();
    }
}
