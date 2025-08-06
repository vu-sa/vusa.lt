<?php

use App\Models\Institution;
use App\Models\SharepointFile;
use App\Services\ResourceServices\SharepointFileableService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SharepointFileableService', function () {
    beforeEach(function () {
        $this->service = new SharepointFileableService;
    });

    describe('attachFileToFileable', function () {
        test('successfully attaches file to valid fileable model', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            $result = $this->service->attachFileToFileable($sharepointFile, $institution);

            expect($result)->toBe($institution);
            expect($institution->files)->toContain($sharepointFile);
        });

        test('throws exception for model without HasSharepointFiles trait', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $modelWithoutTrait = new class extends Model {};

            expect(fn () => $this->service->attachFileToFileable($sharepointFile, $modelWithoutTrait))
                ->toThrow(\InvalidArgumentException::class, 'Model does not have HasSharepointFiles trait');
        });

        test('works with different model types that have the trait', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            $result = $this->service->attachFileToFileable($sharepointFile, $institution);

            expect($result)->toBe($institution);
            expect($institution->files)->toContain($sharepointFile);
        });

        test('handles multiple file attachments', function () {
            $sharepointFile1 = SharepointFile::factory()->create();
            $sharepointFile2 = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            $this->service->attachFileToFileable($sharepointFile1, $institution);
            $this->service->attachFileToFileable($sharepointFile2, $institution);

            expect($institution->files)->toHaveCount(2);
            expect($institution->files)->toContain($sharepointFile1);
            expect($institution->files)->toContain($sharepointFile2);
        });

        test('does not duplicate existing attachments', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            // Attach the same file twice
            $this->service->attachFileToFileable($sharepointFile, $institution);
            $this->service->attachFileToFileable($sharepointFile, $institution);

            // Should still only have one attachment
            expect($institution->files)->toHaveCount(1);
        });

        test('returns the same fileable instance', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            $result = $this->service->attachFileToFileable($sharepointFile, $institution);

            expect($result)->toBe($institution);
            expect($result->id)->toBe($institution->id);
        });

        test('validates trait using class_uses recursively', function () {
            // Create a class that extends a model with the trait
            $extendedModel = new class extends Institution {};
            $sharepointFile = SharepointFile::factory()->create();

            // Should work because Institution has the trait
            $result = $this->service->attachFileToFileable($sharepointFile, $extendedModel);

            expect($result)->toBeInstanceOf($extendedModel::class);
        });

        test('trait validation is case sensitive', function () {
            $sharepointFile = SharepointFile::factory()->create();

            // Create a model that doesn't have the trait
            $modelWithoutTrait = new class extends Model
            {
                protected $table = 'test_models';

                // This model doesn't use HasSharepointFiles trait
            };

            expect(fn () => $this->service->attachFileToFileable($sharepointFile, $modelWithoutTrait))
                ->toThrow(\InvalidArgumentException::class);
        });

        test('works with fresh model instances', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            // Get fresh instance from database
            $freshInstitution = Institution::find($institution->id);

            $result = $this->service->attachFileToFileable($sharepointFile, $freshInstitution);

            expect($result->files)->toContain($sharepointFile);
        });

        test('handles database persistence correctly', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            $this->service->attachFileToFileable($sharepointFile, $institution);

            // Verify the attachment persists in database
            $this->assertDatabaseHas('sharepoint_fileables', [
                'sharepoint_file_id' => $sharepointFile->id,
                'fileable_id' => $institution->id,
                'fileable_type' => Institution::class,
            ]);
        });

        test('maintains model relationships after attachment', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $institution = Institution::factory()->create();

            // Institution should maintain its existing relationships
            $originalTenantId = $institution->tenant_id;

            $result = $this->service->attachFileToFileable($sharepointFile, $institution);

            expect($result->tenant_id)->toBe($originalTenantId);
            expect($result->files)->toContain($sharepointFile);
        });

        test('error message is descriptive', function () {
            $sharepointFile = SharepointFile::factory()->create();
            $modelWithoutTrait = new class extends Model {};

            try {
                $this->service->attachFileToFileable($sharepointFile, $modelWithoutTrait);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (\InvalidArgumentException $e) {
                expect($e->getMessage())->toBe('Model does not have HasSharepointFiles trait');
            }
        });
    });

    describe('trait detection', function () {
        test('correctly identifies models with HasSharepointFiles trait', function () {
            $models = [
                new Institution,
                // Add other models that use the trait
            ];

            foreach ($models as $model) {
                $hasTraits = class_uses($model);
                expect($hasTraits)->toHaveKey(\App\Models\Traits\HasSharepointFiles::class);
            }
        });

        test('correctly identifies models without HasSharepointFiles trait', function () {
            $modelWithoutTrait = new class extends Model {};

            $hasTraits = class_uses($modelWithoutTrait);
            expect($hasTraits)->not()->toHaveKey(\App\Models\Traits\HasSharepointFiles::class);
        });

        test('handles inheritance correctly', function () {
            // Create a class that extends Institution
            $extendedInstitution = new class extends Institution {};

            // Should detect trait through inheritance
            $hasTraits = class_uses_recursive($extendedInstitution);
            expect($hasTraits)->toHaveKey(\App\Models\Traits\HasSharepointFiles::class);
        });
    });
});
