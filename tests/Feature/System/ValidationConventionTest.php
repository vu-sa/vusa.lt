<?php

/**
 * Validation Convention Tests
 *
 * Validation lives in Form Requests, not inline in controllers. Inline `$request->validate()`
 * was how this codebase ended up with rules that drifted from what the controller actually
 * persisted — fields written with no rule at all (QuickLink's `lang`/`icon`, Relationship's
 * `description`, Banner's `link_url`), and duplicated rule blocks that fell out of sync.
 *
 * A Form Request also gives the arch layer something to inspect: RouteAuthorizationCoverageTest
 * can read its authorize(), which it cannot do for an inline array.
 *
 * The inline-validation exemption list is empty and should stay that way. If a new controller
 * needs to be added here, that is a review conversation, not a default.
 *
 * Reading input is held to the same standard: a controller that reads a field off a plain
 * `Illuminate\Http\Request` (or `request()`) skipped validation for it. That is how a public
 * endpoint's `limit=-1` lifted its own cap and `redirect_to` became an open redirect.
 *
 * @see .ai/rules/controllers.md
 * @see tests/Feature/System/RouteAuthorizationCoverageTest.php — the sibling arch guard
 */

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Controller methods still allowed to validate inline. Each entry needs a justification.
 *
 * @var array<int, string>
 */
const INLINE_VALIDATION_EXEMPTIONS = [];

/**
 * Every controller file under app/Http/Controllers.
 *
 * @return array<int, SplFileInfo>
 */
function controllerFiles(): array
{
    $files = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Http/Controllers'))) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file;
        }
    }

    return $files;
}

test('controllers do not validate inline', function (): void {
    $offenders = [];

    foreach (controllerFiles() as $file) {
        $contents = (string) file_get_contents($file->getPathname());
        $relative = Str::after($file->getPathname(), base_path().'/');

        if (in_array($relative, INLINE_VALIDATION_EXEMPTIONS, true)) {
            continue;
        }

        foreach (['$request->validate(', 'Validator::make('] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = "{$relative} uses {$needle}";
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBeEmpty();
});

/**
 * Controller methods allowed to read raw input, keyed `path::method`. Each entry needs a reason.
 *
 * @var array<string, string>
 */
const RAW_INPUT_EXEMPTIONS = [
    'app/Http/Controllers/Admin/AuthController.php::failOAuthError' => 'Microsoft sign-in error parameters are only logged.',
    'app/Http/Controllers/Admin/AuthController.php::storeFromMicrosoft' => 'Socialite reads the OAuth callback; the controller only checks whether it carries an error.',
    'app/Http/Controllers/AdminController.php::guardSelfLockout' => 'Shared helper behind many Form Requests; reads a confirmation flag as a boolean from body or query.',
    'app/Http/Controllers/Public/DocumentRedirectController.php::redirect' => 'Forwards SharePoint viewer parameters to an external URL; nothing reaches a query.',
    'app/Http/Controllers/Public/PublicPageController.php::calendarListLegacy' => '301 to calendar.list, whose IndexPublicCalendarRequest validates the forwarded query.',
    'app/Http/Controllers/PublicController.php::getCanonicalUrl' => 'Copies an allowlisted query string into the canonical URL.',
    'app/Http/Controllers/PublicController.php::sharePaginationSeoMeta' => 'Copies the query string into rel=prev/next links.',
];

/**
 * Reads of raw input in one PHP source: `->input()`, `->query()`, magic properties and the like
 * on a parameter typed `Illuminate\Http\Request`, and on `request()`. Asking who the user is,
 * for the session, a header or a route segment is not input and is not reported.
 *
 * @return list<array{method: string, read: string}>
 */
function rawRequestInputReads(string $source): array
{
    $inputMethods = [
        'input', 'query', 'post', 'get', 'all', 'only', 'except', 'collect', 'keys', 'json',
        'boolean', 'integer', 'float', 'string', 'str', 'date', 'enum', 'enums', 'array',
        'has', 'hasAny', 'filled', 'anyFilled', 'isNotFilled', 'missing', 'exists', 'whenHas', 'whenFilled',
        'file', 'hasFile', 'allFiles', 'getQueryString',
    ];
    $nonInputProperties = ['attributes', 'headers', 'server'];

    $tokens = array_values(array_filter(
        PhpToken::tokenize($source),
        fn (PhpToken $token): bool => ! $token->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]),
    ));
    $text = fn (int $at): string => $tokens[$at]->text ?? '';
    $isArrow = fn (int $at): bool => isset($tokens[$at]) && $tokens[$at]->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]);

    // `$request->name` after an arrow: an input read, or null when it is not one.
    $classify = function (int $nameAt) use ($tokens, $text, $inputMethods, $nonInputProperties): ?string {
        if (! isset($tokens[$nameAt]) || ! $tokens[$nameAt]->is(T_STRING)) {
            return null;
        }

        $name = $text($nameAt);

        if ($text($nameAt + 1) === '(') {
            return in_array($name, $inputMethods, true) ? "->{$name}()" : null;
        }

        return in_array($name, $nonInputProperties, true) ? null : "->{$name}";
    };

    $requestAlias = null;
    foreach ($tokens as $at => $token) {
        if ($token->is(T_USE) && $text($at + 1) === 'Illuminate\Http\Request') {
            $requestAlias = isset($tokens[$at + 2]) && $tokens[$at + 2]->is(T_AS) ? $text($at + 3) : 'Request';
        }
    }

    $reads = [];
    $method = '(class body)';
    $requestVariables = [];

    foreach ($tokens as $at => $token) {
        if ($token->is(T_FUNCTION) && isset($tokens[$at + 1]) && $tokens[$at + 1]->is(T_STRING)) {
            $method = $text($at + 1);
            $requestVariables = [];

            for ($cursor = $at + 2, $depth = 0; isset($tokens[$cursor]); $cursor++) {
                $depth += match ($text($cursor)) {
                    '(' => 1, ')' => -1, default => 0
                };

                if ($depth === 0) {
                    break;
                }

                $isPlainRequest = $text($cursor) === $requestAlias || $text($cursor) === '\\Illuminate\\Http\\Request';

                if ($isPlainRequest && isset($tokens[$cursor + 1]) && $tokens[$cursor + 1]->is(T_VARIABLE)) {
                    $requestVariables[] = $text($cursor + 1);
                }
            }

            continue;
        }

        if ($token->is(T_VARIABLE) && in_array($token->text, $requestVariables, true) && $isArrow($at + 1)) {
            if (($read = $classify($at + 2)) !== null) {
                $reads[] = ['method' => $method, 'read' => $token->text.$read];
            }
        }

        $isRequestHelper = $token->is(T_STRING) && $token->text === 'request' && $text($at + 1) === '('
            && ! (isset($tokens[$at - 1]) && $tokens[$at - 1]->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION]));

        if ($isRequestHelper) {
            if ($text($at + 2) !== ')') {
                $reads[] = ['method' => $method, 'read' => 'request(...)'];
            } elseif ($isArrow($at + 3) && ($read = $classify($at + 4)) !== null) {
                $reads[] = ['method' => $method, 'read' => 'request()'.$read];
            }
        }
    }

    return $reads;
}

test('controllers read input only through Form Requests', function (): void {
    $offenders = [];

    foreach (controllerFiles() as $file) {
        $relative = Str::after($file->getPathname(), base_path().'/');

        foreach (rawRequestInputReads((string) file_get_contents($file->getPathname())) as $read) {
            if (! array_key_exists("{$relative}::{$read['method']}", RAW_INPUT_EXEMPTIONS)) {
                $offenders[] = "{$relative}::{$read['method']} reads {$read['read']}";
            }
        }
    }

    sort($offenders);

    // Type-hint a Form Request and read validated()/safe(); a plain Request is fine for ->user().
    expect(array_values(array_unique($offenders)))->toBeEmpty();
});

test('the raw-input scan catches every way of reading a field', function (): void {
    $source = <<<'PHP'
        <?php
        use Illuminate\Http\Request;
        class Example {
            public function plain(Request $request) { $request->user(); $request->session(); $request->input('a'); $request->b; }
            public function helper() { request()->user(); request()->integer('c'); request('d'); request()->route('e'); }
            public function validated(StoreExampleRequest $request) { $request->input('f'); }
        }
        PHP;

    expect(rawRequestInputReads($source))->toBe([
        ['method' => 'plain', 'read' => '$request->input()'],
        ['method' => 'plain', 'read' => '$request->b'],
        ['method' => 'helper', 'read' => 'request()->integer()'],
        ['method' => 'helper', 'read' => 'request(...)'],
    ]);
});

test('index listings read the page size through BaseIndexRequest', function (): void {
    $offenders = [];

    foreach (controllerFiles() as $file) {
        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, "input('per_page'")) {
            $offenders[] = Str::after($file->getPathname(), base_path().'/');
        }
    }

    sort($offenders);

    // BaseIndexRequest::getPerPage() reads through validated(), so the max:100 cap always
    // applies; $request->input() bypasses it and re-hardcodes the default at every call site.
    expect($offenders)->toBeEmpty();
});

test('every Form Request declares rules', function (): void {
    $withoutRules = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Http/Requests'))) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $class = 'App\\Http\\Requests\\'.str_replace(
            '/',
            '\\',
            Str::of($file->getPathname())->after(app_path('Http/Requests').'/')->beforeLast('.php')->toString()
        );

        if (! class_exists($class) || ! is_subclass_of($class, FormRequest::class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            continue;
        }

        if (! $reflection->hasMethod('rules')) {
            $withoutRules[] = $class;
        }
    }

    sort($withoutRules);

    expect($withoutRules)->toBeEmpty();
});
