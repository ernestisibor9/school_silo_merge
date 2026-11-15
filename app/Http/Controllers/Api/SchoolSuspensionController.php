<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolSuspensionLog;
use App\Models\SchoolSuspension;
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
        return $this->updateSchoolSuspension(
            $request,
            $schoolId,
            'suspend'
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
        return $this->updateSchoolSuspension(
            $request,
            $schoolId,
            'reactivate'
        );
    }

    /**
     * Update school status and record the action.
     */


    private function updateSchoolSuspension(
        Request $request,
        string $schoolId,
        string $action
    ) {
        $user = $request->user();

        // Only Domain Admins can manage school suspensions.
        if (!$user || $user->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Domain Admins can manage school suspension.',
            ], 403);
        }

        // Validate the academic period.
        $validator = Validator::make($request->all(), [
            'session' => [
                'required',
                'integer',
                'digits:4',
                'exists:sesn,year',
            ],
            'term' => [
                'required',
                'integer',
                'in:1,2,3',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = (int) $request->input('session');
        $term = (int) $request->input('term');

        $terms = [
            1 => 'First Term',
            2 => 'Second Term',
            3 => 'Third Term',
        ];

        $termName = $terms[$term];

        // Use the academic session name from the sesn table.
        $academicSession = DB::table('sesn')
            ->where('year', $session)
            ->value('name');

        if (!$academicSession) {
            return response()->json([
                'status' => false,
                'message' => 'Academic session not found.',
            ], 404);
        }

        try {
            $result = DB::transaction(function () use (
                $schoolId,
                $academicSession,
                $termName,
                $action,
                $user,
                $request
            ) {
                // Lock the school record to prevent concurrent changes.
                $school = DB::table('school')
                    ->where('sid', $schoolId)
                    ->lockForUpdate()
                    ->first(['sid', 'name']);

                if (!$school) {
                    return [
                        'http_code' => 404,
                        'response' => [
                            'status' => false,
                            'message' => 'School not found.',
                        ],
                    ];
                }

                // Find the active suspension for this exact academic period.
                $suspension = SchoolSuspension::where('school_id', (string) $schoolId)
                    ->where('academic_session', $academicSession)
                    ->where('term', $termName)
                    ->where('status', 'suspended')
                    ->lockForUpdate()
                    ->first();

                if ($action === 'suspend') {

                    // Do not create duplicate active suspensions.
                    if ($suspension) {
                        return [
                            'http_code' => 200,
                            'response' => [
                                'status' => true,
                                'message' => 'This school is already suspended for the selected academic session and term.',
                                'pld' => [
                                    'school_id' => (string) $schoolId,
                                    'school_name' => $school->name,
                                    'academic_session' => $academicSession,
                                    'term' => $termName,
                                    'school_status' => 0,
                                    'suspension_id' => $suspension->id,
                                    'action' => 'suspended',
                                ],
                            ],
                        ];
                    }

                    // Create a new suspension record.
                    $suspension = SchoolSuspension::create([
                        'school_id' => (string) $schoolId,
                        'academic_session' => $academicSession,
                        'term' => $termName,
                        'reason' => $request->input('reason'),
                        'status' => 'suspended',
                        'suspended_by' => $user->id,
                        'suspended_at' => now(),
                    ]);

                    return [
                        'http_code' => 200,
                        'response' => [
                            'status' => true,
                            'message' => 'School suspended successfully.',
                            'pld' => [
                                'school_id' => (string) $schoolId,
                                'school_name' => $school->name,
                                'academic_session' => $academicSession,
                                'term' => $termName,
                                'school_status' => 0,
                                'suspension_id' => $suspension->id,
                                'action' => 'suspended',
                            ],
                        ],
                    ];
                }

                // Reactivation must target an existing active suspension.
                if (!$suspension) {
                    return [
                        'http_code' => 404,
                        'response' => [
                            'status' => false,
                            'message' => 'No active suspension found for this school, academic session and term.',
                        ],
                    ];
                }

                // Reactivate the existing record without deleting its history.
                $suspension->update([
                    'status' => 'reactivated',
                    'reactivated_by' => $user->id,
                    'reactivated_at' => now(),
                    'reactivation_reason' => $request->input('reason'),
                ]);

                return [
                    'http_code' => 200,
                    'response' => [
                        'status' => true,
                        'message' => 'School reactivated successfully.',
                        'pld' => [
                            'school_id' => (string) $schoolId,
                            'school_name' => $school->name,
                            'academic_session' => $academicSession,
                            'term' => $termName,
                            'school_status' => 1,
                            'suspension_id' => $suspension->id,
                            'action' => 'reactivated',
                        ],
                    ],
                ];
            });

            return response()->json(
                $result['response'],
                $result['http_code']
            );
        } catch (\Throwable $e) {

            Log::error('Unable to update school suspension.', [
                'school_id' => $schoolId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to update school suspension.',
            ], 500);
        }
    }





    /**
     * @OA\Get(
     *     path="/api/billing/school-status",
     *     summary="Get authenticated user's school suspension status",
     *     description="Returns the suspension status of the school associated with the authenticated user. Student and Staff accounts can optionally filter their school association by academic session and term. Session should be supplied as a year, e.g. 2026, not 2026/2027. Domain Admins and Support Admins do not belong to a single school.",
     *     tags={"School Suspension"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="session",
     *         in="query",
     *         required=false,
     *         description="Optional academic session year, e.g. 2026 (not 2026/2027).",
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *
     *     @OA\Parameter(
     *         name="term",
     *         in="query",
     *         required=false,
     *         description="Optional academic term: 1 = First Term, 2 = Second Term, 3 = Third Term.",
     *         @OA\Schema(type="integer", enum={1, 2, 3}, example=1)
     *     ),
     *
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
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Unsupported account type"),
     *     @OA\Response(response=404, description="School association or school not found"),
     *     @OA\Response(response=422, description="Invalid session or term"),
     *     @OA\Response(response=500, description="Unable to retrieve school status")
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
                    'pld' => [],
                ], 401);
            }

            // Validate optional academic session and term filters.
            $validator = \Illuminate\Support\Facades\Validator::make(
                $request->query(),
                [
                    'session' => [
                        'sometimes',
                        'required',
                        'integer',
                        'digits:4',
                        'exists:sesn,year',
                    ],
                    'term' => [
                        'sometimes',
                        'required',
                        'integer',
                        'in:1,2,3',
                    ],
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                    'pld' => [],
                ], 422);
            }

            $session = $request->query('session');
            $term = $request->query('term');

            // Domain Admin
            if ($user->typ === 'a') {
                return response()->json([
                    'status' => true,
                    'message' => 'School status retrieved successfully.',
                    'pld' => [
                        'role' => 'domain_admin',
                        'school_id' => null,
                        'session' => $session !== null ? (int) $session : null,
                        'term' => $term !== null ? (int) $term : null,
                        'suspended' => false,
                        'school_status' => null,
                    ],
                ], 200);
            }

            // Support Admin
            if ($user->typ === 'sa') {
                return response()->json([
                    'status' => true,
                    'message' => 'School status retrieved successfully.',
                    'pld' => [
                        'role' => 'support_admin',
                        'school_id' => null,
                        'session' => $session !== null ? (int) $session : null,
                        'term' => $term !== null ? (int) $term : null,
                        'suspended' => false,
                        'school_status' => null,
                    ],
                ], 200);
            }

            $schoolId = null;

            // School Admin: users.id maps directly to school.sid.
            if ($user->typ === 's') {

                $schoolId = (string) $user->id;
            } elseif ($user->typ === 'z') {

                // Student: users.id maps to old_student.sid.
                $studentQuery = DB::table('old_student')
                    ->where('sid', (string) $user->id);

                // Optional academic session filter.
                if ($session !== null) {
                    $studentQuery->where('ssn', (int) $session);
                }

                // Optional academic term filter.
                if ($term !== null) {
                    $studentQuery->where('trm', (int) $term);
                }

                $schoolId = $studentQuery
                    ->orderByDesc('ssn')
                    ->orderByDesc('trm')
                    ->value('schid');
            } elseif ($user->typ === 'w') {

                // Staff: users.id maps to old_staff.sid.
                $staffQuery = DB::table('old_staff')
                    ->where('sid', (string) $user->id);

                // Optional academic session filter.
                if ($session !== null) {
                    $staffQuery->where('ssn', (int) $session);
                }

                // Optional academic term filter.
                if ($term !== null) {
                    $staffQuery->where('trm', (int) $term);
                }

                $schoolId = $staffQuery
                    ->orderByDesc('ssn')
                    ->orderByDesc('trm')
                    ->value('schid');
            } else {

                return response()->json([
                    'status' => false,
                    'message' => 'Unsupported account type.',
                    'pld' => [],
                ], 403);
            }

            if (!$schoolId) {
                return response()->json([
                    'status' => false,
                    'message' => 'School association not found.',
                    'pld' => [],
                ], 404);
            }

            // If both session and term are supplied,
            // verify that the school has a billing record for that period.
            if ($session !== null && $term !== null) {

                // Convert session year to academic session format.
                // Example: 2026 becomes 2026/2027.
                $academicSession = (int) $session . '/' . ((int) $session + 1);

                // Convert term number to billing term name.
                $terms = [
                    1 => 'First Term',
                    2 => 'Second Term',
                    3 => 'Third Term',
                ];

                $termName = $terms[(int) $term];

                $billExists = DB::table('school_bills')
                    ->where('school_id', $schoolId)
                    ->where('academic_session', $academicSession)
                    ->where('term', $termName)
                    ->exists();

                if (!$billExists) {
                    return response()->json([
                        'status' => false,
                        'message' => 'No billing record found for the selected academic session and term.',
                        'pld' => [],
                    ], 404);
                }
            }

            // Retrieve the school's suspension status using school ID only.
            // Do not use school.cssn, school.ctrm, or school.ctrmn here.
            $school = DB::table('school')
                ->where('sid', $schoolId)
                ->first(['sid', 'school_status']);

            if (!$school) {
                return response()->json([
                    'status' => false,
                    'message' => 'School not found.',
                    'pld' => [],
                ], 404);
            }

            $suspended = (int) $school->school_status === 0;

            return response()->json([
                'status' => true,
                'message' => 'School status retrieved successfully.',
                'pld' => [
                    'role' => 'school_user',
                    'school_id' => (string) $school->sid,
                    'session' => $session !== null ? (int) $session : null,
                    'term' => $term !== null ? (int) $term : null,
                    'suspended' => $suspended,
                    'school_status' => (int) $school->school_status,
                ],
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Unable to retrieve school suspension status.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve school status.',
                'pld' => [],
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
