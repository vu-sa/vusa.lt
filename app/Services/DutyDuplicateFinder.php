<?php

namespace App\Services;

use App\Models\Duty;
use Illuminate\Container\Attributes\Scoped;

/** Duties sharing an institution with another that DutySimilarityFinder would flag on the form. */
#[Scoped]
class DutyDuplicateFinder
{
    /** @var list<string>|null */
    private ?array $ids = null;

    /** @return list<string> */
    public function ids(): array
    {
        return $this->ids ??= $this->findIds();
    }

    /** @return list<string> */
    private function findIds(): array
    {
        $duplicates = [];

        $byInstitution = Duty::query()->select(['id', 'institution_id', 'name'])->with('formerNames:id,duty_id,name')
            ->whereNotNull('institution_id')->get()->groupBy('institution_id');

        foreach ($byInstitution as $duties) {
            $byName = [];

            foreach ($duties as $duty) {
                $key = DutyNameNormalizer::normalize(DutySimilarityFinder::dutyName($duty));

                if ($key !== '') {
                    $byName[$key][] = (string) $duty->id;
                }
            }

            foreach ($byName as $ids) {
                if (count($ids) > 1) {
                    $duplicates += array_fill_keys($ids, true);
                }
            }

            // A duty's former name matching another's current one is the same duty renamed twice.
            foreach ($duties as $duty) {
                foreach ($duty->formerNames as $former) {
                    foreach ($former->getTranslations('name') as $name) {
                        $others = array_diff($byName[DutyNameNormalizer::normalize(trim((string) $name))] ?? [], [(string) $duty->id]);

                        if ($others !== []) {
                            $duplicates += array_fill_keys([(string) $duty->id, ...$others], true);
                        }
                    }
                }
            }
        }

        return array_map('strval', array_keys($duplicates));
    }
}
