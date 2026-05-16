<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ShortUrlHelper;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentRedirectController extends Controller
{
    /**
     * Redirect from short URL to the actual document (SharePoint anonymous URL)
     */
    public function redirect(string $code): RedirectResponse
    {
        $id = ShortUrlHelper::decode($code);

        if ($id === null) {
            throw new NotFoundHttpException('Invalid document code');
        }

        $document = Document::find($id);

        if (! $document || empty($document->anonymous_url)) {
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
