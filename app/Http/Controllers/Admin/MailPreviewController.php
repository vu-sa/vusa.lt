<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\RenderMailPreviewRequest;
use App\Models\Role;
use App\Services\MailPreview\MailPreviewCatalog;
use App\Services\MailPreview\MailPreviewRenderer;
use Illuminate\Http\Response;

class MailPreviewController extends AdminController
{
    public function index(MailPreviewCatalog $catalog): \Inertia\Response
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $this->inertiaResponse('Admin/MailPreviews', ['previews' => $catalog->index()]);
    }

    public function render(RenderMailPreviewRequest $request, string $preview, MailPreviewCatalog $catalog, MailPreviewRenderer $renderer): Response
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $renderer->response(
            $catalog->render($preview, $request->validated('locale', 'lt')),
            $request->validated('format', 'html'),
        );
    }
}
