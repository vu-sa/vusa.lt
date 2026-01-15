<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
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
