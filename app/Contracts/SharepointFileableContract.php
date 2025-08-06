<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Contract for models that can have SharePoint files attached
 *
 * This interface ensures models have the required methods for SharePoint file relationships
 */
interface SharepointFileableContract
{
    /**
     * Get the SharePoint files relationship
     */
    public function files(): MorphToMany;

    /**
     * Get the SharePoint folder path for this model
     */
    public function sharepoint_path(): string;
}