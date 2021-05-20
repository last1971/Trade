<?php

namespace App\Events;

use App\SellerGood;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerGoodUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SellerGood $sellerGood;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SellerGood $sellerGood)
    {
        $this->sellerGood = $sellerGood;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
