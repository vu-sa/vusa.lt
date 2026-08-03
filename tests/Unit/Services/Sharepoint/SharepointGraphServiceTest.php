<?php

use App\Enums\SharepointFieldEnum;
use App\Enums\SharepointPermissionTypeEnum;
use App\Enums\SharepointScopeEnum;

describe('SharepointGraphService enum integration', function (): void {
    test('uses correct permission scopes for API calls', function (): void {
        // Test that enum values work with actual API methods
        $anonymousScope = SharepointScopeEnum::ANONYMOUS;
        $organizationScope = SharepointScopeEnum::ORGANIZATION;

        expect($anonymousScope->label())->toBe('anonymous')
            ->and($organizationScope->label())->toBe('organization');

        // These should be valid SharePoint API scope values
        expect(['anonymous', 'organization', 'users'])
            ->toContain($anonymousScope->label())
            ->toContain($organizationScope->label());
    });

    test('field enums match actual SharePoint field names', function (): void {
        // These field names must exactly match SharePoint's internal field names
        $titleField = SharepointFieldEnum::TITLE;
        $dateField = SharepointFieldEnum::DATE;

        expect($titleField->label())->toBe('Title')
            ->and($dateField->label())->toBe('Date');

        // Test encoded field names for SharePoint compatibility
        $effectiveDate = SharepointFieldEnum::EFFECTIVE_DATE;
        expect($effectiveDate->label())->toContain('_x0020_'); // SharePoint space encoding
    });

    test('permission types are valid for SharePoint API', function (): void {
        $viewPermission = SharepointPermissionTypeEnum::VIEW;
        $editPermission = SharepointPermissionTypeEnum::EDIT;

        // These must be valid SharePoint permission role types
        expect(['view', 'edit', 'owner'])
            ->toContain($viewPermission->label())
            ->toContain($editPermission->label());
    });
});
