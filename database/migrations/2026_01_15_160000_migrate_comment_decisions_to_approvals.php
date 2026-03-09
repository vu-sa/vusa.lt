<?php

use App\Enums\ApprovalDecision;
use App\Models\Comment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate existing comment decisions to the new approvals table.
     */
    public function up(): void
    {
        // Get all comments with decisions on reservation_resources
        $commentsWithDecisions = Comment::query()
            ->whereNotNull('decision')
            ->where('commentable_type', 'reservation_resource')
            ->get();

        foreach ($commentsWithDecisions as $comment) {
            // Map old decision values to new ApprovalDecision enum
            $decision = match ($comment->decision) {
                'approve', 'approved', 'progress' => ApprovalDecision::Approved->value,
                'reject', 'rejected' => ApprovalDecision::Rejected->value,
                'cancel', 'cancelled' => ApprovalDecision::Cancelled->value,
                default => null,
            };

            if ($decision === null) {
                continue;
            }

            // Create approval record
            DB::table('approvals')->insert([
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'approvable_type' => $comment->commentable_type,
                'approvable_id' => $comment->commentable_id,
                'user_id' => $comment->user_id,
                'decision' => $decision,
                'step' => 1,
                'notes' => strip_tags($comment->comment) ?: null,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ]);
        }

        // Remove the decision column from comments table
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the decision column
        Schema::table('comments', function (Blueprint $table) {
            $table->string('decision', 125)
                ->nullable()
                ->after('comment')
                ->comment('The decision made alongside the comment.');
        });

        // Note: We cannot restore the original decision values from approvals
        // because the comment text may have been modified or the relationship
        // between approvals and comments is not preserved.
        // This is a one-way migration.
    }
};
