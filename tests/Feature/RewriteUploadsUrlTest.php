<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

describe('Legacy uploads URL redirect', function (): void {
    it('redirects uploads without the files segment', function (): void {
        // Sending the redirect inside the middleware echoes it before the kernel sends it again.
        $this->expectOutputString('');

        $this->get('/uploads/legacy/report.pdf')
            ->assertRedirect('/uploads/files/legacy/report.pdf');
    });
});
