<?php

namespace App\States\Doing;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class DoingState extends State
{
    abstract public function color(): string;

    /**
     * Defaul implementation is empty (does nothing)
     */
    public function handleProgress(): void
    {
    }

    /**
     * Defaul implementation is empty (does nothing)
     */
    public function handleApprove(): void
    {
    }

    /**
     * Defaul implementation is empty (does nothing)
     */
    public function handleReject(): void
    {
    }

    /**
     * Defaul implementation is empty (does nothing)
     */
    public function handleCancel(): void
    {
    }

    // transition events are handled in the listener
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition([Draft::class, PendingChanges::class], PendingPadalinysApproval::class)
            ->allowTransitions([
                [PendingPadalinysApproval::class, PendingFinalApproval::class],
                [PendingFinalApproval::class, Approved::class],
                [Approved::class, PendingCompletion::class],
                [PendingCompletion::class, Completed::class],
            ])
            ->allowTransition([PendingPadalinysApproval::class, PendingFinalApproval::class], PendingChanges::class)
            ->allowTransition([PendingChanges::class, PendingCompletion::class, Approved::class], Cancelled::class);
    }
}
