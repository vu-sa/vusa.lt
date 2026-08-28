<?php

use App\Support\Docs\DocClaimScanner;

beforeEach(function (): void {
    $this->docs = sys_get_temp_dir().'/docs-claims-'.uniqid();
    mkdir($this->docs.'/en', 0777, true);
});

afterEach(function (): void {
    foreach (glob($this->docs.'/{,en/}*.md', GLOB_BRACE) ?: [] as $file) {
        unlink($file);
    }
    @rmdir($this->docs.'/en');
    @rmdir($this->docs);
});

function writePage(string $path, string $contents): void
{
    file_put_contents($path, $contents);
}

describe('claim parsing', function (): void {
    test('reads a flat tests list out of frontmatter', function (): void {
        writePage($this->docs.'/reservations.md', <<<'MD'
        ---
        title: Rezervacijos
        tests:
          - tests/Feature/Admin/Resources/ReservationControllerTest.php
          - tests/Feature/Admin/Resources/ResourceControllerTest.php
        ---

        # Rezervacijos
        MD);

        $claims = (new DocClaimScanner)->scan($this->docs);
        $page = array_key_first($claims->claims);

        expect($claims->claims[$page])->toHaveCount(2)
            ->and($claims->claims[$page][0])->toContain('ReservationControllerTest.php');
    });

    test('treats a page without a tests key as citing no evidence', function (): void {
        // Most handbook pages are about VU SA procedure, which no test can prove.
        writePage($this->docs.'/procedure.md', "---\ntitle: Procedūra\n---\n\n# Procedūra\n");

        $claims = (new DocClaimScanner)->scan($this->docs);

        expect($claims->claims)->toBeEmpty()
            ->and($claims->unclaimedPages)->toHaveCount(1);
    });

    test('handles a page with no frontmatter at all', function (): void {
        writePage($this->docs.'/bare.md', "# Just prose\n");

        expect((new DocClaimScanner)->scan($this->docs)->unclaimedPages)->toHaveCount(1);
    });

    test('ignores translations so claims are never duplicated', function (): void {
        // docs/en/** is a translation, not an independent claim; reading both
        // would double every claim and guarantee drift between them.
        writePage($this->docs.'/en/reservations.md', "---\ntests:\n  - tests/Feature/Whatever.php\n---\n");

        $claims = (new DocClaimScanner)->scan($this->docs);

        expect($claims->claims)->toBeEmpty()
            ->and($claims->unclaimedPages)->toBeEmpty();
    });
});

describe('stale claims', function (): void {
    test('flags a claim whose test file no longer exists', function (): void {
        writePage($this->docs.'/gone.md', "---\ntests:\n  - tests/Feature/DoesNotExistTest.php\n---\n");

        $dangling = (new DocClaimScanner)->scan($this->docs)->danglingClaims();

        expect($dangling)->toHaveCount(1)
            ->and(array_first($dangling))->toContain('tests/Feature/DoesNotExistTest.php');
    });

    test('accepts a claim pointing at a real test file', function (): void {
        writePage($this->docs.'/real.md', "---\ntests:\n  - tests/Feature/Admin/Content/PageControllerTest.php\n---\n");

        expect((new DocClaimScanner)->scan($this->docs)->danglingClaims())->toBeEmpty();
    });
});
