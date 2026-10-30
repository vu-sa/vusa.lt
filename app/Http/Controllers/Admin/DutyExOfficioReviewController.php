<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Dutiables\ApplyExOfficioReview;
use App\Actions\Dutiables\PlanExOfficioReview;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Dutiables\ApplyExOfficioReviewRequest;
use App\Models\Duty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DutyExOfficioReviewController extends AdminController
{
    public function preview(Duty $duty): JsonResponse
    {
        $this->authorize('managePeople', $duty);

        return response()->json(['success' => true, 'data' => PlanExOfficioReview::execute(PlanExOfficioReview::targetIds($duty), request()->user())]);
    }

    public function apply(ApplyExOfficioReviewRequest $request, Duty $duty): RedirectResponse
    {
        $data = $request->validated();
        $plan = PlanExOfficioReview::execute(PlanExOfficioReview::targetIds($duty), $request->user());
        $couldAffectSelf = ! $request->user()->isSuperAdmin() && collect($plan['items'])->contains(fn (array $item) => in_array($item['id'], $data['item_ids'], true) && $item['holder_id'] === $request->user()->id);
        $mutation = fn () => ApplyExOfficioReview::execute($duty, $request->user(), $data);
        if ($warning = $this->guardSelfLockout($request->user(), $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return back()->with('success', __('dutiables.ex_officio_review.saved'));
    }
}
