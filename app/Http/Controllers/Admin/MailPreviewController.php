<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\RenderMailPreviewRequest;
use App\Models\Role;
use App\Services\MailPreview\MailPreviewCatalog;
use App\Services\MailPreview\MailPreviewRenderer;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

class MailPreviewController extends AdminController
{
    #[Authorize('viewAny', Role::class)]
    public function index(MailPreviewCatalog $catalog): \Inertia\Response
    {
        return Inertia::render('Admin/MailPreviews', ['previews' => $catalog->index()]);
    }

    #[Authorize('viewAny', Role::class)]
    public function render(RenderMailPreviewRequest $request, string $preview, MailPreviewCatalog $catalog, MailPreviewRenderer $renderer): Response
    {
        return $renderer->response(
            $catalog->render($preview, $request->validated('locale', 'lt')),
            $request->validated('format', 'html'),
        );
    }
}
