<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Support\Carbon;

/**
 * One folder of the SharePoint archive drive, mirrored by document discovery.
 *
 * @property string $drive_item_id
 * @property string|null $parent_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFolder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFolder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFolder query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
#[WithoutIncrementing]
class SharepointFolder extends Model
{
    #[\Override]
    protected $primaryKey = 'drive_item_id';

    #[\Override]
    protected $keyType = 'string';
}
