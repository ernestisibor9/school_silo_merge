<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes PREFIX = api

// -- OPEN ENDPOINTS

Route::post('registerSchool', [ApiController::class, 'registerSchool']);
Route::post('schoolLogin', [ApiController::class, 'schoolLogin']);
Route::post('sendPasswordResetEmail', [ApiController::class, 'sendPasswordResetEmail']);
Route::post('resetPassword', [ApiController::class, 'resetPassword']);
Route::post('partnerLogin', [ApiController::class, 'partnerLogin']);
Route::post('registerPartner', [ApiController::class, 'registerPartner']);
Route::post('registerStudent', [ApiController::class, 'registerStudent']);
Route::post('studentLoginByEmail', [ApiController::class, 'studentLoginByEmail']);
Route::post('studentLoginByID', [ApiController::class, 'studentLoginByID']);
Route::post('registerStaff', [ApiController::class, 'registerStaff']);
Route::post('staffLoginByEmail', [ApiController::class, 'staffLoginByEmail']);
Route::post('staffLoginByID', [ApiController::class, 'staffLoginByID']);
Route::post('adminLogin', [ApiController::class, 'adminLogin']);
Route::post('paystackConf', [ApiController::class, 'paystackConf']);
Route::post('resolveIDtoEmail', [ApiController::class, 'resolveIDtoEmail']);

Route::post('setSubject', [ApiController::class, 'setSubj']);
Route::post('setClass', [ApiController::class, 'setCls']);
Route::post('setAdminStaffRole', [ApiController::class, 'setAdminStaffRole']);
Route::post('setSchoolStaffRole', [ApiController::class, 'setSchoolStaffRole']);

Route::post('promoteStudent', [ApiController::class, 'promoteStudent']);
Route::post('repeatStudent', [ApiController::class, 'repeatStudent']);



Route::get('verifyEmail/{typ}/{code}/{schid}', [ApiController::class, 'verifyEmail']);
Route::get('getSchoolWebInfo/{uid}', [ApiController::class, 'getSchoolWebInfo']);
Route::get('getSchoolBySBD/{sbd}', [ApiController::class, 'getSchoolBySBD']);
Route::get('getSchoolWebInfoBySBD/{sbd}', [ApiController::class, 'getSchoolWebInfoBySBD']);
Route::get('getFile/{folder}/{filename}', [ApiController::class, 'getFile']);
Route::get('getSchool/{uid}', [ApiController::class, 'getSchool']);
Route::get('getSchoolLatestNews/{uid}', [ApiController::class, 'getSchoolLatestNews']);
Route::get('getAdminStaffRoles', [ApiController::class, 'getAdminStaffRoles']);
Route::get('getAdminStaffRoleStat', [ApiController::class, 'getAdminStaffRoleStat']);
Route::get('getAdminStaffRole/{rid}', [ApiController::class, 'getAdminStaffRole']);
Route::get('getSchoolStaffRoles/{schid}', [ApiController::class, 'getSchoolStaffRoles']);
Route::get('getSchoolStaffRole/{rid}', [ApiController::class, 'getSchoolStaffRole']);

//-B/c of result checking
Route::get('getSessions', [ApiController::class, 'getSesns']);
Route::get('getTerms', [ApiController::class, 'getTrms']);
Route::get('getClassMarks/{schid}/{clsid}/{ssn}/{trm}', [ApiController::class, 'getClassMarks']);
Route::get('getStudent', [ApiController::class, 'getStudent']);
Route::get('getClass/{cid}', [ApiController::class, 'getCls']);
Route::get('getClassGrades/{schid}/{clsid}/{ssn}/{trm}', [ApiController::class, 'getClassGrades']);
Route::get('getStudentPsy/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}', [ApiController::class, 'getStudentPsy']);
Route::get('getStudentRes/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}', [ApiController::class, 'getStudentRes']);
Route::get('getStudentResult/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}', [ApiController::class, 'getStudentResult']);
Route::get('getClassSubject/{schid}/{clsid}/{sbid}', [ApiController::class, 'getClassSubject']);
Route::get('getStudentResultsByArm/{schid}/{clsid}/{ssn}/{trm}/{arm}', [ApiController::class, 'getStudentResultsByArm']);
Route::get('getResultMeta/{schid}/{ssn}/{trm}', [ApiController::class, 'getResultMeta']);
Route::get('getClassSubjectsByStaff/{schid}/{clsid}/{stid}', [ApiController::class, 'getClassSubjectsByStaff']);
Route::get('getClassSubjects/{schid}/{clsid}/{sesn}/{trm}', [ApiController::class, 'getClassSubjects']);
Route::get('getASchoolClassArm/{cid}', [ApiController::class, 'getASchoolClassArm']);
Route::get('getStudentSubjPos/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}', [ApiController::class, 'getStudentSubjPos']);

Route::get('getEveryStudentCumulativeResult', [ApiController::class, 'getEveryStudentCumulativeResult']);

Route::get('getCumulativeResult', [ApiController::class, 'getCumulativeResult']);
Route::get('getAllStudentCumulativeResult', [ApiController::class, 'getAllStudentCumulativeResult']);

Route::get('getYearlyAssessmentAverage', [ApiController::class, 'getYearlyAssessmentAverage']);
Route::get('getAllStudentsYearlyAssessmentAverages', [ApiController::class, 'getAllStudentsYearlyAssessmentAverages']);

Route::get('getTotalStudentCumulativeResult', [ApiController::class, 'getTotalStudentCumulativeResult']);

Route::get('getLoggedInUserDetails', [ApiController::class, 'getLoggedInUserDetails']);
Route::get('getCummulativeBroadsheet/{schid}/{ssn}/{clsm}/{clsa}', [ApiController::class, 'getCummulativeBroadsheet']);
Route::get('/getComment/{stid}/{schid}/{clsm}/{sesn}', [ApiController::class, 'getComment']);
Route::get('/getAllComment/{schid}/{clsm}/{clsa}/{ssn}', [ApiController::class, 'getAllComment']);



//--- Only for mess handling
Route::get('mgetSchools', [ApiController::class, 'mgetSchools']);
Route::get('mgetSchoolClasses/{schid}', [ApiController::class, 'mgetSchoolClasses']);
Route::get('mgetStudentSubjects/{stid}', [ApiController::class, 'mgetStudentSubjects']);
Route::get('mgetStudentInSchoolAndClass/{schid}/{cls}', [ApiController::class, 'mgetStudentInSchoolAndClass']);


// - PROTECTED ENDPOINTS
Route::post('setStudentSubject', [ApiController::class, 'setStudentSubject']);
Route::post('setClassSubject', [ApiController::class, 'setClassSubject']);
Route::get('getSubjects', [ApiController::class, 'getSubjs']);

Route::group([
    'middleware' => ['auth:api'],
], function () {

    Route::post('sendEmailVerificationLink', [ApiController::class, 'sendEmailVerificationLink']);
    Route::post('setSchool', [ApiController::class, 'setSchool']);
    Route::post('setPartner', [ApiController::class, 'setPartner']);
    Route::post('setSchoolWebData', [ApiController::class, 'setSchoolWebData']);
    Route::post('setSchoolClassArm', [ApiController::class, 'setSchoolClassArm']);
    Route::post('setSchoolClass', [ApiController::class, 'setSchoolClass']);
    Route::post('setSession', [ApiController::class, 'setSesn']);
    Route::post('setSchoolSession', [ApiController::class, 'setSchSesn']);
    Route::post('setTerm', [ApiController::class, 'setTrm']);
    Route::post('setSchoolTerm', [ApiController::class, 'setSchTrm']);
    Route::post('admitStudent', [ApiController::class, 'admitStudent']);
    Route::post('setAppFeePaid', [ApiController::class, 'setAppFeePaid']);
    Route::post('setStudentBasicInfo', [ApiController::class, 'setStudentBasicInfo']);
    Route::post('setStudentMedicalInfo', [ApiController::class, 'setStudentMedicalInfo']);
    Route::post('setStudentParentInfo', [ApiController::class, 'setStudentParentInfo']);
    Route::post('setStudentAcademicInfo', [ApiController::class, 'setStudentAcademicInfo']);
    Route::post('setStaffBasicInfo', [ApiController::class, 'setStaffBasicInfo']);
    Route::post('setStaffProfInfo', [ApiController::class, 'setStaffProfInfo']);
    Route::post('setSchoolGradeInfo', [ApiController::class, 'setSchoolGradeInfo']);
    Route::post('setAcctPref', [ApiController::class, 'setAcctPref']);
    Route::post('setAccount', [ApiController::class, 'setAcct']);
    Route::post('setAFee', [ApiController::class, 'setAFee']);
    Route::post('setPayHead', [ApiController::class, 'setPayHead']);
    Route::post('setClassPay', [ApiController::class, 'setClassPay']);
    Route::post('setPaymentInstruction', [ApiController::class, 'setPaymentInstruction']);
    Route::post('setSchoolAppFee', [ApiController::class, 'setSchoolAppFee']);
    Route::post('setStudentAtOnce', [ApiController::class, 'setStudentAtOnce']);
    Route::post('setVendor', [ApiController::class, 'setVendor']);
    Route::post('setExpense', [ApiController::class, 'setExpense']);
    Route::post('setExternalExpenditure', [ApiController::class, 'setExternalExpenditure']);
    Route::post('setInternalExpenditure', [ApiController::class, 'setInternalExpenditure']);
    Route::post('setSchoolLatestNews', [ApiController::class, 'setSchoolLatestNews']);
    Route::post('setOldStudentInfo', [ApiController::class, 'setOldStudentInfo']);
    Route::post('admitStaff', [ApiController::class, 'admitStaff']);
    Route::post('setStaffSubject', [ApiController::class, 'setStaffSubject']);
    Route::post('setStaffClass', [ApiController::class, 'setStaffClass']);
    Route::post('setStaffClassArm', [ApiController::class, 'setStaffClassArm']);
    Route::post('setOldStaffInfo', [ApiController::class, 'setOldStaffInfo']);
    Route::post('setClassGrade', [ApiController::class, 'setClassGrade']);
    Route::post('setClassMark', [ApiController::class, 'setClassMark']);
    Route::post('setStudentScore', [ApiController::class, 'setStudentScore']);
    Route::post('setArmResultConf', [ApiController::class, 'setArmResultConf']);
    Route::post('setStudentPsy', [ApiController::class, 'setStudentPsy']);
    Route::post('setStudentRes', [ApiController::class, 'setStudentRes']);
    Route::post('setResultMeta', [ApiController::class, 'setResultMeta']);
    Route::post('setStudentSubjPos', [ApiController::class, 'setStudentSubjPos']);
    Route::post('initializePayment', [ApiController::class, 'initializePayment']);
    Route::post('setAcceptanceAcct', [ApiController::class, 'setAcceptanceAcct']);
    Route::post('setApplicationAcct', [ApiController::class, 'setApplicationAcct']);
    Route::post('setChangePassword', [ApiController::class, 'setChangePassword']);
    Route::post('exitStudent/{schid}/{stid}', [ApiController::class, 'exitStudent']);
    Route::post('exitStaff/{schid}/{stid}', [ApiController::class, 'exitStaff']);
    Route::post('restoreStudent/{schid}/{stid}', [ApiController::class, 'restoreStudent']);
    Route::post('restoreStaff/{schid}/{stid}', [ApiController::class, 'restoreStaff']);

    Route::post('setAttendanceMark', [ApiController::class, 'setAttendanceMark']);
    Route::post('submitAttendance', [ApiController::class, 'submitAttendance']);
    Route::post('setCurriculum', [ApiController::class, 'setCurriculum']);
    Route::put('updateCurriculum', [ApiController::class, 'updateCurriculum']);
    Route::post('setLessonPlan', [ApiController::class, 'setLessonPlan']);
    Route::put('updateLessonPlan', [ApiController::class, 'updateLessonPlan']);
    Route::post('setLessonNote', [ApiController::class, 'setLessonNote']);
    Route::put('updateLessonNote', [ApiController::class, 'updateLessonNote']);
    Route::post('/storeComment', [ApiController::class, 'storeComment']);
    Route::post('/autoCommentTemplate', [ApiController::class, 'autoCommentTemplate']);
    Route::post('/allStudentResultsComment', [ApiController::class, 'allStudentResultsComment']);





    Route::get('/getSubAccount/{acctid}', [ApiController::class, 'getSubAccount']);
    Route::get('getSchoolBasicInfo/{uid}', [ApiController::class, 'getSchoolBasicInfo']);
    Route::get('getSchoolGeneralInfo/{uid}', [ApiController::class, 'getSchoolGeneralInfo']);
    Route::get('getSchoolPropInfo/{uid}', [ApiController::class, 'getSchoolPropInfo']);
    Route::get('getPartnerByCode/{pcd}', [ApiController::class, 'getPartnerByCode']);
    Route::get('getPartnerBasicInfo/{uid}', [ApiController::class, 'getPartnerBasicInfo']);
    Route::get('getPartnerGeneralInfo/{uid}', [ApiController::class, 'getPartnerGeneralInfo']);
    Route::get('getPartnerFinancialInfo/{uid}', [ApiController::class, 'getPartnerFinancialInfo']);
    Route::get('getAnnouncements', [ApiController::class, 'getAnnouncements']);
    Route::get('getPaysByReceiver/{rid}', [ApiController::class, 'getPaysByReceiver']);
    Route::get('getPaysBySender/{sid}', [ApiController::class, 'getPaysBySender']);
    Route::get('getPaysBySenderAndReceiver/{sid}/{rid}', [ApiController::class, 'getPaysBySenderAndReceiver']);
    Route::get('getClasses', [ApiController::class, 'getClss']);
    Route::get('getClassesStat', [ApiController::class, 'getClssStat']);
    Route::get('getPaymentStat/{schid}/{clsid}/{ssnid}/{trmid}', [ApiController::class, 'getPaymentStat']);
    Route::get('getPayments/{schid}/{clsid}/{ssnid}/{trmid}', [ApiController::class, 'getPayments']);
    Route::get('confirmPayment/{schid}/{clsid}/{ssnid}/{trmid}/{stid}', [ApiController::class, 'confirmPayment']);
    Route::get('getStudentPayments/{stid}', [ApiController::class, 'getStudentPayments']);
    Route::get('getStudentPaymentStat/{stid}', [ApiController::class, 'getStudentPaymentStat']);
    Route::get('getAcceptancePaymentStat/{schid}/{clsid}', [ApiController::class, 'getAcceptancePaymentStat']);
    Route::get('getAcceptancePayments/{schid}/{clsid}', [ApiController::class, 'getAcceptancePayments']);
    Route::get('confirmAcceptancePayment/{schid}/{clsid}/{stid}', [ApiController::class, 'confirmAcceptancePayment']);
    Route::get('getRegFeePaymentStat/{schid}/{rfee}', [ApiController::class, 'getRegFeePaymentStat']);
    Route::get('getRegFeePayments/{schid}/{rfee}', [ApiController::class, 'getRegFeePayments']);
    Route::get('getAcctPref/{schid}', [ApiController::class, 'getAcctPref']);
    Route::get('getAccountStat/{schid}', [ApiController::class, 'getAccountStat']);
    Route::get('getAccountsBySchool/{schid}', [ApiController::class, 'getAccountsBySchool']);
    Route::get('getAccountsBySchoolAndClass/{schid}/{clsid}', [ApiController::class, 'getAccountsBySchoolAndClass']);
    Route::get('deleteAccount/{schid}/{clsid}', [ApiController::class, 'deleteAcct']);
    Route::get('getAFeeStat/{schid}', [ApiController::class, 'getAFeeStat']);
    Route::get('getAFeeBySchool/{schid}', [ApiController::class, 'getAFeeBySchool']);
    Route::get('getAFee/{schid}/{clsid}', [ApiController::class, 'getAFee']);
    Route::get('deleteAFee/{schid}/{clsid}', [ApiController::class, 'deleteAFee']);
    Route::get('getPayHeadStat/{schid}', [ApiController::class, 'getPayHeadStat']);
    Route::get('getPayHeadsBySchool/{schid}', [ApiController::class, 'getPayHeadsBySchool']);
    Route::get('deletePayHead/{uid}', [ApiController::class, 'deletePayHead']);
    Route::get('getClassPays/{schid}/{clsid}/{sesid}/{trmid}', [ApiController::class, 'getClassPays']);
    Route::get('deleteClassPay/{uid}', [ApiController::class, 'deleteClassPay']);
    Route::get('getSchoolArms/{schid}', [ApiController::class, 'getSchoolArms']);
    Route::get('getSchoolClassArms/{schid}/{clsid}', [ApiController::class, 'getSchoolClassArms']);
    Route::get('getStaffByClassArmAndSubject/{schid}/{clsid}/{sbid}', [ApiController::class, 'getStaffByClassArmAndSubject']);
    Route::get('getSchoolArmsStat/{schid}/{clsid}', [ApiController::class, 'getSchoolArmsStat']);
    Route::get('getSchoolClasses/{schid}', [ApiController::class, 'getSchoolClasses']);
    Route::get('getSubjectsStat', [ApiController::class, 'getSubjsStat']);
    Route::get('getSubject/{sbid}', [ApiController::class, 'getSubj']);
    Route::get('getTerm/{trmid}', [ApiController::class, 'getTrm']);
    Route::get('getStudentsByStatus/{stat}/{schid}', [ApiController::class, 'getStudentsByStatus']);
    Route::get('getStudentBasicInfo/{uid}', [ApiController::class, 'getStudentBasicInfo']);
    Route::get('getStudentMedicalInfo/{uid}', [ApiController::class, 'getStudentMedicalInfo']);
    Route::get('getStudentParentInfo/{uid}', [ApiController::class, 'getStudentParentInfo']);
    Route::get('getStudentAcademicInfo/{uid}', [ApiController::class, 'getStudentAcademicInfo']);
    Route::get('getStaffBasicInfo/{uid}', [ApiController::class, 'getStaffBasicInfo']);
    Route::get('getStaffProfInfo/{uid}', [ApiController::class, 'getStaffProfInfo']);
    Route::get('getSchoolGradeInfo/{uid}', [ApiController::class, 'getSchoolGradeInfo']);
    Route::get('getStudentSubjects/{stid}', [ApiController::class, 'getStudentSubjects']);
    Route::delete('deleteStudentSubject/{uid}/{sbj}', [ApiController::class, 'deleteStudentSubject']);
    Route::delete('deleteStudentSubject/{uid}/{sbj}/{term}', [ApiController::class, 'deleteStudentSubject']);
    Route::get('getStaffSubjects/{stid}', [ApiController::class, 'getStaffSubjects']);
    Route::get('deleteStaffSubject/{uid}', [ApiController::class, 'deleteStaffSubject']);
    Route::get('getStaffClasses/{stid}', [ApiController::class, 'getStaffClasses']);
    Route::get('getStaffClassArms/{stid}/{cls}', [ApiController::class, 'getStaffClassArms']);
    Route::get('getStaffByClassArms/{schid}/{arm}', [ApiController::class, 'getStaffByClassArms']);
    Route::get('deleteStaffClass/{uid}', [ApiController::class, 'deleteStaffClass']);
    Route::get('deleteStaffClassArm/{uid}', [ApiController::class, 'deleteStaffClassArm']);
    Route::get('deleteClassSubject/{uid}', [ApiController::class, 'deleteClassSubject']);
    Route::get('getSchoolAppFee/{uid}', [ApiController::class, 'getSchoolAppFee']);
    Route::get('searchStudents', [ApiController::class, 'searchStudents']);
    Route::get('searchOldStudents', [ApiController::class, 'searchOldStudents']);
    Route::get('searchStaff', [ApiController::class, 'searchStaff']);
    Route::get('searchAdminClasses', [ApiController::class, 'searchAdminClasses']);
    Route::get('searchAdminSubjects', [ApiController::class, 'searchAdminSubjects']);
    Route::get('searchAdminStaffRole', [ApiController::class, 'searchAdminStaffRole']);
    Route::get('getSchoolHighlights/{schid}/{ssnid}/{trmid}', [ApiController::class, 'getSchoolHighlights']);
    Route::get('getVendorStat/{schid}', [ApiController::class, 'getVendorStat']);
    Route::get('getVendorsBySchool/{schid}', [ApiController::class, 'getVendorsBySchool']);
    Route::get('deleteVendor/{vid}', [ApiController::class, 'deleteVendor']);
    Route::get('getVendor/{vid}', [ApiController::class, 'getVendor']);
    Route::get('getExpenseStat/{schid}', [ApiController::class, 'getExpenseStat']);
    Route::get('getExpensesBySchool/{schid}', [ApiController::class, 'getExpensesBySchool']);
    Route::get('deleteExpense/{eid}', [ApiController::class, 'deleteExpense']);
    Route::get('getExpense/{eid}', [ApiController::class, 'getExpense']);
    Route::get('getExternalExpenditureStat/{schid}/{ssn}/{trm}', [ApiController::class, 'getExternalExpenditureStat']);
    Route::get('getExternalExpenditures/{schid}/{ssn}/{trm}', [ApiController::class, 'getExternalExpenditures']);
    Route::get('getExternalExpendituresByFilter/{schid}/{ssn}/{trm}', [ApiController::class, 'getExternalExpendituresByFilter']);
    Route::get('deleteExternalExpenditure/{eid}', [ApiController::class, 'deleteExternalExpenditure']);
    Route::get('getExternalExpenditure/{eid}', [ApiController::class, 'getExternalExpenditure']);
    Route::get('getInternalExpenditureStat/{schid}/{ssn}/{trm}', [ApiController::class, 'getInternalExpenditureStat']);
    Route::get('getInternalExpendituresByFilter/{schid}/{ssn}/{trm}', [ApiController::class, 'getInternalExpendituresByFilter']);
    Route::get('getInternalExpenditures/{schid}/{ssn}/{trm}', [ApiController::class, 'getInternalExpenditures']);
    Route::get('deleteInternalExpenditure/{eid}', [ApiController::class, 'deleteInternalExpenditure']);
    Route::get('getInternalExpenditure/{eid}', [ApiController::class, 'getInternalExpenditure']);
    Route::get('getOldStudentInfo/{uid}', [ApiController::class, 'getOldStudentInfo']);
    Route::get('getOldStudentsStat/{schid}/{ssn}/{trm}/{clsm}/{clsa}', [ApiController::class, 'getOldStudentsStat']);
    Route::get('getOldStudent/{schid}/{ssn}/{stid}', [ApiController::class, 'getOldStudent']);
    Route::get('getOldStudents/{schid}/{ssn}/{trm}/{clsm}/{clsa}', [ApiController::class, 'getOldStudents']);
    Route::get('getOldStaffInfo/{uid}', [ApiController::class, 'getOldStaffInfo']);
    Route::get('getOldStaffStat/{schid}/{ssn}/{clsm}/{role}', [ApiController::class, 'getOldStaffStat']);
    Route::get('getOldStaff/{schid}/{ssn}/{clsm}/{role}', [ApiController::class, 'getOldStaff']);
    Route::get('getStaffRoleByClass/{stid}/{schid}/{clsid}/{ssn}', [ApiController::class, 'getStaffRoleByClass']);
    Route::get('getOldStudentsAndSubject/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}', [ApiController::class, 'getOldStudentsAndSubject']);
    Route::get('getOldStudentsAndSubjectScoreSheet/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}', [ApiController::class, 'getOldStudentsAndSubjectScoreSheet']);
    Route::get('getOldStudentsAndSubjectHistory/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}', [ApiController::class, 'getOldStudentsAndSubjectHistory']);
    Route::get('getOldStudentsAndSubjectWithoutScore/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}', [ApiController::class, 'getOldStudentsAndSubjectWithoutScore']);
    Route::get('getClassArmsByStaffClass/{stid}/{cls}', [ApiController::class, 'getClassArmsByStaffClass']);
    Route::get('studentHasExamRecord/{schid}/{clsid}/{ssn}/{trm}/{stid}', [ApiController::class, 'studentHasExamRecord']);
    Route::get('getArmResultConf/{schid}/{clsid}/{sbid}/{ssn}/{trm}/{arm}', [ApiController::class, 'getArmResultConf']);
    Route::get('getAcctApp/{schid}', [ApiController::class, 'getAcctApp']);
    Route::get('getAcctAccept/{schid}', [ApiController::class, 'getAcctAccept']);
    Route::get('getAlumni/{schid}', [ApiController::class, 'getAlumni']);
    Route::get('getExStaff/{schid}', [ApiController::class, 'getExStaff']);
    Route::get('getPaymentInstruction/{schid}/{clsid}/{sesid}/{trmid}', [ApiController::class, 'getPaymentInstruction']);
    Route::delete('deletePaymentInstruction/{schid}/{clsid}/{sesid}/{trmid}', [ApiController::class, 'deletePaymentInstruction']);

    Route::get('getAttendance/{week}/{schid}/{trm}/{ssn}/{clsm}/{clsa}', [ApiController::class, 'getAttendance']);
    Route::get('getAttendanceByWeek/{week}/{schid}', [ApiController::class, 'getAttendanceByWeek']);

    Route::get('calculateAttendanceForClass/{schid}/{ssn}/{clsm}/{clsa}', [ApiController::class, 'calculateAttendanceForClass']);
    Route::get('getFilteredAttendanceSummary/{schid}/{ssn}/{trm}/{clsm}/{clsa}', [ApiController::class, 'getFilteredAttendanceSummary']);

    Route::get('getCurriculum/{schid}/{ssn}/{trm}/{clsm}', [ApiController::class, 'getCurriculum']);
    Route::get('getcurriculumByStudent/{schid}/{ssn}/{trm}/{clsm}/{sbj}/{sid}', [ApiController::class, 'getcurriculumByStudent']);
    Route::get('getCurriculumBySubject/{schid}/{ssn}/{trm}/{clsm}/{sbj}', [ApiController::class, 'getCurriculumBySubject']);

    Route::get('getLessonPlan/{schid}/{ssn}/{trm}/{clsm}', [ApiController::class, 'getLessonPlan']);
    Route::get('getLessonPlanBySubject/{schid}/{ssn}/{trm}/{clsm}/{sbj}', [ApiController::class, 'getLessonPlanBySubject']);

    Route::get('getAllSubjectPositions/{schid}/{ssn}/{trm}/{clsm}/{clsa}', [ApiController::class, 'getAllSubjectPositions']);
    Route::get('getStudentSubjectPositions/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}', [ApiController::class, 'getStudentSubjectPositions']);

    Route::get('getSingleLessonPlan/{schid}/{ssn}/{trm}/{clsm}/{sbj}/{id}', [ApiController::class, 'getSingleLessonPlan']);

    Route::get('getLessonNote/{sch_id}/{session}/{term}/{class}/{week}', [ApiController::class, 'getLessonNote']);
    Route::get('getSingleLessonNote/{sch_id}/{session}/{term}/{class}/{week}/{lessonNoteId}', [ApiController::class, 'getSingleLessonNote']);
    Route::get('getLessonNoteBySubject/{sch_id}/{session}/{term}/{class}/{subject}/{week}', [ApiController::class, 'getLessonNoteBySubject']);

    Route::get('getOverallBestStudents/{schid}/{ssn}/{trm}/{clsm}', [ApiController::class, 'getOverallBestStudents']);
    Route::get('getBestStudentsInSubject/{schid}/{ssn}/{trm}/{clsm}/{sbj}', [ApiController::class, 'getBestStudentsInSubject']);
    Route::get('getAllSubjectsPerformance/{schid}/{ssn}/{trm}/{clsm}', [ApiController::class, 'getAllSubjectsPerformance']);

    Route::get('/getStudentScoreSummary', [ApiController::class, 'getStudentScoreSummary']);
    Route::get('/filterCommentByRole', [ApiController::class, 'filterCommentByRole']);

    Route::get('getSubjectsByClass/{classId}', [ApiController::class, 'getSubjectsByClass']);







    //--ADMIN
    Route::post('setAdmin', [ApiController::class, 'setAdmin']);
    Route::post('setAnnouncements', [ApiController::class, 'setAnnouncements']);
    Route::post('sendMail', [ApiController::class, 'sendMail']);
    Route::post('resetDefaultPassword', [ApiController::class, 'resetDefaultPassword']);

    Route::get('getSchoolsByPartner/{pid}', [ApiController::class, 'getSchoolsByPartner']);
    Route::get('searchSchools', [ApiController::class, 'searchSchools']);
    Route::get('searchPartners', [ApiController::class, 'searchPartners']);
    Route::get('getSchoolsByPay/{pid}', [ApiController::class, 'getSchoolsByPay']);
    Route::get('getSchoolsByStat/{stat}', [ApiController::class, 'getSchoolsByStat']);
    Route::get('getSchools', [ApiController::class, 'getSchools']);
    Route::get('getSchoolsStat', [ApiController::class, 'getSchoolsStat']);
    Route::get('getStudentsBySchool/{schid}/{stat}', [ApiController::class, 'getStudentsBySchool']);
    Route::get('getStudentsStatBySchool', [ApiController::class, 'getStudentsStatBySchool']);

    Route::get('getStaffBySchool/{schid}/{stat}/{cls?}', [ApiController::class, 'getStaffBySchool']);
    Route::get('getStaffStatBySchool/{schid}/{stat}/{cls?}', [ApiController::class, 'getStaffStatBySchool']);
    Route::get('getStudents', [ApiController::class, 'getStudents']);
    Route::get('getStaff', [ApiController::class, 'getStaff']);

    //--PAYMENT
    Route::post('setPayRecord', [ApiController::class, 'setPayRecord']);
    Route::post('setAFeeRecord', [ApiController::class, 'setAFeeRecord']);


    Route::get('getPaysByReceiver/{rid}', [ApiController::class, 'getPaysByReceiver']);
    Route::get('getPaysBySender/{sid}', [ApiController::class, 'getPaysBySender']);
    Route::get('getPaysBySenderAndReceiver/{sid}/{rid}', [ApiController::class, 'getPaysBySenderAndReceiver']);
    Route::get('resolveAccountNumber/{anum}/{bnk}', [ApiController::class, 'resolveAccountNumber']);


    //--MSG
    Route::post('createMsgThread', [ApiController::class, 'createMsgThread']);
    Route::post('sendMsg', [ApiController::class, 'sendMsg']);

    Route::get('searchMsgThread', [ApiController::class, 'searchMsgThread']);
    Route::get('getMyMessagesStat/{uid}', [ApiController::class, 'getMyMessagesStat']);
    Route::get('getMyMessages/{uid}', [ApiController::class, 'getMyMessages']);
    Route::get('getMessageThread/{tid}', [ApiController::class, 'getMessageThread']);

    //--FILE
    Route::post('uploadFile', [ApiController::class, 'uploadFile']);

    Route::get('getFiles/{uid}', [ApiController::class, 'getFiles']);
    Route::get('fileExists/{folder}/{filename}', [ApiController::class, 'fileExists']);



    //--GENERAL

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('checkTokenValidity', [ApiController::class, 'checkTokenValidity']);
});
