<?php

use App\Http\Requests\Api\Admin\AnalyticsOverviewRequest;
use App\Http\Requests\Api\Admin\InstitutionAliasPreviewRequest;
use App\Http\Requests\KnowledgeItemRequest;
use App\Http\Requests\KnowledgeSectionRequest;
use App\Http\Requests\PreviewContentPartsRequest;
use App\Http\Requests\ShowSvetaineDashboardRequest;
use App\Http\Requests\StoreDutiableRequest;
use App\Http\Requests\UpdateDutiableRequest;
use App\Http\Requests\UpdateQuickLinkOrderRequest;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\Finder\Finder;

const TENANT_ID_EXEMPTIONS = [
    AnalyticsOverviewRequest::class => 'AnalyticsApiController checks the tenant against the actor’s page-update scope before requesting analytics.',
    InstitutionAliasPreviewRequest::class => 'The tenant supplies the public URL and alias prefix for a read-only preview of globally reserved aliases.',
    KnowledgeItemRequest::class => 'The after() hook uses KnowledgeItemPolicy::targetTenant() when creating or moving the item.',
    KnowledgeSectionRequest::class => 'The after() hook authorizes the destination through KnowledgeSectionPolicy::target().',
    PreviewContentPartsRequest::class => 'The authorize() method limits previews to the editable record’s tenant or the actor’s create or update scope.',
    ShowSvetaineDashboardRequest::class => 'SvetaineDashboardController selects the tenant from the actor’s authorized page-update tenants.',
    StoreDutiableRequest::class => 'quotaTenantRules() permits only assignable tenants and requires the assignQuota ability.',
    UpdateDutiableRequest::class => 'quotaTenantRules() permits only assignable tenants and requires the assignQuota ability.',
    UpdateQuickLinkOrderRequest::class => 'The tenant is only a redirect filter, and each reordered link is authorized independently.',
];

function hasTopLevelTenantIdRule(ReflectionMethod $rules): bool
{
    $source = implode('', array_slice(
        file($rules->getFileName()),
        $rules->getStartLine() - 1,
        $rules->getEndLine() - $rules->getStartLine() + 1,
    ));
    $tokens = array_values(array_filter(
        PhpToken::tokenize('<?php '.$source),
        fn (PhpToken $token): bool => ! $token->isIgnorable(),
    ));

    $depth = 0;

    foreach ($tokens as $index => $token) {
        if ($depth === 1
            && $token->is(T_CONSTANT_ENCAPSED_STRING)
            && in_array($token->text, ["'tenant_id'", '"tenant_id"'], true)
            && ($tokens[$index + 1] ?? null)?->is(T_DOUBLE_ARROW)) {
            return true;
        }

        $depth += match (true) {
            $token->is(['[', T_ATTRIBUTE]) => 1,
            $token->is(']') => -1,
            default => 0,
        };
    }

    return false;
}

test('Form Requests scope top-level tenant IDs', function (): void {
    $unscoped = [];
    $files = Finder::create()->files()->in(app_path('Http/Requests'))->name('*.php');

    foreach ($files as $file) {
        $class = 'App\\Http\\Requests\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

        if (! is_subclass_of($class, FormRequest::class) || array_key_exists($class, TENANT_ID_EXEMPTIONS)) {
            continue;
        }

        $hasTenantRule = false;
        $scopeSource = '';

        for ($request = new ReflectionClass($class); $request->isSubclassOf(FormRequest::class); $request = $request->getParentClass()) {
            $scopeSource .= file_get_contents($request->getFileName());

            if ($request->hasMethod('rules')) {
                $rules = $request->getMethod('rules');
                $hasTenantRule = $hasTenantRule || hasTopLevelTenantIdRule($rules);
            }
        }

        if ($hasTenantRule && ! preg_match('/\b(?:tenantIdInAuthorizedScope|WithinAuthorizedTenantScope)\s*\(/', $scopeSource)) {
            $unscoped[] = $class;
        }
    }

    sort($unscoped);

    expect($unscoped)->toBeEmpty(implode("\n", $unscoped));
});
