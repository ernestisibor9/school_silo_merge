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

            // The database unique constraint prevents duplicate session/term fees.
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

    // Validate the submitted data.
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


}
