<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Workspace;
use App\Models\WorkspaceDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + persistence endpoints for workspace collaborative documents.
 *
 * Real-time syncing happens peer-to-peer over the Reverb presence channel
 * (workspace-documents.{id}); the state endpoints only hydrate late joiners
 * and store a durable snapshot — the same split as agenda-item notes.
 * Every action is gated by the workspace "update" ability (all collaborators
 * may create and edit documents).
 */
class WorkspaceDocumentController extends ApiController
{
    /**
     * List the workspace's documents (without CRDT payloads).
     */
    public function index(Workspace $workspace): JsonResponse
    {
        $this->authorizeApi('view', $workspace);

        $documents = $workspace->documents()
            ->select('id', 'workspace_id', 'title', 'updated_by', 'updated_at')
            ->with('editor:id,name')
            ->latest('updated_at')
            ->get();

        return $this->jsonSuccess($documents);
    }

    /**
     * Create a named document.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeApi('update', $workspace);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $document = $workspace->documents()->create([
            'title' => $validated['title'],
            'updated_by' => $request->user()?->id,
        ]);

        return $this->jsonSuccess($document->only(['id', 'workspace_id', 'title', 'updated_at']), __('workspaces.document_created'));
    }

    /**
     * Rename a document.
     */
    public function update(Request $request, Workspace $workspace, WorkspaceDocument $document): JsonResponse
    {
        $this->authorizeApi('update', $workspace);
        $this->assertParentage($workspace, $document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $document->update(['title' => $validated['title']]);

        return $this->jsonSuccess($document->only(['id', 'title']), __('workspaces.document_updated'));
    }

    /**
     * Archive (soft-delete) a document.
     */
    public function destroy(Workspace $workspace, WorkspaceDocument $document): JsonResponse
    {
        $this->authorizeApi('update', $workspace);
        $this->assertParentage($workspace, $document);

        $document->delete();

        return $this->jsonSuccess(null, __('workspaces.document_archived'));
    }

    /**
     * Return the persisted Y.js snapshot + HTML for a document, plus the
     * mentionable collaborator pool.
     */
    public function showState(Workspace $workspace, WorkspaceDocument $document): JsonResponse
    {
        $this->authorizeApi('view', $workspace);
        $this->assertParentage($workspace, $document);

        return $this->jsonSuccess([
            'yjs_state' => $document->yjs_state,
            'content_html' => $document->content_html,
            'updated_by' => $document->updated_by,
            'updated_at' => $document->updated_at?->toISOString(),
        ]);
    }

    /**
     * Persist the debounced Y.js snapshot + rendered HTML.
     */
    public function updateState(Request $request, Workspace $workspace, WorkspaceDocument $document): JsonResponse
    {
        $this->authorizeApi('update', $workspace);
        $this->assertParentage($workspace, $document);

        $validated = $request->validate([
            'yjs_state' => ['required', 'string'],
            'content_html' => ['nullable', 'string'],
        ]);

        $document->update([
            'yjs_state' => $validated['yjs_state'],
            'content_html' => $validated['content_html'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        return $this->jsonSuccess([
            'updated_by' => $document->updated_by,
            'updated_at' => $document->updated_at?->toISOString(),
        ], __('workspaces.document_saved'));
    }

    /**
     * Route model binding resolves both models independently, so the document
     * must be asserted to belong to the workspace in the URL.
     */
    protected function assertParentage(Workspace $workspace, WorkspaceDocument $document): void
    {
        abort_if($document->workspace_id !== $workspace->id, 403);
    }
}
