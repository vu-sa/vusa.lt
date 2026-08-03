<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Services\ContactPresentationService;

describe('ContactPresentationService', function (): void {
    beforeEach(function (): void {
        $this->service = new ContactPresentationService;
    });

    describe('getGroupKey', function (): void {
        test('returns study program name for study_program grouping', function (): void {
            $studyProgram = new StudyProgram;
            $studyProgram->name = ['lt' => 'Informatika', 'en' => 'Computer Science'];

            $dutiable = new Dutiable;
            $dutiable->setRelation('study_program', $studyProgram);

            expect($this->service->getGroupKey($dutiable, 'study_program'))->toBe('Informatika');
        });

        test('returns translated fallback when study program is missing', function (): void {
            $dutiable = new Dutiable;
            $dutiable->setRelation('study_program', null);

            expect($this->service->getGroupKey($dutiable, 'study_program'))->toBe('Kita');
        });

        test('returns dutiable tenant shortname for tenant grouping', function (): void {
            $dutiable = new Dutiable;
            $dutiable->setRelation('tenant', new Tenant(['shortname' => 'VU MIF']));

            expect($this->service->getGroupKey($dutiable, 'tenant'))->toBe('VU MIF');
        });

        test('returns duty institution tenant shortname when dutiable tenant is null', function (): void {
            $institution = new Institution;
            $institution->setRelation('tenant', new Tenant(['shortname' => 'VU SA']));

            $duty = new Duty;
            $duty->setRelation('institution', $institution);

            $dutiable = new Dutiable;
            $dutiable->setRelation('tenant', null);

            expect($this->service->getGroupKey($dutiable, 'tenant', $duty))->toBe('VU SA');
        });

        test('returns translated fallback when no tenant can be resolved', function (): void {
            $dutiable = new Dutiable;
            $dutiable->setRelation('tenant', null);

            expect($this->service->getGroupKey($dutiable, 'tenant'))->toBe('Kita');
        });

        test('returns translated fallback for unknown grouping type', function (): void {
            $dutiable = new Dutiable;

            expect($this->service->getGroupKey($dutiable, 'unknown'))->toBe('Kita');
        });
    });
});
