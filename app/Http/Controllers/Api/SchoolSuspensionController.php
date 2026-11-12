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




/**
 * Get all suspended schools.
 *
 * Only Domain Admins can access this endpoint.
 *
 * @OA\Get(
 *     path="/api/billing/suspended-schools",
 *     tags={"School Suspension"},
 *     summary="Get all suspended schools",
 *     description="Returns suspended schools. Supports optional filtering by school ID, academic session and term using school billing records, with offset pagination.",
 *     operationId="getSuspendedSchools",
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\Parameter(
 *         name="school_id",
 *         in="query",
 *         required=false,
 *         description="Filter by school ID.",
 *         @OA\Schema(type="string", example="13")
 *     ),
 *     @OA\Parameter(
 *         name="session",
 *         in="query",
 *         required=false,
 *         description="Filter by academic session year.",
 *         @OA\Schema(type="integer", example=2024)
 *     ),
 *     @OA\Parameter(
 *         name="term",
 *         in="query",
 *         required=false,
 *         description="Filter by academic term: 1 = First Term, 2 = Second Term, 3 = Third Term.",
 *         @OA\Schema(type="integer", enum={1, 2, 3}, example=2)
 *     ),
 *     @OA\Parameter(
 *         name="start",
 *         in="query",
 *         required=false,
 *         description="Number of records to skip.",
 *         @OA\Schema(type="integer", minimum=0, example=0)
 *     ),
 *     @OA\Parameter(
 *         name="count",
 *         in="query",
 *         required=false,
 *         description="Maximum number of records to return.",
 *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Suspended schools retrieved successfully"
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only Domain Admins can access this endpoint"),
 *     @OA\Response(response=422, description="Validation failed")
 * )
 */
public function suspendedSchools(Request $request)
{
    // Only Domain Admins can access this endpoint.
    $admin = $request->user();

    if (!$admin || $admin->typ !== 'a') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized. Only Domain Admins can access this endpoint.',
        ], 403);
    }

    // Pagination defaults.
    $start = $request->query('start', 0);
    $count = $request->query('count', 20);

    // Validate query parameters.
    $validator = \Illuminate\Support\Facades\Validator::make(
        $request->query(),
        [
            'school_id' => ['sometimes', 'required', 'string', 'exists:school,sid'],
            'session'   => ['sometimes', 'required', 'integer', 'digits:4', 'exists:sesn,year'],
            'term'      => ['sometimes', 'required', 'integer', 'in:1,2,3'],
            'start'     => ['sometimes', 'required', 'integer', 'min:0'],
            'count'     => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ]
    );

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Get the academic session name from the sesn table.
    $sessionName = null;

    if ($request->filled('session')) {
        $sessionName = DB::table('sesn')
            ->where('year', $request->query('session'))
            ->value('name');
    }

    // Map the supplied term number to its billing term name.
    $termName = null;

    if ($request->filled('term')) {
        $terms = [
            1 => 'First Term',
            2 => 'Second Term',
            3 => 'Third Term',
        ];

        $termName = $terms[(int) $request->query('term')];
    }

    // Start with suspended schools only.
    // school_status is retrieved exclusively from the school table.
    $query = DB::table('school')
        ->where('school.school_status', 0);

    // Optional school ID filter.
    if ($request->filled('school_id')) {
        $query->where(
            'school.sid',
            $request->query('school_id')
        );
    }

    // Filter using actual school billing records.
    // A school must have a matching billing record when
    // a session or term filter is supplied.
    if ($sessionName !== null || $termName !== null) {

        $query->whereExists(function ($subQuery) use (
            $sessionName,
            $termName
        ) {
            $subQuery->select(DB::raw(1))
                ->from('school_bills')
                ->whereColumn(
                    'school_bills.school_id',
                    'school.sid'
                );

            if ($sessionName !== null) {
                $subQuery->where(
                    'school_bills.academic_session',
                    $sessionName
                );
            }

            if ($termName !== null) {
                $subQuery->where(
                    'school_bills.term',
                    $termName
                );
            }
        });
    }

    // Retrieve suspended schools with offset pagination.
    $schools = $query
        ->select(
            'school.sid as school_id',
            'school.name as school_name',
            'school.school_status',
            'school.created_at',
            'school.updated_at'
        )
        ->orderBy('school.name', 'asc')
        ->skip((int) $start)
        ->take((int) $count)
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Suspended schools retrieved successfully.',
        'pld' => [
            'school_id' => $request->query('school_id'),
            'session' => $request->query('session'),
            'term' => $request->query('term'),
            'start' => (int) $start,
            'count' => $schools->count(),
            'schools' => $schools,
        ],
    ], 200);
}


}
