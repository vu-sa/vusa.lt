<?php

namespace App\Models;

use App\Contracts\ImageMediaOwner;
use App\Models\Traits\HasImageMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An image uploaded from a form before the record it belongs to is saved. Saving the form moves
 * the media row onto the record (SyncImageMedia); whatever is never claimed is pruned.
 *
 * @property string $id
 * @property string $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaCollection<int, Media> $media
 * @property-read User|null $user
 *
 * @method static Builder<static>|PendingUpload newModelQuery()
 * @method static Builder<static>|PendingUpload newQuery()
 * @method static Builder<static>|PendingUpload query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['user_id'])]
class PendingUpload extends Model implements ImageMediaOwner
{
    use HasImageMedia;
    use HasUlids;
    use Prunable;

    public const string COLLECTION = 'upload';

    public const int MAX_PER_USER = 50;

    /** Outlives the 30-day content editor drafts that may still reference an upload. */
    public const int TTL_DAYS = 32;

    public function registerMediaCollections(): void
    {
        $this->registerImageCollection(self::COLLECTION);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Not MassPrunable: deleting through the model is what removes the media files.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays(self::TTL_DAYS));
    }

    /**
     * Keeps uploads a draft still points at from being pruned.
     *
     * @param  list<int>  $mediaIds
     */
    public static function touchForMedia(User $user, array $mediaIds): void
    {
        if ($mediaIds === []) {
            return;
        }

        static::query()
            ->whereBelongsTo($user)
            ->whereHas('media', fn (Builder $query) => $query->whereKey($mediaIds))
            ->update(['updated_at' => now()]);
    }
}
