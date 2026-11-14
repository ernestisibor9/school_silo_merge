<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolBill;
use App\Models\SchoolBillPayment;
use App\Models\SchoolSuspensionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SchoolBillPaymentController extends Controller
{
    /**
     * Generate a unique technology billing receipt number..
     *
     * Format: SSM-YYYYMMDD-UUID
     */
    private function generateReceiptNumber(): string
    {
        return 'SSM-' . now()->format('Ymd') . '-' . Str::uuid();
    }


/**
 * @OA\Post(
 *     path="/api/billing/school-bills/{billId}/payments/initialize",
 *     operationId="initializeSchoolBillPayment",
 *     tags={"School Technology Billing"},
 *     summary="Initialize technology billing payment",
 *     description="Initializes a Paystack payment for an unpaid school technology bill belonging to the authenticated School Admin. The amount is retrieved from the database using the separate School Technology Billing Paystack account, with an additional transaction charge.",
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
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="charge",
 *                 type="number",
 *                 format="float",
 *                 example=89.00,
 *                 description="Additional transaction charge in Naira"
 *             )
 *         )
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
 *                 @OA\Property(
 *                     property="payment_reference",
 *                     type="string",
 *                     example="schoolsilo-billing-1-123e4567-e89b-12d3-a456-426614174000"
 *                 ),
 *                 @OA\Property(property="amount", type="string", example="2039.00"),
 *                 @OA\Property(property="currency", type="string", example="NGN"),
 *                 @OA\Property(property="authorization_url", type="string", example="https://checkout.paystack.com/example"),
 *                 @OA\Property(property="access_code", type="string", example="example_access_code")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only School Admins can initiate payments"),
 *     @OA\Response(response=404, description="School bill not found"),
 *     @OA\Response(response=422, description="Invalid charge, bill already paid or has no payable amount"),
 *     @OA\Response(response=500, description="Technology billing payment configuration incomplete or server error"),
 *     @OA\Response(response=502, description="Paystack payment initialization failed")
 * )
 */
public function initialize(Request $request, $billId)
{
    $user = $request->user();

    if (!$user || $user->typ !== 's') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized. Only School Admins can initiate school bill payments.',
        ], 403);
    }

    // Validate the transaction charge supplied by the frontend.
    $chargeInput = $request->input('charge', 0);

    if (!is_numeric($chargeInput) || (float) $chargeInput < 0) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid transaction charge.',
        ], 422);
    }

    // Store the charge in Naira, rounded to two decimal places.
    $charge = round((float) $chargeInput, 2);

    // Use the separate technology billing Paystack credentials.
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
        $payment = DB::transaction(function () use ($billId, $user, $charge) {

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

            $reference = 'schoolsilo-billing-' . $bill->id . '-' . Str::uuid();

            // Add the transaction charge to the original school bill.
            $totalPayable = round((float) $bill->total_amount + $charge, 2);

            return SchoolBillPayment::create([
                'school_bill_id' => $bill->id,
                'payment_reference' => $reference,
                'amount' => $totalPayable,
                'transaction_charge' => $charge,
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
                    'transaction_charge' => $payment->transaction_charge,
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


    /**
     * Handle Paystack webhook for school technology billing.
     *
     * This webhook is independent of the existing paystackConf() method.
     */

    public function webhook(Request $request)
    {
        $secretKey = config('services.billing_paystack.secret_key');

        if (empty($secretKey)) {
            Log::error('Technology billing webhook: billing Paystack secret is missing.');

            return response()->json([
                'status' => false,
                'message' => 'Payment configuration is incomplete.',
            ], 500);
        }

        // Read the incoming request body.
        $payloadRaw = $request->getContent();

        // Initially assume this is a direct Paystack webhook.
        $signaturePayload = $payloadRaw;

        // Decode the incoming JSON.
        $outerPayload = json_decode($payloadRaw, true);

        if (!is_array($outerPayload)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        // Check whether ADSI forwarded the original Paystack payload.
        if (
            isset($outerPayload['payload']) &&
            is_string($outerPayload['payload'])
        ) {
            $signaturePayload = $outerPayload['payload'];
        }

        // Decode the original Paystack payload.
        $payload = json_decode($signaturePayload, true);

        if (!is_array($payload)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Paystack payload.',
            ], 400);
        }

        // Verify the original Paystack signature.
        $signature = $request->header('x-paystack-signature');

        if (empty($signature)) {
            Log::warning('Technology billing webhook: missing Paystack signature.');

            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        $expectedSignature = hash_hmac(
            'sha512',
            $signaturePayload,
            $secretKey
        );

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Technology billing webhook: invalid Paystack signature.');

            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        // Process only successful payment events.
        if (($payload['event'] ?? null) !== 'charge.success') {
            return response()->json([
                'status' => true,
                'message' => 'Webhook event acknowledged.',
            ], 200);
        }

        $reference = data_get($payload, 'data.reference');

        if (empty($reference)) {
            Log::warning('Technology billing webhook: payment reference missing.');

            return response()->json([
                'status' => false,
                'message' => 'Payment reference is missing.',
            ], 400);
        }

        $verifyUrl = config('services.billing_paystack.verify_url');

        if (empty($verifyUrl)) {
            Log::error('Technology billing webhook: verification URL is missing.');

            return response()->json([
                'status' => false,
                'message' => 'Payment verification configuration is incomplete.',
            ], 500);
        }

        try {
            // Find the payment in our technology billing payment table.
            $payment = SchoolBillPayment::where(
                'payment_reference',
                $reference
            )->first();

            if (!$payment) {
                Log::warning('Technology billing webhook: payment reference not found.', [
                    'reference' => $reference,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Technology billing payment not found.',
                ], 404);
            }

            // Independently verify the transaction directly with Paystack.
            $verificationResponse = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(30)
                ->get($verifyUrl . urlencode($reference));

            if (!$verificationResponse->successful()) {
                Log::error('Technology billing webhook: Paystack verification request failed.', [
                    'reference' => $reference,
                    'http_status' => $verificationResponse->status(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to verify payment with Paystack.',
                ], 502);
            }

            $verificationData = $verificationResponse->json();

            if (
                !data_get($verificationData, 'status') ||
                data_get($verificationData, 'data.status') !== 'success'
            ) {
                Log::warning('Technology billing webhook: transaction was not successful.', [
                    'reference' => $reference,
                    'verification_response' => $verificationData,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Paystack has not confirmed this payment as successful.',
                ], 422);
            }

            // Confirm that Paystack returned the exact reference.
            if (
                data_get($verificationData, 'data.reference') !==
                $payment->payment_reference
            ) {
                Log::warning('Technology billing webhook: reference mismatch.', [
                    'reference' => $reference,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Payment reference verification failed.',
                ], 422);
            }

            // Paystack amounts are in kobo; our database stores naira.
            $expectedAmount = (int) round((float) $payment->amount * 100);
            $verifiedAmount = (int) data_get($verificationData, 'data.amount', 0);

            if ($verifiedAmount !== $expectedAmount) {
                Log::warning('Technology billing webhook: amount mismatch.', [
                    'reference' => $reference,
                    'expected_amount' => $expectedAmount,
                    'verified_amount' => $verifiedAmount,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Payment amount verification failed.',
                ], 422);
            }

            $verifiedCurrency = strtoupper(
                (string) data_get($verificationData, 'data.currency', '')
            );

            if ($verifiedCurrency !== strtoupper($payment->currency)) {
                Log::warning('Technology billing webhook: currency mismatch.', [
                    'reference' => $reference,
                    'expected_currency' => $payment->currency,
                    'verified_currency' => $verifiedCurrency,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Payment currency verification failed.',
                ], 422);
            }

            // Update payment, bill, receipt and school atomically.
            $result = DB::transaction(function () use ($payment, $verificationData) {
                $lockedPayment = SchoolBillPayment::where('id', $payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $bill = SchoolBill::where('id', $lockedPayment->school_bill_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Duplicate delivery of an already completed payment.
                if (
                    $lockedPayment->status === 'successful' &&
                    $bill->status === 'paid'
                ) {
                    // Generate a receipt only if one is missing.
                    if (empty($lockedPayment->receipt_number)) {
                        $lockedPayment->update([
                            'receipt_number' => $this->generateReceiptNumber(),
                        ]);
                    }

                    return 'already_processed';
                }

                // Never process a payment that is not pending.
                if ($lockedPayment->status !== 'pending') {
                    return 'payment_not_pending';
                }

                // Another payment reference may already have settled this bill.
                if ($bill->status === 'paid') {
                    return 'bill_already_paid';
                }

                $lockedPayment->update([
                    'status' => 'successful',
                    'receipt_number' => $lockedPayment->receipt_number
                        ?: $this->generateReceiptNumber(),
                    'paystack_transaction_id' => data_get($verificationData, 'data.id'),
                    'channel' => data_get($verificationData, 'data.channel'),
                    'gateway_response' => json_encode($verificationData),
                    'verified_at' => now(),
                ]);

                // Mark the corresponding technology bill as paid.
                $bill->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Automatically reactivate the school if it is suspended.
                $school = DB::table('school')
                    ->where('sid', $bill->school_id)
                    ->where('school_status', 0)
                    ->lockForUpdate()
                    ->first();

                if ($school) {
                    DB::table('school')
                        ->where('sid', $bill->school_id)
                        ->update([
                            'school_status' => 1,
                        ]);

                    SchoolSuspensionLog::create([
                        'school_id' => $bill->school_id,
                        'action' => 'reactivated',
                        'performed_by' => null,
                        'reason' => 'Automatically reactivated after successful technology billing payment.',
                    ]);
                }

                return 'processed';
            });

            // Handle the transaction outcome.
            if ($result === 'already_processed') {
                return response()->json([
                    'status' => true,
                    'message' => 'Payment was already processed.',
                    'pld' => [
                        'receipt_number' => $payment->fresh()->receipt_number,
                    ],
                ], 200);
            }

            if ($result === 'payment_not_pending') {
                Log::warning('Technology billing webhook: payment is not pending.', [
                    'reference' => $reference,
                    'payment_status' => $payment->fresh()->status,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'This payment cannot be processed in its current state.',
                ], 409);
            }

            if ($result === 'bill_already_paid') {
                Log::critical('Technology billing webhook: another payment has already settled this bill.', [
                    'reference' => $reference,
                    'payment_id' => $payment->id,
                    'bill_id' => $payment->school_bill_id,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'This bill has already been settled by another payment.',
                ], 409);
            }

            Log::info('Technology billing payment verified successfully.', [
                'payment_reference' => $reference,
                'payment_id' => $payment->id,
                'bill_id' => $payment->school_bill_id,
                'receipt_number' => $payment->fresh()->receipt_number,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Technology billing payment verified successfully.',
                'pld' => [
                    'payment_reference' => $reference,
                    'receipt_number' => $payment->fresh()->receipt_number,
                ],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Technology billing webhook processing error.', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing the payment webhook.',
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/billing/school-bill-payments/{reference}/verify",
     *     operationId="verifySchoolBillPayment",
     *     tags={"School Technology Billing"},
     *     summary="Verify school technology billing payment",
     *     description="Verifies a technology billing payment directly with Paystack and updates its payment and bill status.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Paystack payment reference",
     *         @OA\Schema(type="string", example="TECHBILL-1-123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment verification result"
     *     ),
     *     @OA\Response(response=202, description="Payment is still pending"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only School Admins can verify payments"),
     *     @OA\Response(response=404, description="Payment not found"),
     *     @OA\Response(response=422, description="Payment verification failed"),
     *     @OA\Response(response=502, description="Paystack verification request failed")
     * )
     */
    public function verify(Request $request, $reference)
    {
        $user = $request->user();

        // Only authenticated School Admins can verify their payments.
        if (!$user || $user->typ !== 's') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only School Admins can verify payments.',
            ], 403);
        }

        $secretKey = config('services.billing_paystack.secret_key');
        $verifyUrl = config('services.billing_paystack.verify_url');

        if (empty($secretKey) || empty($verifyUrl)) {
            return response()->json([
                'status' => false,
                'message' => 'Technology billing payment configuration is incomplete.',
            ], 500);
        }

        try {
            // Ensure this payment belongs to a bill owned by this School Admin.
            $payment = SchoolBillPayment::query()
                ->join(
                    'school_bills',
                    'school_bill_payments.school_bill_id',
                    '=',
                    'school_bills.id'
                )
                ->where('school_bill_payments.payment_reference', $reference)
                ->where('school_bills.school_id', (string) $user->id)
                ->select('school_bill_payments.*')
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment not found for your school.',
                ], 404);
            }

            // A payment already successfully processed does not need verification again.
            if ($payment->status === 'successful') {
                return response()->json([
                    'status' => true,
                    'message' => 'This payment has already been verified successfully.',
                    'pld' => [
                        'payment_reference' => $payment->payment_reference,
                        'payment_status' => 'successful',
                        'receipt_number' => $payment->receipt_number,
                    ],
                ], 200);
            }

            // Ask Paystack for the authoritative transaction status.
            $verificationResponse = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(30)
                ->get(
                    rtrim($verifyUrl, '/') . '/' . rawurlencode($reference)
                );

            if (!$verificationResponse->successful()) {
                Log::error('Technology billing payment verification request failed.', [
                    'payment_reference' => $reference,
                    'http_status' => $verificationResponse->status(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to verify payment with Paystack.',
                ], 502);
            }

            $verificationData = $verificationResponse->json();

            if (!data_get($verificationData, 'status')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Paystack could not verify this transaction.',
                ], 502);
            }

            // Confirm that Paystack returned the exact payment reference.
            if (
                data_get($verificationData, 'data.reference') !==
                $payment->payment_reference
            ) {
                Log::warning('Technology billing verification reference mismatch.', [
                    'payment_reference' => $reference,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Payment reference verification failed.',
                ], 422);
            }

            // Confirm the exact amount.
            $expectedAmount = (int) round((float) $payment->amount * 100);
            $verifiedAmount = (int) data_get($verificationData, 'data.amount', 0);

            if ($verifiedAmount !== $expectedAmount) {
                Log::warning('Technology billing verification amount mismatch.', [
                    'payment_reference' => $reference,
                    'expected_amount' => $expectedAmount,
                    'verified_amount' => $verifiedAmount,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Payment amount verification failed.',
                ], 422);
            }

            // Confirm the currency.
            $verifiedCurrency = strtoupper(
                (string) data_get($verificationData, 'data.currency', '')
            );

            if ($verifiedCurrency !== strtoupper($payment->currency)) {
                Log::warning('Technology billing verification currency mismatch.', [
                    'payment_reference' => $reference,
                    'expected_currency' => $payment->currency,
                    'verified_currency' => $verifiedCurrency,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Payment currency verification failed.',
                ], 422);
            }

            $transactionStatus = strtolower(
                (string) data_get($verificationData, 'data.status', '')
            );

            // Process only recognised terminal failure states.
            if (in_array($transactionStatus, ['failed', 'abandoned'], true)) {

                $result = DB::transaction(function () use ($payment, $verificationData) {

                    $lockedPayment = SchoolBillPayment::where('id', $payment->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedPayment->status === 'successful') {
                        return 'already_successful';
                    }

                    if ($lockedPayment->status !== 'pending') {
                        return 'already_processed';
                    }

                    $lockedPayment->update([
                        'status' => 'failed',
                        'paystack_transaction_id' => data_get($verificationData, 'data.id'),
                        'channel' => data_get($verificationData, 'data.channel'),
                        'gateway_response' => json_encode($verificationData),
                        'verified_at' => now(),
                    ]);

                    return 'failed';
                });

                if ($result === 'already_successful') {
                    $freshPayment = $payment->fresh();

                    return response()->json([
                        'status' => true,
                        'message' => 'This payment has already been processed successfully.',
                        'pld' => [
                            'payment_reference' => $freshPayment->payment_reference,
                            'payment_status' => 'successful',
                            'receipt_number' => $freshPayment->receipt_number,
                        ],
                    ], 200);
                }

                if ($result === 'already_processed') {
                    return response()->json([
                        'status' => false,
                        'message' => 'This payment has already been processed.',
                    ], 409);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Paystack confirmed that this payment failed.',
                    'pld' => [
                        'payment_reference' => $reference,
                        'payment_status' => 'failed',
                        'gateway_response' => data_get(
                            $verificationData,
                            'data.gateway_response'
                        ),
                    ],
                ], 200);
            }

            // Do not change the database for pending or unknown transaction states.
            if ($transactionStatus !== 'success') {
                return response()->json([
                    'status' => true,
                    'message' => 'Paystack has not confirmed a final payment result.',
                    'pld' => [
                        'payment_reference' => $reference,
                        'payment_status' => $transactionStatus ?: 'unknown',
                    ],
                ], 202);
            }

            // Process a verified successful payment atomically.
            $result = DB::transaction(function () use ($payment, $verificationData) {

                $lockedPayment = SchoolBillPayment::where('id', $payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $bill = SchoolBill::where('id', $lockedPayment->school_bill_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedPayment->status === 'successful' &&
                    $bill->status === 'paid'
                ) {
                    // Ensure a successful payment has a receipt.
                    if (empty($lockedPayment->receipt_number)) {
                        $lockedPayment->update([
                            'receipt_number' => $this->generateReceiptNumber(),
                        ]);
                    }

                    return 'already_processed';
                }

                if ($lockedPayment->status !== 'pending') {
                    return 'payment_not_pending';
                }

                if ($bill->status === 'paid') {
                    return 'bill_already_paid';
                }

                $lockedPayment->update([
                    'status' => 'successful',
                    'receipt_number' => $lockedPayment->receipt_number
                        ?: $this->generateReceiptNumber(),
                    'paystack_transaction_id' => data_get($verificationData, 'data.id'),
                    'channel' => data_get($verificationData, 'data.channel'),
                    'gateway_response' => json_encode($verificationData),
                    'verified_at' => now(),
                ]);

                $bill->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Automatically reactivate the school after successful payment.
                $school = DB::table('school')
                    ->where('sid', $bill->school_id)
                    ->where('school_status', 0)
                    ->lockForUpdate()
                    ->first();

                if ($school) {

                    DB::table('school')
                        ->where('sid', $bill->school_id)
                        ->update([
                            'school_status' => 1,
                        ]);

                    SchoolSuspensionLog::create([
                        'school_id' => $bill->school_id,
                        'action' => 'reactivated',
                        'performed_by' => null,
                        'reason' => 'Automatically reactivated after successful technology billing payment.',
                    ]);
                }

                return 'processed';
            });

            if ($result === 'already_processed') {
                $freshPayment = $payment->fresh();

                return response()->json([
                    'status' => true,
                    'message' => 'Payment has already been processed successfully.',
                    'pld' => [
                        'payment_reference' => $freshPayment->payment_reference,
                        'payment_status' => 'successful',
                        'receipt_number' => $freshPayment->receipt_number,
                    ],
                ], 200);
            }

            if ($result === 'payment_not_pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'This payment cannot be processed in its current state.',
                ], 409);
            }

            if ($result === 'bill_already_paid') {
                Log::critical('Technology billing verification: bill already paid by another payment.', [
                    'payment_reference' => $reference,
                    'payment_id' => $payment->id,
                    'bill_id' => $payment->school_bill_id,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'This bill has already been settled by another payment.',
                ], 409);
            }

            $freshPayment = $payment->fresh();

            Log::info('Technology billing payment verified successfully through API.', [
                'payment_reference' => $reference,
                'payment_id' => $payment->id,
                'bill_id' => $payment->school_bill_id,
                'receipt_number' => $freshPayment->receipt_number,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Technology billing payment verified successfully.',
                'pld' => [
                    'payment_reference' => $reference,
                    'payment_status' => 'successful',
                    'receipt_number' => $freshPayment->receipt_number,
                ],
            ], 200);
        } catch (Throwable $e) {

            Log::error('Technology billing payment verification error.', [
                'payment_reference' => $reference,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while verifying your payment.',
            ], 500);
        }
    }




/**
 * @OA\Get(
 *     path="/api/billing/school-bills/{billId}/payments/reference",
 *     operationId="getSchoolBillPaymentReference",
 *     tags={"School Technology Billing"},
 *     summary="Retrieve the latest pending payment reference for a school bill",
 *     description="Retrieves the latest pending payment reference belonging to the authenticated School Admin's bill.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="billId",
 *         in="path",
 *         required=true,
 *         description="School bill ID",
 *         @OA\Schema(type="integer", example=52)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Payment reference retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Payment reference retrieved successfully."),
 *             @OA\Property(
 *                 property="pld",
 *                 type="object",
 *                 @OA\Property(property="payment_reference", type="string", example="schoolsilo-billing-52-333fe7d9-8f1a-4beb-8ff4-f6d7"),
 *                 @OA\Property(property="amount", type="number", format="float", example=1950.00),
 *                 @OA\Property(property="currency", type="string", example="NGN"),
 *                 @OA\Property(property="payment_status", type="string", example="pending")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only School Admins can retrieve payment references"),
 *     @OA\Response(response=404, description="School bill or pending payment reference not found")
 * )
 */
public function getPaymentReference(Request $request, $billId)
{
    $user = $request->user();

    // Only authenticated School Admins can retrieve payment references.
    if (!$user || $user->typ !== 's') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized. Only School Admins can retrieve payment references.',
        ], 403);
    }

    // Confirm that the bill belongs to the authenticated School Admin.
    $bill = SchoolBill::where('id', $billId)
        ->where('school_id', (string) $user->id)
        ->first();

    if (!$bill) {
        return response()->json([
            'status' => false,
            'message' => 'School bill not found.',
        ], 404);
    }

    // Retrieve the latest pending payment reference for this bill.
    $payment = SchoolBillPayment::where('school_bill_id', $bill->id)
        ->where('status', 'pending')
        ->orderByDesc('id')
        ->first();

    if (!$payment) {
        return response()->json([
            'status' => false,
            'message' => 'No pending payment reference was found for this school bill.',
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'Payment reference retrieved successfully.',
        'pld' => [
            'payment_reference' => $payment->payment_reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_status' => $payment->status,
        ],
    ], 200);
}





    /**
     * @OA\Get(
     *     path="/api/billing/school-bill-payments/{reference}/receipt",
     *     operationId="getSchoolBillPaymentReceipt",
     *     tags={"School Bill Payments"},
     *     summary="Retrieve a verified school bill payment receipt",
     *     description="Retrieves the receipt for a successfully verified technology billing payment. Only the authenticated School Admin who owns the bill can access the receipt.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Paystack payment reference",
     *         @OA\Schema(type="string", example="TECHBILL-1-82e15631-53b0-421d-ac")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment receipt retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment receipt retrieved successfully."),
     *             @OA\Property(
     *                 property="pld",
     *                 type="object",
     *                 @OA\Property(property="receipt_number", type="string", example="SSM-20261108-550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="school_name", type="string", example="Holy Ghost College"),
     *                 @OA\Property(property="school_id", type="string", example="12"),
     *                 @OA\Property(property="academic_session", type="string", example="2026/2027"),
     *                 @OA\Property(property="term", type="string", example="First Term"),
     *                 @OA\Property(property="student_count", type="integer", example=471),
     *                 @OA\Property(property="fee_per_student", type="number", format="float", example=2000.00),
     *                 @OA\Property(property="amount_paid", type="number", format="float", example=942000.00),
     *                 @OA\Property(property="currency", type="string", example="NGN"),
     *                 @OA\Property(property="payment_reference", type="string", example="TECHBILL-1-82e15631-53b0-421d-ac"),
     *                 @OA\Property(property="paystack_transaction_id", type="integer", nullable=true, example=123456789),
     *                 @OA\Property(property="payment_date", type="string", format="date-time", example="2026-11-08T10:30:00.000000Z"),
     *                 @OA\Property(property="status", type="string", example="successful")
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
     *         description="Only School Admins can access payment receipts",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only School Admins can access payment receipts.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Receipt not found or does not belong to the authenticated school",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Receipt not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Payment has not been successfully verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="A receipt is only available for successfully verified payments.")
     *         )
     *     )
     * )
     */

    public function receipt(Request $request, $reference)
    {
        $user = $request->user();

        // Only School Admins can retrieve school payment receipts.
        if (!$user || $user->typ !== 's') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only School Admins can access payment receipts.',
            ], 403);
        }

        $receipt = DB::table('school_bill_payments as payments')
            ->join('school_bills as bills', 'payments.school_bill_id', '=', 'bills.id')
            ->join('school', 'bills.school_id', '=', 'school.sid')
            ->where('payments.payment_reference', $reference)
            ->where('bills.school_id', (string) $user->id)
            ->select([
                'payments.receipt_number',
                'school.name as school_name',
                'school.sid as school_id',
                'bills.academic_session',
                'bills.term',
                'bills.student_count',
                'bills.fee_per_student',
                'payments.amount as amount_paid',
                'payments.currency',
                'payments.payment_reference',
                'payments.paystack_transaction_id',
                'payments.verified_at as payment_date',
                'payments.status',
            ])
            ->first();

        if (!$receipt) {
            return response()->json([
                'status' => false,
                'message' => 'Receipt not found.',
            ], 404);
        }

        if ($receipt->status !== 'successful' || !$receipt->receipt_number) {
            return response()->json([
                'status' => false,
                'message' => 'A receipt is only available for successfully verified payments.',
            ], 409);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment receipt retrieved successfully.',
            'pld' => $receipt,
        ], 200);
    }
}
