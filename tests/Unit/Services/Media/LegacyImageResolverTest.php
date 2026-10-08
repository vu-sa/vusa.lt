<?php

use App\Services\Media\LegacyImageResolver;
use App\Services\Media\LegacyImageSource;

beforeAll(function (): void {
    $root = sys_get_temp_dir().'/legacy-resolver-test';
    $files = [
        'uploads/news/„ARK šablonas“ kopija.webp',
        'uploads/contacts/jonas.webp',
        'uploads/files/padaliniai/vusachgf/Naujienos/Ataskaitine konferencija.jpg',
        'uploads/files/2020-2021/Dizainas be pavadinimo.png',
        'uploads/files/news/0038f7945f84feb0301de368915288aa349695fa.jpeg',
        'images/photos/observatorijos_kiemelis.jpg',
    ];

    foreach ($files as $file) {
        @mkdir(dirname("{$root}/{$file}"), recursive: true);
        touch("{$root}/{$file}");
    }
});

function resolverRoot(): string
{
    return sys_get_temp_dir().'/legacy-resolver-test';
}

function resolveLegacy(?string $value): LegacyImageSource
{
    return (new LegacyImageResolver(resolverRoot().'/uploads', resolverRoot()))->resolve($value);
}

test('a stored value resolves to the file behind it', function (string $value, string $expected): void {
    $source = resolveLegacy($value);

    expect($source->outcome)->toBe(LegacyImageSource::RESOLVED)
        ->and($source->path)->toBe(resolverRoot().'/'.$expected);
})->with([
    'relative shared folder' => ['/uploads/contacts/jonas.webp', 'uploads/contacts/jonas.webp'],
    'quotes and Lithuanian letters' => ['/uploads/news/„ARK šablonas“ kopija.webp', 'uploads/news/„ARK šablonas“ kopija.webp'],
    'percent-encoded' => ['/uploads/news/%E2%80%9EARK%20%C5%A1ablonas%E2%80%9C%20kopija.webp', 'uploads/news/„ARK šablonas“ kopija.webp'],
    'old static host, served from files/' => ['https://static.vusa.lt/uploads/padaliniai/vusachgf/Naujienos/Ataskaitine konferencija.jpg', 'uploads/files/padaliniai/vusachgf/Naujienos/Ataskaitine konferencija.jpg'],
    'file manager path on the main host' => ['https://vusa.lt/uploads/files/2020-2021/Dizainas be pavadinimo.png', 'uploads/files/2020-2021/Dizainas be pavadinimo.png'],
    'www host' => ['https://www.vusa.lt/uploads/contacts/jonas.webp', 'uploads/contacts/jonas.webp'],
    'bare sha1 of old news' => ['0038f7945f84feb0301de368915288aa349695fa.jpeg', 'uploads/files/news/0038f7945f84feb0301de368915288aa349695fa.jpeg'],
    'static public asset' => ['/images/photos/observatorijos_kiemelis.jpg', 'images/photos/observatorijos_kiemelis.jpg'],
]);

test('values that are not an image of ours resolve to nothing', function (?string $value, string $outcome): void {
    expect(resolveLegacy($value)->outcome)->toBe($outcome);
})->with([
    'old news default' => ['058543019bc51198ea1dc255580d215be99f2297.jpeg', LegacyImageSource::PLACEHOLDER],
    'another site' => ['https://via.placeholder.com/640x480.png', LegacyImageSource::FOREIGN],
    'club name typed into the photo field' => ['VU Fotoklubas', LegacyImageSource::JUNK],
    'path traversal' => ['/uploads/../.env', LegacyImageSource::JUNK],
    'file that is gone' => ['/uploads/news/deleted.jpg', LegacyImageSource::MISSING],
    'empty' => ['', LegacyImageSource::EMPTY],
    'null' => [null, LegacyImageSource::EMPTY],
]);
