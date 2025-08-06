# SharePoint Configuration Architecture

This document explains the dual configuration system used for SharePoint integration in the VUSA platform.

## Overview

The SharePoint integration uses two separate configuration systems to handle different types of settings:

1. **Technical Configuration** (`SharepointConfigEnum`) - Static constants
2. **Business Configuration** (`SharepointSettings`) - Dynamic settings

## Technical Configuration (SharepointConfigEnum)

**Location**: `app/Enums/SharepointConfigEnum.php`

**Purpose**: Hardcoded static values for technical configuration that should not change during runtime.

**Contains**:
- `API_BASE_URL` → 'https://graph.microsoft.com/v1.0/'
- `DEFAULT_TIMEOUT` → '30' (seconds)
- `MAX_RETRIES` → '3' (retry attempts)
- `RETRY_DELAY_MS` → '1000' (milliseconds)
- `DEFAULT_BATCH_SIZE` → '20' (items per batch)

**Usage**:
```php
use App\Enums\SharepointConfigEnum;

$apiUrl = SharepointConfigEnum::API_BASE_URL()->label;
$maxRetries = (int) SharepointConfigEnum::MAX_RETRIES()->label;
```

**When to use**: For values that are technical constants and should never change based on user preferences or admin configuration.

## Business Configuration (SharepointSettings)

**Location**: `app/Settings/SharepointSettings.php`

**Purpose**: User-configurable dynamic settings stored in the database that control business behavior.

**Contains**:
- `permission_expiry_days` (default: 365) - How long SharePoint permissions last
- `default_folder_structure` (default: 'General/{type}/{name}') - Folder naming pattern

**Usage**:
```php
use App\Settings\SharepointSettings;

$settings = app(SharepointSettings::class);
$expiryDays = $settings->permission_expiry_days;
$folderStructure = $settings->default_folder_structure;
```

**When to use**: For values that admins or users should be able to configure through the application interface.

## How They Work Together

In `SharepointGraphService`, both systems are used:

```php
class SharepointGraphService
{
    public function __construct(?SharepointSettings $settings = null)
    {
        // Technical constants from enum
        $this->graphApiBaseUrl = SharepointConfigEnum::API_BASE_URL()->label;
        
        // Business settings from database
        $this->settings = $settings ?? app(SharepointSettings::class);
    }
    
    public function createPublicPermission($datetime = null)
    {
        // Use business setting for permission expiry
        $datetime = $datetime ?? Carbon::now()->addDays($this->settings->permission_expiry_days);
    }
    
    private function executeWithRetry(callable $operation)
    {
        // Use technical constants for retry logic
        $maxRetries = (int) SharepointConfigEnum::MAX_RETRIES()->label;
        $delay = (int) SharepointConfigEnum::RETRY_DELAY_MS()->label;
    }
}
```

## Decision Matrix

| Setting Type | Use SharepointConfigEnum | Use SharepointSettings |
|--------------|-------------------------|----------------------|
| API endpoints | ✅ | ❌ |
| Timeout values | ✅ | ❌ |
| Retry logic | ✅ | ❌ |
| Permission expiry | ❌ | ✅ |
| Folder structure | ❌ | ✅ |
| User preferences | ❌ | ✅ |

## Testing

- **SharepointConfigEnum**: Tested in `tests/Unit/Enums/SharepointEnumsTest.php`
- **SharepointSettings**: Tested in `tests/Unit/Settings/SharepointSettingsTest.php`

Both are also integration tested in the various SharePoint service tests.

## Migration from SharepointConstants

The `SharepointConstants` class was replaced with this dual system:
- Technical constants moved to `SharepointConfigEnum`
- Business settings were already in `SharepointSettings`
- This provides better separation of concerns and type safety
