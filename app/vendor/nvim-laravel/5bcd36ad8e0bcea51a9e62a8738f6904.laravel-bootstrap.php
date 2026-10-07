<?php
error_reporting(E_ERROR | E_PARSE);

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../autoload.php';

class LaraveNvim
{
    public static function relativePath($path)
    {
        if (!str_contains($path, base_path())) {
            return (string) $path;
        }

        return ltrim(str_replace(base_path(), '', realpath($path) ?: $path), DIRECTORY_SEPARATOR);
    }

    public static function isVendor($path)
    {
        return str_contains($path, base_path("vendor"));
    }

    public static function outputMarker($key)
    {
        return '__NEOVIM_LARAVEL_' . $key . '__';
    }

    public static function startupError(\Throwable $e)
    {
        throw new Error(self::outputMarker('STARTUP_ERROR') . ': ' . $e->getMessage());
    }
}

try {
    $app = require_once __DIR__ . '/../../bootstrap/app.php';
} catch (\Throwable $e) {
    LaraveNvim::startupError($e);
    exit(1);
}

$app->register(new class($app) extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        config([
            'logging.channels.null' => [
                'driver' => 'monolog',
                'handler' => \Monolog\Handler\NullHandler::class,
            ],
            'logging.default' => 'null',
        ]);
    }
});

try {
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (\Throwable $e) {
    LaraveNvim::startupError($e);
    exit(1);
}

echo LaraveNvim::outputMarker('START_OUTPUT');
$livewire = new class {
    protected $namespaces;
    protected $paths;
    protected $extensions;

    public function __construct()
    {
        $this->namespaces = collect(
            config("livewire.component_namespaces", [])
        )->map(LaraveNvim::relativePath(...));

        $this->paths = collect($this->namespaces->values())
            ->merge(config("livewire.component_locations", []))
            ->unique()
            ->map(LaraveNvim::relativePath(...));

        $this->extensions = array_map(
            fn($extension) => ".{$extension}",
            app('view')->getExtensions(),
        );
    }

    public function parse(\Illuminate\Support\Collection $views)
    {
        if (!$this->isVersionFour()) {
            return $views;
        }

        return $views
            ->map(function (array $view) {
                if (!$this->pathExists($view["path"])) {
                    return $view;
                }

                if (!$this->componentExists($key = $this->key($view))) {
                    return $view;
                }

                if ($this->isMfc($view) && str($view['path'])->doesntEndWith('.blade.php')) {
                    return null;
                }

                return array_merge($view, [
                    "key" => $key,
                    "isLivewire" => true,
                ]);
            })
            ->whereNotNull()
            ->unique('key')
            ->values();
    }

    protected function isVersionFour(): bool
    {
        return property_exists(\Livewire\LivewireManager::class, "v4") &&
            \Livewire\LivewireManager::$v4;
    }

    protected function pathExists(string $path): bool
    {
        return $this->paths->contains(fn (string $item) => str($path)->contains($item));
    }

    protected function componentExists(string $key): bool
    {
        try {
            app("livewire")->new($key);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    protected function key(array $view): string
    {
        $livewirePath = $this->paths->firstWhere(
            fn(string $path) => str($view["path"])->startsWith($path)
        );

        $namespace = $this->namespaces->search($livewirePath);

        return str($view["path"])
            ->replace($livewirePath, "")
            ->replace("⚡", "")
            ->beforeLast(".blade.php")
            ->ltrim(DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, ".")
            ->when($namespace, fn($key) => $key->prepend($namespace . "::"))
            ->when($this->isMfc($view), fn($key) => $key->beforeLast("."))
            ->toString();
    }

    protected function isMfc(array $view): bool
    {
        $path = str($view["path"])->replace("⚡", "");

        $file = str($path)
            ->basename()
            ->beforeLast(".blade.php");

        $directory = str($path)
            ->dirname()
            ->afterLast(DIRECTORY_SEPARATOR);

        $class = str($view["path"])
            ->dirname()
            ->append(DIRECTORY_SEPARATOR . $file . ".php");

        return $file->is($directory) &&
            \Illuminate\Support\Facades\File::exists($class);
    }
};

$blade = new class ($livewire) {
    public function __construct(protected $livewire)
    {
        //
    }

    public function getAllViews()
    {
        $finder = app("view")->getFinder();

        $paths = collect($finder->getPaths())->flatMap(fn($path) => $this->findViews($path));

        $hints = collect($finder->getHints())
            ->filter(
                fn ($_, $key) => ! (strlen($key) === 32 && ctype_xdigit($key))
            )->flatMap(
                fn($paths, $key) => collect($paths)->flatMap(
                    fn($path) => collect($this->findViews($path))->map(
                        fn($value) => array_merge($value, ["key" => "{$key}::{$value["key"]}"])
                    )
                )
            );

        [$local, $vendor] = $paths
            ->merge($hints)
            ->values()
            ->partition(fn($v) => !$v["isVendor"]);

        return $local
            ->sortBy("key", SORT_NATURAL)
            ->merge($vendor->sortBy("key", SORT_NATURAL))
            ->pipe($this->livewire->parse(...));
    }

    public function getAllComponents()
    {
        $namespaced = \Illuminate\Support\Facades\Blade::getClassComponentNamespaces();
        $autoloaded = require base_path("vendor/composer/autoload_psr4.php");
        $components = [];

        foreach ($namespaced as $key => $ns) {
            $path = null;

            foreach ($autoloaded as $namespace => $paths) {
                if (str_starts_with($ns, $namespace)) {
                    foreach ($paths as $p) {
                        $test = str($ns)->replace($namespace, '')->replace('\\', '/')->prepend($p . DIRECTORY_SEPARATOR)->toString();

                        if (is_dir($test)) {
                            $path = $test;
                            break;
                        }
                    }

                    break;
                }
            }

            if (!$path) {
                continue;
            }

            $files = \Symfony\Component\Finder\Finder::create()
                ->files()
                ->name("*.php")
                ->in($path);

            foreach ($files as $file) {
                $realPath = $file->getRealPath();

                $components[] = [
                    "path" => str_replace(base_path(DIRECTORY_SEPARATOR), '', $realPath),
                    "isVendor" => str_contains($realPath, base_path("vendor")),
                    "key" =>  str($realPath)
                        ->replace(realpath($path), "")
                        ->replace(".php", "")
                        ->ltrim(DIRECTORY_SEPARATOR)
                        ->replace(DIRECTORY_SEPARATOR, ".")
                        ->kebab()
                        ->prepend($key . "::"),
                ];
            }
        }

        return $components;
    }

    protected function findViews($path)
    {
        $paths = [];

        if (!is_dir($path)) {
            return $paths;
        }

        $finder = app("view")->getFinder();
        $extensions = array_map(fn($extension) => ".{$extension}", $finder->getExtensions());

        $files = \Symfony\Component\Finder\Finder::create()
            ->files()
            ->name(array_map(fn ($ext) => "*{$ext}", $extensions))
            ->in($path);

        foreach ($files as $file) {
            $paths[] = [
                "path" => str_replace(base_path(DIRECTORY_SEPARATOR), '', $file->getRealPath()),
                "isVendor" => str_contains($file->getRealPath(), base_path("vendor")),
                "key" => $key = str($file->getRealPath())
                    ->replace(realpath($path), "")
                    ->replace($extensions, "")
                    ->ltrim(DIRECTORY_SEPARATOR)
                    ->replace(DIRECTORY_SEPARATOR, "."),
                "isLivewire" => $key->startsWith("livewire."),
            ];
        }

        return $paths;
    }
};

echo json_encode($blade->getAllViews()->merge($blade->getAllComponents()));
;
echo LaraveNvim::outputMarker('END_OUTPUT');

exit(0);
