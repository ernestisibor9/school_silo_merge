<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolBill;
use App\Models\SchoolSuspension;
use Illuminate\Http\Request;

class SchoolBillController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/billing/school-bills/{schid}",
     *     tags={"Billing"},
     *     summary="Get school technology bills",
     *     description="Retrieves technology bills for the specified school with optional academic session, term and offset-based pagination. Only the authenticated School Admin belonging to that school can access its bills. Schools with an active suspension for the requested academic period cannot access their bills.",
     *     operationId="getSchoolTechnologyBills",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="12")
     *     ),
     *     @OA\Parameter(
     *         name="session",
     *         in="query",
     *         required=false,
     *         description="Filter by academic session year.",
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *     @OA\Parameter(
     *         name="term",
     *         in="query",
     *         required=false,
     *         description="Filter by academic term: 1 = First Term, 2 = Second Term, 3 = Third Term.",
     *         @OA\Schema(type="integer", enum={1, 2, 3}, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Start index for pagination (default 0).",
     *         @OA\Schema(type="integer", default=0, minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of school bills to fetch (default 20).",
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="School bills retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="School bills retrieved successfully."),
     *             @OA\Property(
     *                 property="pld",
     *                 type="object",
     *                 @OA\Property(property="schid", type="string", example="12"),
     *                 @OA\Property(property="start", type="integer", example=0),
     *                 @OA\Property(property="count", type="integer", example=2),
     *                 @OA\Property(
     *                     property="school_bills",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="academic_session", type="string", example="2026/2027"),
     *                         @OA\Property(property="term", type="string", example="First Term"),
     *                         @OA\Property(property="student_count", type="integer", example=471),
     *                         @OA\Property(property="fee_per_student", type="number", format="float", example=20.00),
     *                         @OA\Property(property="total_amount", type="number", format="float", example=9420.00),
     *                         @OA\Property(
     *                             property="status",
     *                             type="string",
     *                             enum={"unpaid", "paid"},
     *                             example="paid"
     *                         ),
     *                         @OA\Property(property="paid_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Unauthorized, school suspended, or school ID does not belong to authenticated School Admin"),
     *     @OA\Response(response=422, description="Invalid query parameters")
     * )
     */
    public function index(Request $request, $schid)
    {
        $user = $request->user();

        // Only authenticated School Admins can access school bills.
        if (!$user || $user->typ !== 's') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only School Admins can access school bills.',
            ], 403);
        }

        // Ensure the requested school belongs to the authenticated School Admin.
        if ((string) $user->id !== (string) $schid) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. You cannot access another school\'s bills.',
            ], 403);
        }

        // Validate query parameters.
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
                'start' => 'sometimes|required|integer|min:0',
                'count' => 'sometimes|required|integer|min:1|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid pagination parameters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Pagination defaults.
        $start = (int) $request->query('start', 0);
        $count = (int) $request->query('count', 20);

        // Resolve the requested academic session.
        $sessionName = null;

        if ($request->filled('session')) {
            $sessionName = \Illuminate\Support\Facades\DB::table('sesn')
                ->where('year', $request->query('session'))
                ->value('name');
        }

        // Resolve the requested academic term.
        $termName = null;

        if ($request->filled('term')) {
            $terms = [
                1 => 'First Term',
                2 => 'Second Term',
                3 => 'Third Term',
            ];

            $termName = $terms[(int) $request->query('term')];
        }

        // Check active suspension records.
        // school_suspensions is the sole source of suspension status.
        $suspensionQuery = SchoolSuspension::where(
            'school_id',
            (string) $schid
        )->where('status', 'suspended');

        // Apply academic session filter when supplied.
        if ($sessionName !== null) {
            $suspensionQuery->where(
                'academic_session',
                $sessionName
            );
        }

        // Apply academic term filter when supplied.
        if ($termName !== null) {
            $suspensionQuery->where(
                'term',
                $termName
            );
        }

        // Prevent suspended schools from accessing their bills.
        if ($suspensionQuery->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Your school is currently suspended and cannot access technology bills.',
            ], 403);
        }

        // Retrieve bills belonging to the requested school.
        // No comparison with school.school_status is required.
        $query = SchoolBill::where(
            'school_bills.school_id',
            (string) $schid
        );

        // Optional academic session filter.
        if ($sessionName !== null) {
            $query->where(
                'school_bills.academic_session',
                $sessionName
            );
        }

        // Optional academic term filter.
        if ($termName !== null) {
            $query->where(
                'school_bills.term',
                $termName
            );
        }

        // Retrieve bills with offset pagination.
        $bills = $query
            ->orderByDesc('school_bills.id')
            ->skip($start)
            ->take($count)
            ->get([
                'school_bills.id',
                'school_bills.academic_session',
                'school_bills.term',
                'school_bills.student_count',
                'school_bills.fee_per_student',
                'school_bills.total_amount',
                'school_bills.status',
                'school_bills.paid_at',
                'school_bills.created_at',
            ]);

        return response()->json([
            'status' => true,
            'message' => 'School bills retrieved successfully.',
            'pld' => [
                'schid' => $schid,
                'start' => $start,
                'count' => $bills->count(),
                'school_bills' => $bills,
            ],
        ], 200);
    }
}
