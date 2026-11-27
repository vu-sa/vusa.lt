<?php

use App\Helpers\InternetShortcutParser;

test('parses the URL from a canonical CRLF .url file', function (): void {
    $contents = "[InternetShortcut]\r\nURL=https://ataskaita2023.vusa.lt\r\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt');
});

test('ignores other InternetShortcut keys', function (): void {
    $contents = "[InternetShortcut]\r\n"
        ."URL=https://ataskaita2023.vusa.lt\r\n"
        ."IconFile=https://sharepoint.example.com/icon.ico\r\n"
        ."IconIndex=0\r\n"
        ."HotKey=0\r\n"
        ."Modified=A0B1C2D3E4F5\r\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt');
});

test('strips a leading UTF-8 BOM', function (): void {
    $contents = "\xEF\xBB\xBF[InternetShortcut]\r\nURL=https://ataskaita2023.vusa.lt\r\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt');
});

test('accepts lowercase section header and key', function (): void {
    $contents = "[internetshortcut]\nurl=https://ataskaita2023.vusa.lt\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt');
});

test('a trailing GUID shell-extension section does not clobber the result', function (): void {
    $contents = "[InternetShortcut]\r\n"
        ."URL=https://ataskaita2023.vusa.lt\r\n"
        ."[{000214A0-0000-0000-C000-000000000046}]\r\n"
        ."Prop3=19,11\r\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt');
});

test('preserves a query string containing multiple parameters', function (): void {
    $contents = "[InternetShortcut]\r\nURL=https://ataskaita2023.vusa.lt/report?a=1&b=2\r\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt/report?a=1&b=2');
});

test('a URL line outside the InternetShortcut section is ignored', function (): void {
    $contents = "[SomeOtherSection]\r\nURL=https://ataskaita2023.vusa.lt\r\n";

    expect(InternetShortcutParser::parse($contents))->toBeNull();
});

test('rejects non-http(s) schemes', function (string $url): void {
    $contents = "[InternetShortcut]\r\nURL={$url}\r\n";

    expect(InternetShortcutParser::parse($contents))->toBeNull();
})->with([
    'javascript:alert(1)',
    'file:///etc/passwd',
    'ftp://example.com/file',
]);

test('rejects garbage, empty, and whitespace-only content', function (?string $contents): void {
    expect(InternetShortcutParser::parse($contents))->toBeNull();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ["  \n\t "],
    'no URL key' => ["[InternetShortcut]\r\nIconFile=https://example.com/icon.ico\r\n"],
    'non-URL value' => ["[InternetShortcut]\r\nURL=not a url\r\n"],
]);

test('trims leading and trailing whitespace from the value', function (): void {
    $contents = "[InternetShortcut]\r\nURL=  https://ataskaita2023.vusa.lt  \r\n";

    expect(InternetShortcutParser::parse($contents))->toBe('https://ataskaita2023.vusa.lt');
});
