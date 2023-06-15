<?php

namespace App\States\ReservationResource;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ReservationResourceState extends State
{
    abstract public function color(): string;

    abstract public function handleProgress(): void;

    abstract public function handleApprove(): void;

    abstract public function handleReject(): void;

    abstract public function handleCancel(): void;

    // transition events are handled in the listener
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Created::class)
            ->allowTransition([Created::class, Updated::class], Reserved::class)
            ->allowTransitions([
                [Created::class, Updated::class],
                [Updated::class, Updated::class],
                [Reserved::class, Lent::class],
                [Lent::class, Returned::class],
            ])
            ->allowTransition([Created::class, Updated::class], Rejected::class)
            ->allowTransition([Created::class, Reserved::class, Updated::class], Cancelled::class);
    }
}
