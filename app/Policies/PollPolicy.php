<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    /**
     * Only the poll owner can update their own poll.
     */
    public function update(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id;
    }

    /**
     * Only the poll owner can cancel their own poll.
     */
    public function delete(User $user, Poll $poll): bool
    {
        return $user->id === $poll->user_id;
    }
}
