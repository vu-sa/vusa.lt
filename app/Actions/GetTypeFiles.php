<?php

namespace App\Actions;

use App\Models\DutyType;
use App\Models\FileableFile;
use App\Models\InstitutionType;
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

        /** @var Collection<int, InstitutionType|DutyType> $types */
        $types = $fileable->types()->get();

        return self::forTypes($types, $limit);
    }

    /**
     * @template T of InstitutionType|DutyType
     *
     * @param  SupportCollection<int, T>  $types
     * @return Collection<int, FileableFile>
     */
    public static function forTypes(SupportCollection $types, ?int $limit = null): Collection
    {
        $byDomain = $types->flatMap(fn (InstitutionType|DutyType $type) => $type->getParentsAndSelf())
            ->groupBy(fn ($type) => $type->getMorphClass());

        if ($byDomain->isEmpty()) {
            return new Collection;
        }

        return FileableFile::query()
            ->where(function ($query) use ($byDomain): void {
                foreach ($byDomain as $alias => $domainTypes) {
                    $query->orWhere(fn ($group) => $group->where('fileable_type', $alias)
                        ->whereIn('fileable_id', $domainTypes->pluck('id')->unique()));
                }
            })
            ->available()
            ->with('fileable:id,title')
            ->orderByDesc('file_date')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
    }
}
