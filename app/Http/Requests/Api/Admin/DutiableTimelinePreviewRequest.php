<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Dutiables\DutiableTimelineOperationsRequest;

/**
 * The dry-run half. Read-only, so it stays on the JSON API — only the commit needs the
 * Inertia flash contract.
 */
class DutiableTimelinePreviewRequest extends DutiableTimelineOperationsRequest {}
