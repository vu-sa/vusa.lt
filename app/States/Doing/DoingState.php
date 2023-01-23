<?php
namespace App\States\Doing;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class DoingState extends State
{  
    abstract public function color(): string;

    abstract public function handleProgress (): void;
    abstract public function handleApprove (): void;
    abstract public function handleReject (): void;
    abstract public function handleCancel (): void;

    // transition events are handled in the listener
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition([Draft::class, PendingChanges::class], PendingPadalinysApproval::class, TransitionToPendingPadalinysApproval::class) 
            ->allowTransitions([
                [PendingPadalinysApproval::class, PendingFinalApproval::class, TransitionPendingPadalinysToFinal::class], 
                [PendingFinalApproval::class, Approved::class, TransitionFinalToApproved::class], 
                [Approved::class, PendingCompletion::class, TransitionApprovedToPendingCompletion::class], 
                [PendingCompletion::class, Completed::class, TransitionPendingCompletionToCompleted::class], 
            ])
            ->allowTransition([PendingPadalinysApproval::class, PendingFinalApproval::class], PendingChanges::class, TransitionFromPendingToPendingChanges::class) 
            ->allowTransition([PendingChanges::class, PendingCompletion::class, Approved::class], Cancelled::class, TransitionToCancelled::class) 
        ;
    }
}