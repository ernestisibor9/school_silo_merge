<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolSuspensionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

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


    /**
     * @OA\Get(
     *     path="/api/billing/school-status",
     *     summary="Get authenticated user's school suspension status",
     *     description="Returns the suspension status of the school associated with the authenticated user. Domain Admins and Support Admins do not belong to a single school.",
     *     tags={"School Suspension"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="School status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="role", type="string", example="school_user"),
     *             @OA\Property(property="school_id", type="string", nullable=true, example="12"),
     *             @OA\Property(property="suspended", type="boolean", example=false),
     *             @OA\Property(property="school_status", type="integer", nullable=true, example=1),
     *             @OA\Property(property="message", type="string", nullable=true, example="THIS SCHOOL IS SUSPENDED, KINDLY CONTACT THE ADMINISTRATOR.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unsupported account type"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="School association or school not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unable to retrieve school status"
     *     )
     * )
     */
    public function schoolStatus(Request $request)
    {
        try {
            // The route is protected by auth:api.
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            // Domain Admin
            if ($user->typ === 'a') {
                return response()->json([
                    'status' => true,
                    'role' => 'domain_admin',
                    'school_id' => null,
                    'suspended' => false,
                    'school_status' => null,
                    'message' => null,
                ]);
            }

            // Support Admin
            if ($user->typ === 'sa') {
                return response()->json([
                    'status' => true,
                    'role' => 'support_admin',
                    'school_id' => null,
                    'suspended' => false,
                    'school_status' => null,
                    'message' => null,
                ]);
            }

            $schoolId = null;

            // School Admin: users.id maps directly to school.sid.
            if ($user->typ === 's') {

                $schoolId = (string) $user->id;
            } elseif ($user->typ === 'z') {

                // Student: users.id maps to old_student.sid.
                $schoolId = DB::table('old_student')
                    ->where('sid', (string) $user->id)
                    ->orderByDesc('ssn')
                    ->orderByDesc('trm')
                    ->value('schid');
            } elseif ($user->typ === 'w') {

                // Staff: users.id maps to old_staff.sid.
                $schoolId = DB::table('old_staff')
                    ->where('sid', (string) $user->id)
                    ->orderByDesc('ssn')
                    ->orderByDesc('trm')
                    ->value('schid');
            } else {

                return response()->json([
                    'status' => false,
                    'message' => 'Unsupported account type.',
                ], 403);
            }

            if (!$schoolId) {
                return response()->json([
                    'status' => false,
                    'message' => 'School association not found.',
                ], 404);
            }

            // Retrieve the school's suspension status.
            $school = DB::table('school')
                ->where('sid', $schoolId)
                ->first(['sid', 'school_status']);

            if (!$school) {
                return response()->json([
                    'status' => false,
                    'message' => 'School not found.',
                ], 404);
            }

            $suspended = (int) $school->school_status === 0;

            return response()->json([
                'status' => true,
                'role' => 'school_user',
                'school_id' => $school->sid,
                'suspended' => $suspended,
                'school_status' => (int) $school->school_status,
                'message' => $suspended
                    ? 'THIS SCHOOL IS SUSPENDED, KINDLY CONTACT THE ADMINISTRATOR.'
                    : null,
            ]);
        } catch (\Throwable $e) {

            Log::error('Unable to retrieve school suspension status.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve school status.',
            ], 500);
        }
    }
}
