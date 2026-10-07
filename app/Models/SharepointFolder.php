<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * One folder of the SharePoint archive drive, mirrored by document discovery.
 *
 * @property string $drive_item_id
 * @property string|null $parent_id
 * @property string $name
 */
#[Unguarded]
class SharepointFolder extends Model
{
    protected $primaryKey = 'drive_item_id';

    protected $keyType = 'string';

    public $incrementing = false;
}
