<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a successful response', function () {
    $response = $this->get('/lt');

    $response->assertStatus(200);
});
