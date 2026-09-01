<?php

use App\Models\Document;
use App\Services\SharepointGraphService;
use Microsoft\Graph\Generated\Models\Permission;
use Microsoft\Graph\Generated\Models\PermissionCollectionResponse;
use Microsoft\Graph\Generated\Models\SharingLink;

/**
 * Builds a service whose Graph call is replaced by a fixed permission list.
 *
 * @param  array<int, Permission>  $permissions
 */
function fakePermissionService(array $permissions): SharepointGraphService
{
    return new class($permissions) extends SharepointGraphService
    {
        /** @param array<int, Permission> $permissions */
        public function __construct(private array $permissions)
        {
            parent::__construct(siteId: 'test-site', driveId: 'test-drive', listId: null);
        }

        #[Override]
        protected function getDriveItemPermissions(string $driveItemId): PermissionCollectionResponse
        {
            $response = new PermissionCollectionResponse;
            $response->setValue($this->permissions);

            return $response;
        }
    };
}

function anonymousPermission(string $id, ?string $webUrl): Permission
{
    $link = new SharingLink;
    $link->setScope('anonymous');
    $link->setType('view');

    if ($webUrl !== null) {
        $link->setWebUrl($webUrl);
    }

    $permission = new Permission;
    $permission->setId($id);
    $permission->setLink($link);

    return $permission;
}

describe('getDriveItemPublicLink', function (): void {
    test('ignores an anonymous permission whose webUrl Graph withheld', function (): void {
        // Graph drops link.webUrl once an identity is granted on an "anyone" link.
        $service = fakePermissionService([
            anonymousPermission('3c7581d4-4537-466b-8654-9ce078a78f06', null),
        ]);

        expect($service->getDriveItemPublicLink('drive-item-id'))->toBeNull();
    });

    test('picks the readable permission when a urlless one is listed first', function (): void {
        $service = fakePermissionService([
            anonymousPermission('withheld-url', null),
            anonymousPermission('usable', 'https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/IQBuUQuoGF9SYOZie4WZ0AiASIHzNkp6N4awW08YsBgU'),
        ]);

        $permission = $service->getDriveItemPublicLink('drive-item-id');

        expect($permission?->getId())->toBe('usable');
    });

    test('still rejects folder links', function (): void {
        $service = fakePermissionService([
            anonymousPermission('folder', 'https://vustudentuatstovybe.sharepoint.com/:f:/s/vieningai/IQBuUQuoGF9SYOZie4WZ0Ai'),
        ]);

        expect($service->getDriveItemPublicLink('drive-item-id'))->toBeNull();
    });
});

describe('applyImportedPublicLink', function (): void {
    beforeEach(function (): void {
        $this->apply = function (?array $permission): Document {
            $document = new Document(['sharepoint_id' => 'list-item-id', 'title' => 'Protokolas']);

            (new ReflectionMethod(SharepointGraphService::class, 'applyImportedPublicLink'))
                ->invoke(fakePermissionService([]), $document, $permission);

            return $document;
        };
    });

    test('marks the import failed when link creation produced no url', function (): void {
        $document = ($this->apply)(null);

        expect($document->anonymous_url)->toBeNull()
            ->and($document->sync_status)->toBe('failed')
            ->and($document->sync_error_message)->toBe('Imported without a public link')
            // Left null so the rolling refresh treats the document as critical.
            ->and($document->checked_at)->toBeNull();
    });

    test('stores the link and marks the document imported', function (): void {
        $url = 'https://vustudentuatstovybe.sharepoint.com/:b:/s/vieningai/IQBuUQuoGF9SYOZie4WZ0Ai';

        $document = ($this->apply)(['id' => 'permission-id', 'link' => ['webUrl' => $url]]);

        expect($document->anonymous_url)->toBe($url)
            ->and($document->sharepoint_permission_id)->toBe('permission-id')
            ->and($document->sync_status)->toBe('imported')
            ->and($document->checked_at)->not->toBeNull();
    });
});
