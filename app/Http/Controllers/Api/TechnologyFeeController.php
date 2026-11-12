<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnologyFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TechnologyFeeController extends Controller
{



    /**
     * @OA\Get(
     *     path="/api/billing/technology-fees",
     *     tags={"Billing"},
     *     summary="Retrieve all technology fees",
     *     description="Returns technology fees, including draft and published records. Supports filtering by academic session, term, and offset pagination.",
     *     operationId="getAllTechnologyFees",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="session",
     *         in="query",
     *         required=false,
     *         description="Academic session starting year. For example, 2026 represents 2026/2027.",
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *     @OA\Parameter(
     *         name="term",
     *         in="query",
     *         required=false,
     *         description="Academic term: 1 = First Term, 2 = Second Term, 3 = Third Term.",
     *         @OA\Schema(type="integer", enum={1, 2, 3}, example=1)
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
     *     @OA\Response(response=200, description="Technology fees retrieved successfully."),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only the Domain Admin can access this endpoint"),
     *     @OA\Response(response=422, description="Invalid query parameters")
     * )
     */
    public function index(Request $request)
    {
        // Only the Domain Admin can access this endpoint.
        $admin = $request->user();

        if (!$admin || $admin->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only the Domain Admin can access this endpoint.',
            ], 403);
        }

        // Pagination defaults
        $start = request()->query('start', 0);
        $count = request()->query('count', 20);

        // Validate query parameters.
        $validator = Validator::make($request->query(), [
            'session' => 'sometimes|required|digits:4|exists:sesn,year',
            'term'    => 'sometimes|required|integer|in:1,2,3',
            'start'   => 'sometimes|required|integer|min:0',
            'count'   => 'sometimes|required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid query parameters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Retrieve technology fees.
        $query = TechnologyFee::query();

        // Filter by academic session.
        if ($request->filled('session')) {
            $sessionName = DB::table('sesn')
                ->where('year', $request->query('session'))
                ->value('name');

            $query->where('academic_session', $sessionName);
        }

        // Filter by term.
        if ($request->filled('term')) {
            $terms = [
                1 => 'First Term',
                2 => 'Second Term',
                3 => 'Third Term',
            ];

            $termName = $terms[(int) $request->query('term')];

            $query->where('term', $termName);
        }

        // Retrieve records using offset pagination.
        $technologyFees = $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->skip((int) $start)
            ->take((int) $count)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Technology fees retrieved successfully.',
            'pld' => [
                'start' => (int) $start,
                'count' => $technologyFees->count(),
                'technology_fees' => $technologyFees,
            ],
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/billing/technology-fees",
     *     tags={"Billing"},
     *     summary="Create a technology fee",
     *     description="Creates a technology fee in draft status. Only the Domain Admin can create technology fees.",
     *     operationId="createTechnologyFee",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"academic_session","term","fee_per_student"},
     *             @OA\Property(
     *                 property="academic_session",
     *                 type="string",
     *                 example="2026/2027"
     *             ),
     *             @OA\Property(
     *                 property="term",
     *                 type="string",
     *                 example="First Term"
     *             ),
     *             @OA\Property(
     *                 property="fee_per_student",
     *                 type="number",
     *                 format="float",
     *                 example=1500.00
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Technology fee created successfully as a draft"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized. Only the Domain Admin can manage technology fees."
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="A technology fee already exists for this academic session and term."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Only the Domain Admin can create technology fees..
        $admin = auth()->user();

        if (!$admin || $admin->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only the Domain Admin can manage technology fees.',
            ], 403);
        }

        // Validate the submitted data.
        $validator = Validator::make($request->all(), [
            'academic_session' => ['required', 'string', 'max:50'],
            'term' => ['required', 'string', 'max:50'],
            'fee_per_student' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $technologyFee = TechnologyFee::create([
                'academic_session' => $request->academic_session,
                'term' => $request->term,
                'fee_per_student' => $request->fee_per_student,
                'status' => 'draft',
                'created_by' => $admin->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Technology fee created successfully as a draft.',
                'pld' => $technologyFee,
            ], 201);
        } catch (QueryException $e) {

            // The database unique constraint prevents duplicate session/term fees..
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status' => false,
                    'message' => 'A technology fee already exists for this academic session and term.',
                ], 409);
            }

            throw $e;
        }
    }



    /**
     * @OA\Put(
     *     path="/api/billing/technology-fees/{id}",
     *     tags={"Billing"},
     *     summary="Update a technology fee",
     *     description="Updates the per-student technology fee. If the fee has already been published, existing unpaid school bills are automatically recalculated. Paid bills remain unchanged.",
     *     operationId="updateTechnologyFee",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Technology fee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"fee_per_student"},
     *             @OA\Property(
     *                 property="fee_per_student",
     *                 type="number",
     *                 format="float",
     *                 example=2000.00
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Technology fee updated successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized. Only the Domain Admin can manage technology fees."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Technology fee not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        // Only the Domain Admin can update technology fees.
        $admin = auth()->user();

        if (!$admin || $admin->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only the Domain Admin can manage technology fees.',
            ], 403);
        }

        // Validate the submitted data..
        $validator = Validator::make($request->all(), [
            'fee_per_student' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999999.99',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return DB::transaction(function () use (
            $id,
            $request,
            $admin
        ) {

            // Lock the fee before updating it.
            $technologyFee = TechnologyFee::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$technologyFee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Technology fee not found.',
                ], 404);
            }

            // Update the fee.
            $technologyFee->update([
                'fee_per_student' => $request->fee_per_student,
                'updated_by' => $admin->id,
            ]);

            // Recalculate existing unpaid bills if the fee is published.
            $recalculatedBills = 0;

            if ($technologyFee->status === 'published') {
                $billingService = app(\App\Services\BillingService::class);

                $recalculatedBills = $billingService
                    ->recalculateUnpaidBillsForFee(
                        $technologyFee->fresh(),
                        $admin->id
                    );
            }

            return response()->json([
                'status' => true,
                'message' => 'Technology fee updated successfully.',
                'pld' => [
                    'technology_fee' => $technologyFee->fresh(),
                    'unpaid_bills_processed' => $recalculatedBills,
                ],
            ], 200);
        });
    }

    /**
     * @OA\Post(
     *     path="/api/billing/technology-fees/{id}/publish",
     *     tags={"Billing"},
     *     summary="Publish a technology fee",
     *     description="Publishes a draft technology fee and generates individual bills for all schools with billable learners for the selected academic session and term. Schools without billable learners are skipped.",
     *     operationId="publishTechnologyFee",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Technology fee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Technology fee published successfully and school bills generated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized. Only the Domain Admin can manage technology fees."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Technology fee not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="This technology fee has already been published."
     *     )
     * )
     */

    public function publish($id)
    {
        // Only the Domain Admin can publish technology fees.
        $admin = auth()->user();

        if (!$admin || $admin->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only the Domain Admin can manage technology fees.',
            ], 403);
        }

        // Find the technology fee.
        $technologyFee = TechnologyFee::find($id);

        if (!$technologyFee) {
            return response()->json([
                'status' => false,
                'message' => 'Technology fee not found.',
            ], 404);
        }

        return DB::transaction(function () use ($id, $admin) {

            // Lock the fee to prevent simultaneous publishing.
            $technologyFee = TechnologyFee::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            // A published fee cannot be published again.
            if ($technologyFee->status === 'published') {
                return response()->json([
                    'status' => false,
                    'message' => 'This technology fee has already been published.',
                ], 409);
            }

            // Publish the fee.
            $technologyFee->update([
                'status' => 'published',
                'published_at' => now(),
                'updated_by' => $admin->id,
            ]);

            // Generate bills for all schools with billable learners.
            $billingService = app(\App\Services\BillingService::class);

            $bills = $billingService->generateBillsForAllSchools(
                $technologyFee->fresh()
            );

            return response()->json([
                'status' => true,
                'message' => 'Technology fee published successfully.',
                'pld' => [
                    'technology_fee' => $technologyFee->fresh(),
                    'bills_generated' => count($bills),
                    'bills' => $bills,
                ],
            ], 200);
        });
    }




    /**
     * @OA\Get(
     *     path="/api/billing/technology-fees/{id}/schools",
     *     tags={"Billing"},
     *     summary="Get schools under a technology fee",
     *     description="Returns schools with generated bills under a selected technology fee. Supports payment status filtering and offset pagination.",
     *     operationId="getSchoolsForTechnologyFee",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Technology fee ID",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=true,
     *         description="Filter school bills by payment status.",
     *         @OA\Schema(type="string", enum={"paid", "unpaid"}, example="paid")
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
     *     @OA\Response(response=200, description="Schools retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only the Domain Admin can access this endpoint"),
     *     @OA\Response(response=404, description="Technology fee not found"),
     *     @OA\Response(response=422, description="Invalid query parameters")
     * )
     */
    public function schools(Request $request, $id)
    {
        // Only the Domain Admin can access this endpoint.
        $admin = $request->user();

        if (!$admin || $admin->typ !== 'a') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only the Domain Admin can access this endpoint.',
            ], 403);
        }

        // Pagination defaults
        $start = request()->query('start', 0);
        $count = request()->query('count', 20);

        // Validate query parameters.
        $validator = Validator::make($request->query(), [
            'status' => ['required', 'in:paid,unpaid'],
            'start'  => ['sometimes', 'required', 'integer', 'min:0'],
            'count'  => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the selected technology fee.
        $technologyFee = TechnologyFee::find($id);

        if (!$technologyFee) {
            return response()->json([
                'status' => false,
                'message' => 'Technology fee not found.',
            ], 404);
        }

        // Retrieve schools using offset pagination.
        $schools = DB::table('school_bills')
            ->join('school', 'school.sid', '=', 'school_bills.school_id')
            ->where('school_bills.technology_fee_id', $technologyFee->id)
            ->where('school_bills.status', $request->query('status'))
            ->select(
                'school.sid as school_id',
                'school.name as school_name',
                'school_bills.id as bill_id',
                'school_bills.academic_session',
                'school_bills.term',
                'school_bills.student_count',
                'school_bills.fee_per_student',
                'school_bills.total_amount',
                'school_bills.status as payment_status',
                'school_bills.paid_at',
                'school_bills.created_at as bill_created_at'
            )
            ->orderBy('school.name', 'asc')
            ->skip((int) $start)
            ->take((int) $count)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Schools retrieved successfully.',
            'pld' => [
                'technology_fee' => [
                    'id' => $technologyFee->id,
                    'academic_session' => $technologyFee->academic_session,
                    'term' => $technologyFee->term,
                    'fee_per_student' => $technologyFee->fee_per_student,
                    'status' => $technologyFee->status,
                    'published_at' => $technologyFee->published_at,
                ],
                'start' => (int) $start,
                'count' => $schools->count(),
                'schools' => $schools,
            ],
        ], 200);
    }
}
