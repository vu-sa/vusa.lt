<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ShortUrlHelper;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentRedirectController extends Controller
{
    /**
     * Redirect from short URL to the actual document (SharePoint anonymous URL),
     * or straight to the resolved destination for `.url` internet shortcuts.
     */
    public function redirect(string $code): RedirectResponse
    {
        $id = ShortUrlHelper::decode($code);

        if ($id === null) {
            throw new NotFoundHttpException('Invalid document code');
        }

        $document = Document::find($id);

        if (! $document) {
            throw new NotFoundHttpException('Document not found or unavailable');
        }

        // Internet-shortcut documents (.url files) point at an external site.
        // Send visitors straight there instead of to the SharePoint viewer for
        // the shortcut blob. web=1 / download=1 are viewer parameters and query
        // forwarding does not apply to an external microsite.
        // Re-check the scheme: link_url is validated on sync, but this is the
        // last hop before redirect()->away(), which does no validation itself.
        if (! empty($document->link_url) && Str::startsWith($document->link_url, ['http://', 'https://'])) {
            return redirect()->away($document->link_url);
        }

        if (empty($document->anonymous_url)) {
            throw new NotFoundHttpException('Document not found or unavailable');
        }

        $targetUrl = $document->anonymous_url;
        $queryString = request()->getQueryString();

        if ($queryString) {
            $separator = str_contains($targetUrl, '?') ? '&' : '?';
            $targetUrl .= $separator.$queryString;
        }

        // Force browser rendering instead of native apps (e.g. OneDrive/Copilot on mobile).
        // Skip for downloads so the file downloads directly.
        if (! request()->boolean('download') && ! str_contains($targetUrl, 'web=1')) {
            $separator = str_contains($targetUrl, '?') ? '&' : '?';
            $targetUrl .= $separator.'web=1';
        }

        return redirect()->away($targetUrl);
    }
}
