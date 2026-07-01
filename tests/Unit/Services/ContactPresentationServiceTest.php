<?php

use App\Services\ContactPresentationService;

describe('ContactPresentationService', function () {
    beforeEach(function () {
        $this->service = new ContactPresentationService;
    });

    describe('getGroupKey', function () {
        test('returns study program name for study_program grouping', function () {
            $dutiable = new stdClass;
            $dutiable->study_program = (object) ['name' => 'Computer Science'];

            expect($this->service->getGroupKey($dutiable, 'study_program'))->toBe('Computer Science');
        });

        test('returns Other when study program is missing', function () {
            $dutiable = new stdClass;
            $dutiable->study_program = null;

            expect($this->service->getGroupKey($dutiable, 'study_program'))->toBe('Other');
        });

        test('returns tenant shortname for tenant grouping', function () {
            $dutiable = new stdClass;
            $dutiable->study_program = (object) ['tenant' => (object) ['shortname' => 'MIF']];

            expect($this->service->getGroupKey($dutiable, 'tenant'))->toBe('MIF');
        });

        test('returns Other for unknown grouping type', function () {
            $dutiable = new stdClass;

            expect($this->service->getGroupKey($dutiable, 'unknown'))->toBe('Other');
        });
    });
});
