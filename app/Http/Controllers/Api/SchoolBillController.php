<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolBill;
use Illuminate\Http\Request;

class SchoolBillController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/billing/school-bills",
 *     tags={"Billing"},
 *     summary="Get school technology bills",
 *     description="Retrieves all technology bills belonging to the authenticated School Admin's school. Only School Admins can access this endpoint.",
 *     operationId="getSchoolTechnologyBills",
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="School bills retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status",
 *                 type="boolean",
 *                 example=true
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="School bills retrieved successfully."
 *             ),
 *             @OA\Property(
 *                 property="pld",
 *                 type="object",
 *                 @OA\Property(
 *                     property="bills",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="academic_session", type="string", example="2026/2027"),
 *                         @OA\Property(property="term", type="string", example="First Term"),
 *                         @OA\Property(property="student_count", type="integer", example=471),
 *                         @OA\Property(property="fee_per_student", type="number", format="float", example=2000.00),
 *                         @OA\Property(property="total_amount", type="number", format="float", example=942000.00),
 *                         @OA\Property(property="status", type="string", enum={"unpaid", "paid"}, example="unpaid"),
 *                         @OA\Property(property="paid_at", type="string", format="date-time", nullable=true, example=null),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-07T10:00:00.000000Z")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated. A valid JWT token is required."
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized. Only School Admins can access school bills."
 *     )
 * )
 */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only School Admins can access this endpoint.
        if (!$user || $user->typ !== 's') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only School Admins can access school bills.',
            ], 403);
        }

        // A School Admin's user ID corresponds to school.sid.
        $bills = SchoolBill::where('school_id', (string) $user->id)
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
            'pld' => [
                'bills' => $bills,
            ],
        ], 200);
    }
}
