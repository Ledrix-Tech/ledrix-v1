<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LeadAssigned implements ShouldBroadcast
{
    public $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('executive.' . $this->lead->executive_id);
    }

    public function broadcastWith()
    {
        return [
            'name' => $this->lead->name,
            'email' => $this->lead->email,
        ];
    }
}
