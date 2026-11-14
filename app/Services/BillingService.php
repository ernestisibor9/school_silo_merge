<?php

namespace App\Services;

use App\Models\TechnologyFee;
use App\Models\SchoolBill;
use App\Models\SchoolBillAdjustment;
use App\Models\SchoolBillingNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BillingService
{

    /**
     * Count total active learners for a school.
     * Uses the same logic as getSchoolHighlights().
     */
    public function countBillableStudents(
        string $schoolId,
        string $academicSession,
        string $term
    ): int {

        return DB::table('old_student')
            ->join(
                'student_basic_data',
                'old_student.sid',
                '=',
                'student_basic_data.user_id'
            )
            ->where('old_student.schid', $schoolId)
            ->where('old_student.status', 'active')
            ->distinct('old_student.sid')
            ->count();
    }

    /**
     * Calculate a school's technology bill.
     */
    public function calculateBillDetails(
        string $schoolId,
        TechnologyFee $technologyFee
    ): array {
        if ($technologyFee->status !== 'published') {
            throw new \InvalidArgumentException(
                'Only published technology fees can be used to calculate bills.'
            );
        }

        $studentCount = $this->countBillableStudents(
            $schoolId,
            $technologyFee->academic_session,
            $technologyFee->term
        );

        $feePerStudent = (float) $technologyFee->fee_per_student;

        $totalAmount = round($studentCount * $feePerStudent, 2);

        return [
            'technology_fee_id' => $technologyFee->id,
            'school_id' => $schoolId,
            'academic_session' => $technologyFee->academic_session,
            'term' => $technologyFee->term,
            'student_count' => $studentCount,
            'fee_per_student' => $feePerStudent,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Generate a technology bill for a school.
     */
    public function generateBillForSchool(
        string $schoolId,
        TechnologyFee $technologyFee
    ): SchoolBill {
        return DB::transaction(function () use ($schoolId, $technologyFee) {

            if ($technologyFee->status !== 'published') {
                throw new \InvalidArgumentException(
                    'Only published technology fees can generate school bills.'
                );
            }

            // Check whether a bill already exists for this school, session and term.
            $existingBill = SchoolBill::where('school_id', $schoolId)
                ->where('academic_session', $technologyFee->academic_session)
                ->where('term', $technologyFee->term)
                ->lockForUpdate()
                ->first();

            // Never create a duplicate bill or overwrite an existing bill.
            if ($existingBill) {
                return $existingBill;
            }

            // Calculate the bill details.
            $billDetails = $this->calculateBillDetails(
                $schoolId,
                $technologyFee
            );

            // Save the new bill.
            return SchoolBill::create([
                ...$billDetails,
                'status' => 'unpaid',
            ]);
        });
    }

    /**
     * Recalculate an existing unpaid school bill.
     *
     * Records the adjustment and notifies the School Admin
     * whenever the bill amount or learner count changes.
     */
    public function recalculateUnpaidBill(
        SchoolBill $bill,
        ?int $changedBy = null,
        ?string $notes = null
    ): SchoolBill {
        return DB::transaction(function () use (
            $bill,
            $changedBy,
            $notes
        ) {

            // Lock the bill before making any changes.
            $bill = SchoolBill::whereKey($bill->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Never modify a bill that has already been paid.
            if ($bill->status === 'paid') {
                return $bill;
            }

            // Get the current published fee for this session and term.
            $technologyFee = TechnologyFee::where(
                'academic_session',
                $bill->academic_session
            )
                ->where('term', $bill->term)
                ->where('status', 'published')
                ->firstOrFail();

            // Calculate the latest bill details.
            $newDetails = $this->calculateBillDetails(
                $bill->school_id,
                $technologyFee
            );

            // Capture the previous values before updating the bill.
            $previousStudentCount = (int) $bill->student_count;
            $previousFeePerStudent = (float) $bill->fee_per_student;
            $previousTotalAmount = (float) $bill->total_amount;

            $newStudentCount = (int) $newDetails['student_count'];
            $newFeePerStudent = (float) $newDetails['fee_per_student'];
            $newTotalAmount = (float) $newDetails['total_amount'];

            // Check whether the learner count or fee has changed.
            $studentCountChanged = $previousStudentCount !== $newStudentCount;

            $feeChanged = round($previousFeePerStudent, 2)
                !== round($newFeePerStudent, 2);

            // Nothing changed, so no adjustment or notification is needed.
            if (!$studentCountChanged && !$feeChanged) {
                return $bill;
            }

            // Find the School Admin responsible for this school.
            $schoolAdmin = User::where('id', $bill->school_id)
                ->where('typ', 's')
                ->first();

            if (!$schoolAdmin) {
                throw new \RuntimeException(
                    'No School Admin account was found for this school.'
                );
            }

            // Determine the adjustment reason.
            if ($studentCountChanged && $feeChanged) {
                $reason = 'fee_and_learner_count_change';
            } elseif ($feeChanged) {
                $reason = 'fee_change';
            } else {
                $reason = 'learner_count_change';
            }

            // Update the unpaid bill with the latest values.
            $bill->update([
                'technology_fee_id' => $newDetails['technology_fee_id'],
                'student_count' => $newStudentCount,
                'fee_per_student' => $newFeePerStudent,
                'total_amount' => $newTotalAmount,
            ]);

            // Record the adjustment for audit purposes.
            $adjustment = SchoolBillAdjustment::create([
                'school_bill_id' => $bill->id,
                'previous_student_count' => $previousStudentCount,
                'new_student_count' => $newStudentCount,
                'previous_fee_per_student' => $previousFeePerStudent,
                'new_fee_per_student' => $newFeePerStudent,
                'previous_total_amount' => $previousTotalAmount,
                'new_total_amount' => $newTotalAmount,
                'reason' => $reason,
                'changed_by' => $changedBy,
                'notes' => $notes,
            ]);

            // Prepare a notification for the School Admin.
            $message = sprintf(
                'Your technology bill for session %s, term %s has changed. Previous amount: ₦%s. New amount: ₦%s. Previous learner count: %d. New learner count: %d.',
                $bill->academic_session,
                $bill->term,
                number_format($previousTotalAmount, 2),
                number_format($newTotalAmount, 2),
                $previousStudentCount,
                $newStudentCount
            );

            SchoolBillingNotification::create([
                'school_bill_id' => $bill->id,
                'school_bill_adjustment_id' => $adjustment->id,
                'school_id' => $bill->school_id,
                'recipient_id' => $schoolAdmin->id,
                'notification_type' => $reason,
                'title' => 'Technology Bill Updated',
                'message' => $message,
            ]);

            return $bill->fresh();
        });
    }

    /**
     * Generate technology bills for all schools with billable learners.
     *
     * Schools with zero billable learners are skipped.
     *
     * @return array<int, SchoolBill>
     */
    public function generateBillsForAllSchools(
        TechnologyFee $technologyFee
    ): array {
        if ($technologyFee->status !== 'published') {
            throw new \InvalidArgumentException(
                'Only published technology fees can generate school bills.'
            );
        }

        $generatedBills = [];

        // Retrieve all registered school IDs.
        $schoolIds = DB::table('school')
            ->orderBy('sid')
            ->pluck('sid');

        foreach ($schoolIds as $schoolId) {

            // Count active, non-exited learners for this school and term.
            $studentCount = $this->countBillableStudents(
                (string) $schoolId,
                $technologyFee->academic_session,
                $technologyFee->term
            );

            // Do not generate bills for schools with zero learners.
            if ($studentCount === 0) {
                continue;
            }

            // Generate the bill or return the existing bill.
            $bill = $this->generateBillForSchool(
                (string) $schoolId,
                $technologyFee
            );

            $generatedBills[] = $bill;
        }

        return $generatedBills;
    }


    /**
     * Recalculate all existing unpaid bills affected by a technology fee change.
     *
     * Paid bills are never modified.
     *
     * @return int Number of unpaid bills recalculated.
     */
    public function recalculateUnpaidBillsForFee(
        TechnologyFee $technologyFee,
        ?int $changedBy = null
    ): int {
        if ($technologyFee->status !== 'published') {
            throw new \InvalidArgumentException(
                'Only published technology fees can recalculate school bills.'
            );
        }

        $recalculatedCount = 0;

        // Retrieve all unpaid bills for this session and term.
        $bills = SchoolBill::where(
            'academic_session',
            $technologyFee->academic_session
        )
            ->where('term', $technologyFee->term)
            ->where('status', 'unpaid')
            ->get();

        foreach ($bills as $bill) {

            $this->recalculateUnpaidBill(
                $bill,
                $changedBy,
                'Technology fee updated by Domain Admin.'
            );

            $recalculatedCount++;
        }

        return $recalculatedCount;
    }
}
