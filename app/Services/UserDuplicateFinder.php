<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Scoped;

#[Scoped]
class UserDuplicateFinder
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
        $groups = [];
        $index = [];

        foreach (User::query()->select(['id', 'name'])->get() as $user) {
            $tokens = UserSimilarityFinder::nameTokens((string) $user->name);

            if (count($tokens) < 2) {
                continue;
            }

            $key = implode(' ', $tokens);
            $groups[$key]['tokens'] = $tokens;
            $groups[$key]['ids'][] = (string) $user->id;

            foreach ($tokens as $token) {
                $index[$token][$key] = true;
            }
        }

        $duplicates = [];

        foreach ($groups as $key => $group) {
            $candidates = $index[$group['tokens'][0]];

            foreach (array_slice($group['tokens'], 1) as $token) {
                $candidates = array_intersect_key($candidates, $index[$token]);
            }

            foreach ($candidates as $candidate => $_) {
                if ($candidate === $key && count($group['ids']) < 2) {
                    continue;
                }

                if (UserSimilarityFinder::namesMatch($key, $candidate)) {
                    foreach ([...$group['ids'], ...$groups[$candidate]['ids']] as $id) {
                        $duplicates[$id] = true;
                    }
                }
            }
        }

        return array_keys($duplicates);
    }
}
