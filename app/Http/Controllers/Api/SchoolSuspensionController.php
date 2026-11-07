<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolSuspensionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolSuspensionController extends Controller
{
    /**
     * Suspend a school.
     *
     * Only Domain Admins can suspend schools.
     *
     * @OA\Post(
     *     path="/api/billing/schools/{schoolId}/suspend",
     *     tags={"Billing"},
     *     summary="Suspend a school",
     *     description="Allows only a Domain Admin to suspend a school.",
     *     operationId="suspendSchool",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schoolId",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="12")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reason",
     *                 type="string",
     *                 example="Outstanding technology billing payment."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="School suspended successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only Domain Admins can suspend schools"),
     *     @OA\Response(response=404, description="School not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function suspend(Request $request, string $schoolId)
    {
        return $this->updateSchoolStatus(
            $request,
            $schoolId,
            0,
            'suspended',
            'School suspended successfully.'
        );
    }

    /**
     * Reactivate a school.
     *
     * Only Domain Admins can reactivate schools.
     *
     * @OA\Post(
     *     path="/api/billing/schools/{schoolId}/reactivate",
     *     tags={"Billing"},
     *     summary="Reactivate a school",
     *     description="Allows only a Domain Admin to reactivate a suspended school.",
     *     operationId="reactivateSchool",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schoolId",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="12")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reason",
     *                 type="string",
     *                 example="Manual reactivation approved by Domain Admin."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="School reactivated successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only Domain Admins can reactivate schools"),
     *     @OA\Response(response=404, description="School not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function reactivate(Request $request, string $schoolId)
    {
        return $this->updateSchoolStatus(
            $request,
            $schoolId,
            1,
            'reactivated',
            'School reactivated successfully.'
        );
    }

    /**
     * Update school status and record the action.
     */
    private function updateSchoolStatus(
        Request $request,
        string $schoolId,
        int $newStatus,
        string $action,
        string $successMessage
    ) {
        $user = $request->user();

        // Only Domain Admins can manage school suspension.
        if (!$user || $user->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Domain Admins can manage school suspension.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:5000',
        ]);

        $result = DB::transaction(function () use (
            $schoolId,
            $newStatus,
            $action,
            $successMessage,
            $user,
            $validated
        ) {
            // Lock the school record to prevent concurrent status changes.
            $school = DB::table('school')
                ->where('sid', $schoolId)
                ->lockForUpdate()
                ->first();

            if (!$school) {
                return [
                    'http_code' => 404,
                    'response' => [
                        'status' => false,
                        'message' => 'School not found.',
                    ],
                ];
            }

            // Avoid unnecessary updates and duplicate audit logs.
            if ((int) $school->school_status === $newStatus) {
                return [
                    'http_code' => 200,
                    'response' => [
                        'status' => true,
                        'message' => $newStatus === 0
                            ? 'This school is already suspended.'
                            : 'This school is already active.',
                        'pld' => [
                            'school_id' => (string) $school->sid,
                            'school_status' => (int) $school->school_status,
                        ],
                    ],
                ];
            }

            // Update the school's status.
            DB::table('school')
                ->where('sid', $schoolId)
                ->update([
                    'school_status' => $newStatus,
                    'updated_at' => now(),
                ]);

            // Record the suspension/reactivation action.
            SchoolSuspensionLog::create([
                'school_id' => (string) $schoolId,
                'action' => $action,
                'performed_by' => $user->id,
                'reason' => $validated['reason'] ?? null,
            ]);

            return [
                'http_code' => 200,
                'response' => [
                    'status' => true,
                    'message' => $successMessage,
                    'pld' => [
                        'school_id' => (string) $schoolId,
                        'school_status' => $newStatus,
                        'action' => $action,
                    ],
                ],
            ];
        });

        return response()->json(
            $result['response'],
            $result['http_code']
        );
    }
}
