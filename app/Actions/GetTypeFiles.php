<?php

namespace App\Actions;

use App\Models\FileableFile;
use App\Models\Type;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Reference files kept on types (regulations, templates) — shared by every institution or duty
 * of that type and of its parent types.
 */
class GetTypeFiles
{
    /**
     * @return Collection<int, FileableFile>
     */
    public static function forFileable(Model $fileable, ?int $limit = null): Collection
    {
        if (! method_exists($fileable, 'types')) {
            return new Collection;
        }

        /** @var Collection<int, Type> $types */
        $types = $fileable->types()->get();

        return self::forTypes($types, $limit);
    }

    /**
     * @param  SupportCollection<int, Type>  $types
     * @return Collection<int, FileableFile>
     */
    public static function forTypes(SupportCollection $types, ?int $limit = null): Collection
    {
        $typeIds = $types
            ->flatMap(fn (Type $type) => $type->getParentsAndSelf())
            ->pluck('id')
            ->unique()
            ->values();

        if ($typeIds->isEmpty()) {
            return new Collection;
        }

        return FileableFile::query()
            ->where('fileable_type', MorphMap::alias(Type::class))
            ->whereIn('fileable_id', $typeIds)
            ->available()
            ->with('fileable:id,title')
            ->orderByDesc('file_date')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
    }
}
