<?php

use App\Enums\SharepointFieldEnum;
use App\Enums\SharepointPermissionTypeEnum;
use App\Enums\SharepointScopeEnum;

describe('SharepointGraphService enum integration', function () {
    test('uses correct permission scopes for API calls', function () {
        // Test enum values without instantiating the service to avoid SharePoint connection
        $anonymousScope = SharepointScopeEnum::ANONYMOUS();
        $organizationScope = SharepointScopeEnum::ORGANIZATION();

        expect($anonymousScope->label)->toBe('anonymous');
        expect($organizationScope->label)->toBe('organization');

        // These should be valid SharePoint API scope values
        expect(['anonymous', 'organization', 'users'])
            ->toContain($anonymousScope->label)
            ->toContain($organizationScope->label);
    });

    test('field enums match actual SharePoint field names', function () {
        // These field names must exactly match SharePoint's internal field names
        $titleField = SharepointFieldEnum::TITLE();
        $dateField = SharepointFieldEnum::DATE();

        expect($titleField->label)->toBe('Title');
        expect($dateField->label)->toBe('Date');

        // Test encoded field names for SharePoint compatibility
        $effectiveDate = SharepointFieldEnum::EFFECTIVE_DATE();
        expect($effectiveDate->label)->toContain('_x0020_'); // SharePoint space encoding
    });

    test('permission types are valid for SharePoint API', function () {
        $viewPermission = SharepointPermissionTypeEnum::VIEW();
        $editPermission = SharepointPermissionTypeEnum::EDIT();

        // These must be valid SharePoint permission role types
        expect(['view', 'edit', 'owner'])
            ->toContain($viewPermission->label)
            ->toContain($editPermission->label);
    });
});
