<?php

namespace App\Support\Docs;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Extracts, from the test suite's source, which routes each test file names and
 * which assertions it uses.
 *
 * Parses rather than greps: 26 test names in tests/Feature contain escaped
 * apostrophes that truncate under a regex, and Pest's own --list-tests output
 * is unusable here — it mangles names (`can't` becomes `can_t`) and attributes
 * every test to the eval()'d TestCaseFactory rather than its source file.
 */
class TestSurfaceScanner
{
    /**
     * Assertions that say something beyond "the request did not blow up".
     * Deliberately excludes assertStatus/assertOk/assertForbidden and the
     * project's toBeSecureResponse()/toRequireAuth() expectations, which are
     * status checks wearing a nicer hat.
     *
     * @var list<string>
     */
    private const array MEANINGFUL_ASSERTIONS = [
        'assertDatabaseHas', 'assertDatabaseMissing', 'assertDatabaseCount',
        'assertInertia', 'assertSessionHasErrors', 'assertSessionHas',
        'assertSoftDeleted', 'assertNotSoftDeleted', 'assertJsonFragment',
        'assertJsonPath', 'assertJsonCount', 'assertSee', 'assertDontSee',
        'assertDownload', 'assertRedirectToRoute', 'assertViewHas',
    ];

    /**
     * Test names (with describe context) declared by a single PHP source string.
     *
     * Takes source rather than a path so a file's previous revision can be read
     * straight out of `git show` without checking anything out.
     *
     * @return list<string>
     */
    public function testNamesIn(string $source): array
    {
        $ast = (new ParserFactory)->createForNewestSupportedVersion()->parse($source);

        if ($ast === null) {
            return [];
        }

        $visitor = new TestSurfaceVisitor(self::MEANINGFUL_ASSERTIONS);
        new NodeTraverser($visitor)->traverse($ast);

        return $visitor->testNames;
    }

    public function scan(string $directory): TestSurface
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $routes = [];
        $assertions = [];
        $fileCount = 0;
        $testCount = 0;

        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
            $ast = $parser->parse((string) file_get_contents($file->getPathname()));

            if ($ast === null) {
                continue;
            }

            $visitor = new TestSurfaceVisitor(self::MEANINGFUL_ASSERTIONS);
            $traverser = new NodeTraverser($visitor);
            $traverser->traverse($ast);

            $fileCount++;
            $testCount += $visitor->testCount;

            $relative = str_replace(base_path().'/', '', $file->getPathname());

            if ($visitor->assertions !== []) {
                $assertions[$relative] = array_values(array_unique($visitor->assertions));
            }

            foreach (array_unique($visitor->routes) as $route) {
                $routes[$route][] = $relative;
            }
        }

        ksort($routes);

        return new TestSurface($routes, $assertions, $fileCount, $testCount);
    }
}

/**
 * @internal
 */
class TestSurfaceVisitor extends NodeVisitorAbstract
{
    /** @var list<string> */
    public array $routes = [];

    /** @var list<string> */
    public array $assertions = [];

    /**
     * Test names, prefixed with their describe() context.
     *
     * @var list<string>
     */
    public array $testNames = [];

    public int $testCount = 0;

    /** @var list<string> */
    private array $describeStack = [];

    /**
     * @param  list<string>  $meaningfulAssertions
     */
    public function __construct(private readonly array $meaningfulAssertions) {}

    public function enterNode(Node $node): null
    {
        if ($node instanceof FuncCall && $node->name instanceof Node\Name) {
            $this->enterFuncCall($node, $node->name->toString());
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $name = $node->name->toString();

            if (in_array($name, $this->meaningfulAssertions, true)) {
                $this->assertions[] = $name;
            }
        }

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof FuncCall
            && $node->name instanceof Node\Name
            && $node->name->toString() === 'describe'
            && $this->describeStack !== []) {
            array_pop($this->describeStack);
        }

        return null;
    }

    private function enterFuncCall(FuncCall $node, string $name): void
    {
        if ($name === 'describe') {
            // Pushed on the way in and popped in leaveNode, so nested describes
            // read as the path a person would see in Pest's own output.
            $this->describeStack[] = $this->firstStringArg($node) ?? '?';

            return;
        }

        if ($name === 'it' || $name === 'test') {
            $this->testCount++;

            if (($label = $this->firstStringArg($node)) !== null) {
                $this->testNames[] = implode(' → ', [...$this->describeStack, $label]);
            }

            return;
        }

        if ($name !== 'route') {
            return;
        }

        // route($variable) can't be resolved statically; ignoring it only ever
        // understates coverage, which is the safe direction for this report.
        if (($route = $this->firstStringArg($node)) !== null) {
            $this->routes[] = $route;
        }
    }

    private function firstStringArg(FuncCall $node): ?string
    {
        $first = $node->args[0] ?? null;

        if ($first instanceof Node\Arg && $first->value instanceof String_) {
            return $first->value->value;
        }

        return null;
    }
}
