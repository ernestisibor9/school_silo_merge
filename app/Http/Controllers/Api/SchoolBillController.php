<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolBill;
use Illuminate\Http\Request;

class SchoolBillController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/billing/school-bills/{schid}",
     *     tags={"Billing"},
     *     summary="Get school technology bills",
     *     description="Retrieves technology bills for the specified school. Only the authenticated School Admin belonging to that school can access its bills.",
     *     operationId="getSchoolTechnologyBills",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(
     *             type="string",
     *             example="12"
     *         )
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
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="academic_session", type="string", example="2026/2027"),
     *                     @OA\Property(property="term", type="string", example="First Term"),
     *                     @OA\Property(property="student_count", type="integer", example=471),
     *                     @OA\Property(property="fee_per_student", type="number", format="float", example=20.00),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=9420.00),
     *                     @OA\Property(
     *                         property="status",
     *                         type="string",
     *                         enum={"unpaid", "paid"},
     *                         example="paid"
     *                     ),
     *                     @OA\Property(
     *                         property="paid_at",
     *                         type="string",
     *                         format="date-time",
     *                         nullable=true,
     *                         example="2026-11-08T11:35:42"
     *                     ),
     *                     @OA\Property(
     *                         property="created_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2026-11-07T10:00:00"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized or school ID does not belong to authenticated School Admin"
     *     )
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

        // Retrieve bills for the requested school.
        $bills = SchoolBill::where('school_id', (string) $schid)
            ->orderByDesc('id')
            ->get([
                'id',
                'academic_session',
                'term',
                'student_count',
                'fee_per_student',
                'total_amount',
                'status',
                'paid_at',
                'created_at',
            ]);

        return response()->json([
            'status' => true,
            'message' => 'School bills retrieved successfully.',
            'pld' => $bills,
        ], 200);
    }
}
