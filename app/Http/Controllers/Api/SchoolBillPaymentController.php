<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolBill;
use App\Models\SchoolBillPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SchoolBillPaymentController extends Controller
{

/**
 * @OA\Post(
 *     path="/api/billing/school-bills/{billId}/payments/initialize",
 *     operationId="initializeSchoolBillPayment",
 *     tags={"School Technology Billing"},
 *     summary="Initialize technology billing payment",
 *     description="Initializes a Paystack payment for an unpaid school technology bill belonging to the authenticated School Admin. The amount is retrieved from the database.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="billId",
 *         in="path",
 *         required=true,
 *         description="School bill ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Payment initialized successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Technology billing payment initialized successfully."),
 *             @OA\Property(
 *                 property="pld",
 *                 type="object",
 *                 @OA\Property(property="payment_reference", type="string", example="TECHBILL-1-123e4567-e89b-12d3-a456-426614174000"),
 *                 @OA\Property(property="amount", type="string", example="942000.00"),
 *                 @OA\Property(property="currency", type="string", example="NGN"),
 *                 @OA\Property(property="authorization_url", type="string", example="https://checkout.paystack.com/example"),
 *                 @OA\Property(property="access_code", type="string", example="example_access_code")
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
 *         description="Only School Admins can initiate payments"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="School bill not found"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Bill already paid or has no payable amount"
 *     ),
 *     @OA\Response(
 *         response=502,
 *         description="Paystack payment initialization failed"
 *     )
 * )
 */

    public function initialize(Request $request, $billId)
    {
        $user = $request->user();

        // Only authenticated School Admins can pay school bills.
        if (!$user || $user->typ !== 's') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only School Admins can initiate school bill payments.',
            ], 403);
        }

        // Confirm the separate billing Paystack credentials are configured.
        $secretKey = config('services.billing_paystack.secret_key');
        $paymentUrl = config('services.billing_paystack.payment_url');

        if (empty($secretKey) || empty($paymentUrl)) {
            return response()->json([
                'status' => false,
                'message' => 'Technology billing payment configuration is incomplete.',
            ], 500);
        }

        if (empty($user->email)) {
            return response()->json([
                'status' => false,
                'message' => 'Your account must have an email address before making payment.',
            ], 422);
        }

        try {
            // Find the bill and create a pending payment record.
            $payment = DB::transaction(function () use ($billId, $user) {

                $bill = SchoolBill::where('id', $billId)
                    ->where('school_id', (string) $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$bill) {
                    abort(404, 'School bill not found.');
                }

                if ($bill->status === 'paid') {
                    abort(422, 'This school bill has already been paid.');
                }

                if ((float) $bill->total_amount <= 0) {
                    abort(422, 'This school bill has no payable amount.');
                }

                $reference = 'TECHBILL-' . $bill->id . '-' . Str::uuid();

                return SchoolBillPayment::create([
                    'school_bill_id' => $bill->id,
                    'payment_reference' => $reference,
                    'amount' => $bill->total_amount,
                    'currency' => 'NGN',
                    'status' => 'pending',
                ]);
            });

            // Paystack expects amounts in kobo.
            $amountInKobo = (int) round((float) $payment->amount * 100);

            $paystackResponse = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(30)
                ->post($paymentUrl, [
                    'email' => $user->email,
                    'amount' => $amountInKobo,
                    'currency' => 'NGN',
                    'reference' => $payment->payment_reference,
                    'metadata' => [
                        'payment_type' => 'school_technology_billing',
                        'school_bill_id' => $payment->school_bill_id,
                        'school_id' => (string) $user->id,
                        'school_bill_payment_id' => $payment->id,
                    ],
                ]);

            $responseData = $paystackResponse->json();

            // Save Paystack's response for transaction tracking.
            DB::table('school_bill_payments')
                ->where('id', $payment->id)
                ->update([
                    'gateway_response' => json_encode($responseData),
                    'updated_at' => now(),
                ]);

            if (
                !$paystackResponse->successful() ||
                !data_get($responseData, 'status')
            ) {
                DB::table('school_bill_payments')
                    ->where('id', $payment->id)
                    ->update([
                        'status' => 'failed',
                        'updated_at' => now(),
                    ]);

                Log::error('Technology billing Paystack initialization failed.', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'response' => $responseData,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to initialize technology billing payment.',
                ], 502);
            }

            return response()->json([
                'status' => true,
                'message' => 'Technology billing payment initialized successfully.',
                'pld' => [
                    'payment_reference' => $payment->payment_reference,
                    'amount' => $payment->amount,
                    'currency' => 'NGN',
                    'authorization_url' => data_get($responseData, 'data.authorization_url'),
                    'access_code' => data_get($responseData, 'data.access_code'),
                ],
            ], 200);

        } catch (Throwable $e) {

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            Log::error('Technology billing payment initialization error.', [
                'bill_id' => $billId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while initializing your payment.',
            ], 500);
        }
    }
}
