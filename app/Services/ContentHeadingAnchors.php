<?php

namespace App\Services;

use Illuminate\Support\Str;

class ContentHeadingAnchors
{
    public function normalize(array $parts): array
    {
        $used = [];
        $walk = function (array &$value) use (&$walk, &$used): void {
            if (($value['type'] ?? null) === 'heading') {
                $existing = $value['attrs']['id'] ?? '';
                $base = is_string($existing) && preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $existing)
                    ? $existing : (self::slug($this->text($value)) ?: 'heading');
                $id = $base;
                $suffix = 1;
                while (isset($used[$id])) {
                    $id = $base.'-'.$suffix++;
                }
                $value['attrs']['id'] = $id;
                $used[$id] = true;
            }
            foreach ($value as &$child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk($parts);

        return $parts;
    }

    private function text(array $node): string
    {
        return $node['text'] ?? implode('', array_map($this->text(...), $node['content'] ?? []));
    }

    public static function slug(string $text): string
    {
        $text = strtr(Str::lower($text), ['ą' => 'a', 'č' => 'c', 'ę' => 'e', 'ė' => 'e', 'į' => 'i', 'š' => 's', 'ų' => 'u', 'ū' => 'u', 'ž' => 'z']);

        return substr(trim(preg_replace('/[^a-z0-9]+/', '-', $text), '-'), 0, 100);
    }
}
