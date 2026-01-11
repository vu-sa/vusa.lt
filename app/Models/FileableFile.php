<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Local metadata storage for SharePoint files attached to fileables.
 *
 * This model stores file metadata locally to enable:
 * - Instant queries (e.g., "which meetings have protokolas?")
 * - File availability badges without API calls
 * - Tracking of externally deleted files
 *
 * @property string $id
 * @property string $fileable_type
 * @property string $fileable_id
 * @property string $sharepoint_id SharePoint drive item ID
 * @property string|null $sharepoint_path Canonical path in SharePoint
 * @property string $name Original filename
 * @property string|null $file_type Semantic type: protokolas, ataskaita, etc.
 * @property string|null $mime_type
 * @property int|null $size_bytes
 * @property \Illuminate\Support\Carbon|null $file_date Document date (not upload date)
 * @property string|null $description
 * @property string|null $public_link SharePoint anonymous sharing link
 * @property \Illuminate\Support\Carbon|null $public_link_expires_at
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property \Illuminate\Support\Carbon|null $deleted_externally_at Set when file deleted in SharePoint
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Model|\Eloquent $fileable
 * @property-read string|null $file_type_label
 * @property-read string|null $formatted_size
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile notDeletedExternally()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileableFile withoutTrashed()
 *
 * @mixin \Eloquent
 */
class FileableFile extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'file_date' => 'datetime',
        'public_link_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'deleted_externally_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    /**
     * Semantic file types for meetings and other fileables.
     * These match the SharePoint metadata labels.
     */
    public const TYPE_PROTOCOL = 'Protokolai';

    public const TYPE_REPORT = 'Ataskaitos';

    public const TYPE_AGENDA = 'Darbotvarkės';

    public const TYPE_METHODOLOGY = 'Metodinė medžiaga';

    public const TYPE_PRESENTATION = 'Pristatymai';

    public const TYPE_TEMPLATE = 'Šablonai';

    public const TYPE_REGULATION = 'Veiklą reglamentuojantys dokumentai';

    /**
     * Get all available file types.
     */
    public static function fileTypes(): array
    {
        return [
            self::TYPE_PROTOCOL => 'Protokolai',
            self::TYPE_REPORT => 'Ataskaitos',
            self::TYPE_AGENDA => 'Darbotvarkės',
            self::TYPE_METHODOLOGY => 'Metodinė medžiaga',
            self::TYPE_PRESENTATION => 'Pristatymai',
            self::TYPE_TEMPLATE => 'Šablonai',
            self::TYPE_REGULATION => 'Veiklą reglamentuojantys dokumentai',
        ];
    }

    /**
     * Get the parent fileable model (Meeting, Institution, Duty, Type).
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by semantic file type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope: Exclude files that were deleted externally in SharePoint.
     */
    public function scopeNotDeletedExternally($query)
    {
        return $query->whereNull('deleted_externally_at');
    }

    /**
     * Scope: Only files that are available (not soft deleted, not externally deleted).
     */
    public function scopeAvailable($query)
    {
        return $query->whereNull('deleted_externally_at')->whereNull('deleted_at');
    }

    /**
     * Mark this file as deleted externally (in SharePoint, not via app).
     */
    public function markAsDeletedExternally(): bool
    {
        return $this->update([
            'deleted_externally_at' => now(),
        ]);
    }

    /**
     * Check if the file was deleted externally.
     */
    public function isDeletedExternally(): bool
    {
        return $this->deleted_externally_at !== null;
    }

    /**
     * Check if the public link has expired.
     */
    public function hasExpiredPublicLink(): bool
    {
        return $this->public_link_expires_at !== null
            && $this->public_link_expires_at->isPast();
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedSizeAttribute(): ?string
    {
        if ($this->size_bytes === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->size_bytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get the file type label for display.
     */
    public function getFileTypeLabelAttribute(): ?string
    {
        if ($this->file_type === null) {
            return null;
        }

        return self::fileTypes()[$this->file_type] ?? $this->file_type;
    }
}
