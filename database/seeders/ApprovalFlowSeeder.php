<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\Survey;
use Illuminate\Database\Seeder;

class ApprovalFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates default approval flows for approvable models.
     */
    public function run(): void
    {
        // Default single-step approval flow for ReservationResource
        ApprovalFlow::updateOrCreate(
            [
                'name' => 'reservation_resource_default',
                'flowable_type' => 'reservation_resource',
                'flowable_id' => null, // Global default for all reservation resources
            ],
            [
                'steps' => [
                    [
                        'order' => 1,
                        'name' => 'Resource Manager Approval',
                        'required_count' => 1,
                        'permission' => 'resources.update.padalinys',
                    ],
                ],
                'is_sequential' => true,
                'escalation_days' => 3,
            ]
        );

        /*
         * Surveys: one central sign-off before anything reaches LimeSurvey.
         *
         * The step list is data, so making this two-step (padalinys review, then central)
         * later is a seeder change, not a code change. Note that flowable_type stores the
         * class name — Survey::getApprovalFlow() looks it up by Survey::class, and no morph
         * map is registered in this application.
         */
        ApprovalFlow::updateOrCreate(
            [
                'name' => 'survey_default',
                'flowable_type' => Survey::class,
                'flowable_id' => null,
            ],
            [
                'steps' => [
                    [
                        'order' => 1,
                        'name' => 'Survey Approval',
                        'required_count' => 1,
                        'permission' => 'surveys.update.*',
                    ],
                ],
                'is_sequential' => true,
                'escalation_days' => 5,
            ]
        );

        // Example of a multi-step approval flow (commented out, can be enabled if needed)
        // ApprovalFlow::updateOrCreate(
        //     [
        //         'name' => 'reservation_resource_high_value',
        //         'flowable_type' => 'reservation_resource',
        //         'flowable_id' => null,
        //     ],
        //     [
        //         'steps' => [
        //             [
        //                 'order' => 1,
        //                 'name' => 'Resource Manager Approval',
        //                 'required_count' => 1,
        //                 'permission' => 'resources.update.padalinys',
        //             ],
        //             [
        //                 'order' => 2,
        //                 'name' => 'Admin Approval',
        //                 'required_count' => 1,
        //                 'permission' => 'resources.update.*',
        //             ],
        //         ],
        //         'is_sequential' => true,
        //         'escalation_days' => 5,
        //     ]
        // );
    }
}
