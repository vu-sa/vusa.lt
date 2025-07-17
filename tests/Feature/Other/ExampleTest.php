<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('returns a successful response', function () {
    $response = $this->get('/lt');

    $response->assertStatus(200);
});
