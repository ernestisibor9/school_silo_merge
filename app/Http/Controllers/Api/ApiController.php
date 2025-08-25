<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Mail\SSSMails;
use App\Models\announcements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use App\Models\password_reset_tokens;
use App\Models\school;
use App\Models\User;
use App\Models\files;
use App\Models\msg;
use App\Models\msgthread;
use App\Models\partner_basic_data;
use App\Models\partner_financial_data;
use App\Models\partner_general_data;
use App\Models\school_basic_data;
use App\Models\school_web_data;
use App\Models\school_general_data;
use App\Models\school_prop_data;
use App\Models\school_app_fee;
use App\Models\silo_user;
use App\Models\pay;
use App\Models\payhead;
use App\Models\clspay;
use App\Models\auto_comment_template;
use App\Models\accts;
use App\Models\afee;
use App\Models\afeerec;
use App\Models\acct_pref;
use App\Models\payments;
use App\Models\attendance;
use App\Models\sub_account;
use App\Models\cls;
use App\Models\staff_role;
use App\Models\sch_staff_role;
use App\Models\class_subj;
use App\Models\sch_grade;
use App\Models\sch_cls;
use App\Models\school_class;
use App\Models\sch_mark;
use App\Models\std_score;
use App\Models\arm_result_conf;
use App\Models\subj;
use App\Models\subaccount;
use App\Models\payment_instruction;
use App\Models\student_subj;
use App\Models\result_meta;
use App\Models\staff_subj;
use App\Models\staff_class;
use App\Models\staff_class_arm;
use App\Models\sesn;
use App\Models\trm;
use App\Models\student;
use App\Models\old_student;
use App\Models\student_psy;
use App\Models\student_res;
use App\Models\student_sub_res;
use App\Models\old_staff;
use App\Models\student_basic_data;
use App\Models\student_medical_data;
use App\Models\student_parent_data;
use App\Models\student_academic_data;
use App\Models\staff;
use App\Models\alumni;
use App\Models\ex_staff;
use App\Models\curriculum;
use App\Models\lesson_plan;
use App\Models\lesson_note;
use App\Models\topic;
use App\Models\sub_topic;
use App\Models\cummulative_comment;

use App\Models\staff_basic_data;
use App\Models\staff_prof_data;
use App\Models\school_grade_data;
use App\Models\payment_refs;
use App\Models\acceptance_acct;
use App\Models\acceptance_sub_acct;
use App\Models\application_acct;
use App\Models\application_sub_acct;
use App\Models\vendor;
use App\Models\expense;
use App\Models\ext_expenditure;
use App\Models\in_expenditure;
use App\Models\school_news_data;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



/**
 * @OA\Info(
 *    title="SCHOOLSILO API | Stable Shield Solutions",
 *    version="1.0.0",
 *    description="Backend for the SCHOOLSILO project. Powered by Stable Shield Solutions",
 *    @OA\Contact(
 *        email="support@stableshield.com",
 *        name="API Support"
 *    ),
 *    @OA\License(
 *        name="Stable Shield API License",
 *        url="http://stableshield.com/api-licenses"
 *    )
 * )
 */


class ApiController extends Controller
{

    //--Schools

    /**
     * @OA\Post(
     *     path="/api/registerSchool",
     *     tags={"Unprotected"},
     *     summary="Register a new school",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sbd", type="string", description="Sub-Domain ID"),
     *             @OA\Property(property="sch3", type="string", description="3 acrn for the school"),
     *             @OA\Property(property="password", type="string", description="The password for the school"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */

    public function registerSchool(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email|unique:users,email",
            "password" => "required",
            "name" => "required",
            "sbd" => "required",
            "sch3" => "required",
        ]);
        if (strlen($request->password) < 6) {
            return response()->json([
                "status" => false,
                "message" => "Password must be at least 6 char",
            ], 400);
        }
        $typ = 's';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if (!$usr) {
            $usr = User::create([
                "email" => $request->email,
                "typ" => $typ,
                "verif" => '0',
                "password" => bcrypt($request->password),
            ]);
            $count = school::count() + 1;
            school::create([
                "sid" => strval($usr->id),
                "name" => $request->name,
                "sbd" => $request->sbd,
                "sch3" => $request->sch3,
                "count" => strval($count),
                "s_web" => '0',
                "s_info" => '0',

                "cssn" => '0',
                "ctrm" => '0',
                "ctrmn" => '0',
            ]);
            $code = Str::random(6);
            $eml = $request->email;
            password_reset_tokens::updateOrCreate(
                ['email' => $eml],
                ['token' => $code]
            );
            $schid = strval($usr->id);
            $lnk = env('API_URL') . '/api/verifyEmail/' . $typ . '/' . $code . '/' . $schid;
            // Wrap the email sending logic in a try-catch block
            try {
                $data = [
                    'name' => $request->name,
                    'subject' => 'Verify Your Email',
                    'body' => 'Please use the link below to verify your email. Welcome to School Silo. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. ' . $lnk,
                    'link' => $lnk,
                ];
                Mail::to($eml)->send(new SSSMails($data));
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }


            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            return response()->json([
                "status" => true,
                "message" => "User created successfully",
                "token" => $token,
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Account already exists",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/schoolLogin",
     *     tags={"Unprotected"},
     *     summary="School Login to the application. NOTE:: You get both pld and sch (with sch containing more info)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="your-jwt-token-here", description="This will contain a JWT token that must be passed with consequent request using bearer token"),
     *         )
     *     ),
     * )
     */
    public function schoolLogin(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
        $typ = 's';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if ($usr) {
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                return response()->json([
                    "status" => true,
                    "message" => "Login successful",
                    "token" => $token,
                    "pld" => $usr,
                ]);
            }
        }
        // Respond
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchool/{uid}",
     *     tags={"Unprotected"},
     *     summary="Get a particular School by their ID",
     *     description="Use this endpoint to Get a particular School by their ID",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchool($uid)
    {
        $sch = school::where('sid', $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $sch,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolBySBD/{sbd}",
     *     tags={"Unprotected"},
     *     summary="Get a particular School by their Subdomain ID",
     *     description="Use this endpoint to Get a particular School by their Subdomain ID",
     *     @OA\Parameter(
     *         name="sbd",
     *         in="path",
     *         required=true,
     *         description="SubDomain ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolBySBD($sbd)
    {
        $sch = school::where('sbd', $sbd)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $sch,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/sendEmailVerificationLink",
     *     tags={"Api"},
     *     summary="Send a verification code to user email. Only call after user has logged in",
     *     description="Use this endpoint to verify user email. Only call after user has logged in",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="typ", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function sendEmailVerificationLink(Request $request)
    {
        $request->validate([
            "schid" => "required",
            "email" => "required",
            "typ" => "required",
        ]);
        $eml = $request->email;
        $typ = $request->typ;
        $code = Str::random(6);
        password_reset_tokens::updateOrCreate(
            ['email' => $eml],
            ['token' => $code]
        );
        $lnk = env('API_URL') . '/api/verifyEmail/' . $typ . '/' . $code . '/' . $request->schid;
        // Wrap the email sending logic in a try-catch block
        try {
            $data = [
                'name' => 'SCHOOL-SILO USER',
                'subject' => 'Verify Your Email',
                'body' => 'Please use the link below to verify your email. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. ' . $lnk,
                'link' => $lnk,
            ];

            Mail::to($eml)->send(new SSSMails($data));
        } catch (\Exception $e) {
            // Log the email error, but don't stop the process
            Log::error('Failed to send email: ' . $e->getMessage());
        }


        return response()->json([
            "status" => true,
            "message" => "Link sent to mail",
        ]);
        return response()->json([
            "status" => false,
            "message" => "Must Login User First",
        ], 400);
    }

    /**
     * @OA\Get(
     *     path="/api/verifyEmail/{typ}/{code}/{schid}",
     *     tags={"Unprotected"},
     *     summary="Verify email",
     *     description="Verify email. Usually called by user from their mail",
     *     @OA\Parameter(
     *         name="typ",
     *         in="path",
     *         required=true,
     *         description="Type of user",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="The Code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */
    public function verifyEmail($typ, $code, $schid)
    {
        $pld = password_reset_tokens::where("token", $code)->first();
        if ($pld) {
            $eml = $pld->email;
            $usr = User::where("email", $eml)->where('typ', $typ)->first();
            if ($usr) {
                $usr->update([
                    "verif" => '1',
                ]);
                $pld->delete();
                if ($typ == 'a') { //An Admin
                    return redirect()->away(env('PORTAL_URL') . '/adminLogin');
                }
                if ($typ == 'p') { //A Partner
                    return redirect()->away(env('PORTAL_URL') . '/partnerLogin');
                }
                if ($typ == 's') { //A School
                    return redirect()->away(env('PORTAL_URL') . '/schoolLogin');
                }
                if ($typ == 'z') { //A Student
                    return redirect()->away(env('PORTAL_URL') . '/studentLogin' . '/' . $schid);
                }
                if ($typ == 'w') { //A Staff
                    return redirect()->away(env('PORTAL_URL') . '/staffLogin' . '/' . $schid);
                }
                return response()->json([
                    "status" => true,
                    "message" => "Success. Please login again"
                ]);
            }
            return response()->json([
                "status" => false,
                "message" => "User not found",
            ], 400);
        }
        return response()->json([
            "status" => false,
            "message" => "Invalid Code. Please Try Login Again",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/sendPasswordResetEmail",
     *     tags={"Unprotected"},
     *     summary="Send reset email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="typ", type="string", description="For school, pass s"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */
    public function sendPasswordResetEmail(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required",
            "typ" => "required",
            "schid" => "required",
        ]);
        $typ = $request->typ;
        $schid = $request->schid;
        $usr = User::where("email", $request->email)->where('typ', $typ)->first();
        if ($usr) {
            $eml = $usr->email;
            $token = Str::random(60); //Random reset token
            password_reset_tokens::updateOrCreate(
                ['email' => $eml],
                ['token' => $token]
            );
            // Wrap the email sending logic in a try-catch block
            try {
                $data = [
                    'name' => 'SCHOOLSILO USER',
                    'subject' => 'Reset your SCHOOLSILO password',
                    'body' => 'Please go to this link to reset your password. It will expire in 1 hour. . If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam.',
                    'link' => env('PORTAL_URL') . '/resetPassword' . '/' . $request->typ . '/' . $token . '/' . $schid,
                ];

                Mail::to($eml)->send(new SSSMails($data));
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }


            return response()->json([
                "status" => true,
                "message" => "Password reset link sent to mail",
            ]);
        }


        // Respond
        return response()->json([
            "status" => false,
            "message" => "User not found",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/resetPassword",
     *     tags={"Unprotected"},
     *     summary="change user password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="typ", type="string", description="For school, pass s"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */
    public function resetPassword(Request $request)
    {
        //Data validation
        $request->validate([
            "token" => "required",
            "password" => "required",
            "typ" => "required",
        ]);
        $pld = password_reset_tokens::where("token", "=", $request->token)->first();
        if ($pld) {
            $email = $pld->email;
            $usr = User::where("email", $email)->where("typ", $request->typ)->first();
            if ($usr) {
                $usr->update([
                    "password" => bcrypt($request->password),
                ]);
                $pld->delete();
                return response()->json([
                    "status" => true,
                    "message" => "Success. Please login again"
                ]);
            }
            return response()->json([
                "status" => false,
                "message" => "User not found",
            ], 400);
        }
        return response()->json([
            "status" => false,
            "message" => "Denied. Invalid/Expired Token",
        ], 400);
    }



    /**
     * @OA\Post(
     *     path="/api/setSchoolWebData",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set school website data",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="sname", type="string"),
     *             @OA\Property(property="color", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="lga", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="eml", type="string"),
     *             @OA\Property(property="vision", type="string"),
     *             @OA\Property(property="values", type="string"),
     *             @OA\Property(property="year", type="string"),
     *             @OA\Property(property="about", type="string"),
     *             @OA\Property(property="motto", type="string"),
     *             @OA\Property(property="fb", type="string"),
     *             @OA\Property(property="isg", type="string"),
     *             @OA\Property(property="yt", type="string"),
     *             @OA\Property(property="wh", type="string"),
     *             @OA\Property(property="lkd", type="string"),
     *             @OA\Property(property="tw", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="School data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setSchoolWebData(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "sname" => "required",
            'color' => 'required',
            'addr' => 'required',
            'country' => 'required',
            'state' => 'required',
            'lga' => 'required',
            'phn' => 'required',
            'eml' => 'required',
            'vision' => 'required',
            'values' => 'required',
            'year' => 'required',
            'about' => 'required',
            'motto' => 'required',
            'fb' => 'required',
            'isg' => 'required',
            'yt' => 'required',
            'wh' => 'required',
            'lkd' => 'required',
            'tw' => 'required',
        ]);
        school_web_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "sname" => $request->sname,
                "color" => $request->color,
                'addr' => $request->addr,
                'country' => $request->country,
                'state' => $request->state,
                'lga' => $request->lga,
                'phn' => $request->phn,
                'eml' => $request->eml,
                'vision' => $request->vision,
                'values' => $request->values,
                'year' => $request->year,
                'about' => $request->about,
                'motto' => $request->motto,
                'fb' => $request->fb,
                'isg' => $request->isg,
                'yt' => $request->yt,
                'wh' => $request->wh,
                'lkd' => $request->lkd,
                'tw' => $request->tw,
            ]
        );
        school::where('sid', $request->user_id)->update([
            "s_web" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolWebInfo/{uid}",
     *     tags={"Unprotected"},
     *     summary="Get School Website Info",
     *     description="Use this endpoint to get website information about a school.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the School",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolWebInfo($uid)
    {
        $pld = school_web_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolWebInfoBySBD/{sbd}",
     *     tags={"Unprotected"},
     *     summary="Get School Website Info using the subdomain ID",
     *     description="Use this endpoint to get website information about a school by SBD.",
     *     @OA\Parameter(
     *         name="sbd",
     *         in="path",
     *         required=true,
     *         description="Subdomain Id of the School",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolWebInfoBySBD($sbd)
    {
        $sch = school::where('sbd', $sbd)->first();
        $pld = null;
        if ($sch) {
            $pld = school_web_data::where("user_id", $sch->sid)->first();
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
            "sch" => $sch,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSchool",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set school data",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="sname", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="eml", type="string", format="email"),
     *             @OA\Property(property="pcode", type="string"),
     *             @OA\Property(property="pay", type="string", description="Has paid reg. fee? pass 0 or 1"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="lga", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="vision", type="string"),
     *             @OA\Property(property="mission", type="string"),
     *             @OA\Property(property="values", type="string"),
     *             @OA\Property(property="pfname", type="string"),
     *             @OA\Property(property="pmname", type="string"),
     *             @OA\Property(property="plname", type="string"),
     *             @OA\Property(property="psex", type="string"),
     *             @OA\Property(property="pphn", type="string"),
     *             @OA\Property(property="paddr", type="string"),
     *             @OA\Property(property="peml", type="string", format="email"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="School data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setSchool(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "sname" => "required",
            "phn" => "required",
            "eml" => "required",
            "pcode" => "required",
            "pay" => "required",
            "state" => "required",
            "lga" => "required",
            "addr" => "required",
            "vision" => "required",
            "mission" => "required",
            "values" => "required",
            "pfname" => "required",
            "plname" => "required",
            "psex" => "required",
            "pphn" => "required",
            "paddr" => "required",
            "peml" => "required|email",
        ]);
        school_basic_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "sname" => $request->sname,
                "phn" => $request->phn,
                "eml" => $request->eml,
                "pcode" => $request->pcode,
                "pay" => $request->pay,
            ]
        );
        school_general_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "state" => $request->state,
                "lga" => $request->lga,
                "addr" => $request->addr,
                "vision" => $request->vision,
                "mission" => $request->mission,
                "values" => $request->values,
            ]
        );
        school_prop_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "pfname" => $request->pfname,
                "pmname" => $request->pmname,
                "plname" => $request->plname,
                "psex" => $request->psex,
                "pphn" => $request->pphn,
                "paddr" => $request->paddr,
                "peml" => $request->peml,
            ]
        );
        school::where('sid', $request->user_id)->update([
            "s_info" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getSchoolBasicInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get School Basic Info",
     *     description="Use this endpoint to get basic information about a school.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the School",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolBasicInfo($uid)
    {
        $pld = school_basic_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSchoolLatestNews",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set school news data",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="body", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="School data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setSchoolLatestNews(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "title" => "required",
            "body" => "required",
        ]);
        school_news_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "title" => $request->title,
                "body" => $request->body,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getSchoolLatestNews/{uid}",
     *     tags={"Unprotected"},
     *     summary="Get School Latest News Info",
     *     description="Use this endpoint to get Latest News information about a school.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the School",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolLatestNews($uid)
    {
        $pld = school_news_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolGeneralInfo/{uid}",
     *     tags={"Api"},
     *     summary="Get School General Info",
     *     description="Use this endpoint to get general information about a school.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolGeneralInfo($uid)
    {
        $pld = school_general_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSchoolAppFee",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set school application fee",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="sid", type="string"),
     *             @OA\Property(property="fee", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setSchoolAppFee(Request $request)
    {
        //Data validation
        $request->validate([
            "sid" => "required",
            "fee" => "required",
        ]);
        school_app_fee::updateOrCreate(
            ["sid" => $request->sid,],
            [
                "fee" => $request->fee,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolAppFee/{uid}",
     *     tags={"Api"},
     *     summary="Get School Application Fee",
     *     description="Use this endpoint to get school app. fee",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolAppFee($uid)
    {
        $pld = school_app_fee::where("sid", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolPropInfo/{uid}",
     *     tags={"Api"},
     *     summary="Get School Proprietor Info",
     *     description="Use this endpoint to get general information about a proprietor.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolPropInfo($uid)
    {
        $pld = school_prop_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/registerPartner",
     *     tags={"Unprotected"},
     *     summary="Register a partner",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="pcode", type="string",description="Partner Unique Code"),
     *             @OA\Property(property="verif", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Account created successfully"),
     * )
     */
    public function registerPartner(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required",
            "fname" => "required",
            "lname" => "required",
            "phn" => "required",
            "verif" => "required",
            "pcode" => "required|unique:partner_basic_data",
        ]);
        $typ = 'p';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if (!$usr) {
            $usr = User::create([
                "email" => $request->email,
                "typ" => $typ,
                "verif" => "0",
                "password" => bcrypt($request->password),
            ]);
            partner_basic_data::create([
                "user_id" => strval($usr->id),
                "fname" => $request->fname,
                "lname" => $request->lname,
                "mname" => $request->mname ?? '',
                "phn" => $request->phn,
                "eml" => $request->email,
                "verif" => $request->verif,
                "pcode" => $request->pcode,
            ]);
            // Respond
            return response()->json([
                "status" => true,
                "message" => "Account created successfully",
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Account already exists",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/partnerLogin",
     *     tags={"Unprotected"},
     *     summary="Partner Login to the application",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Partner Login Successfully"),
     * )
     */
    public function partnerLogin(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
        $typ = 'p';
        $usr = User::where("typ", $typ)->where('email', $request->email)->first();
        if ($usr) {
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                return response()->json([
                    "status" => true,
                    "message" => "Partner login successfully",
                    "token" => $token,
                    "pld" => $usr
                ]);
            }
        }
        // Respond
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/setPartner",
     *     tags={"Api"},
     *     summary="Set Partner Data",
     *     description="This endpoint is used to set information about a partner.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="eml", type="string", format="email"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="verif", type="string"),
     *             @OA\Property(property="pcode", type="string",description="Partner Unique Code"),
     *             @OA\Property(property="state", type="string", description="State"),
     *             @OA\Property(property="lga", type="string", description="Local Government Area"),
     *             @OA\Property(property="addr", type="string", description="Address"),
     *             @OA\Property(property="sex", type="string", description="Gender"),
     *             @OA\Property(property="bnk", type="string", description="Bank Code"),
     *             @OA\Property(property="anum", type="string", description="Account Number"),
     *             @OA\Property(property="aname", type="string", description="Acct Name"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setPartner(Request $request)
    {
        $request->validate([
            "user_id" => "required",
            "fname" => "required",
            "lname" => "required",
            "phn" => "required",
            "eml" => "required",
            "verif" => "required",
            "state" => "required",
            "lga" => "required",
            "addr" => "required",
            "sex" => "required",
            "bnk" => "required",
            "anum" => "required",
            "aname" => "required",
        ]);
        $dbd = partner_basic_data::where("user_id", $request->user_id)->first();
        if ($dbd) {
            $dbd->update([
                "fname" => $request->fname,
                "lname" => $request->lname,
                "mname" => $request->mname,
                "phn" => $request->phn,
                "eml" => $request->eml,
                "verif" => $request->verif,
            ]);
            partner_general_data::updateOrCreate(
                ["user_id" => $request->user_id,],
                [
                    "state" => $request->state,
                    "lga" => $request->lga,
                    "addr" => $request->addr,
                    "sex" => $request->sex,
                ]
            );
            partner_financial_data::updateOrCreate(
                ["user_id" => $request->user_id,],
                [
                    "bnk" => $request->bnk,
                    "anum" => $request->anum,
                    "aname" => $request->aname,
                ]
            );
            return response()->json([
                "status" => true,
                "message" => "Success"
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Partner not found"
        ], 400);
    }


    /**
     * @OA\Get(
     *     path="/api/getPartnerByCode/{pcd}",
     *     tags={"Api"},
     *     summary="Get Partner Basic Info Using Their Code",
     *     description="Use this endpoint to get basic information about a partner.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="pcd",
     *         in="path",
     *         required=true,
     *         description="Code of the Partner",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPartnerByCode($pcd)
    {
        $pld = partner_basic_data::where("pcode", $pcd)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPartnerBasicInfo/{uid}",
     *     tags={"Api"},
     *     summary="Get Partner Basic Info",
     *     description="Use this endpoint to get basic information about a partner.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the Partner. User ID, not partner code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPartnerBasicInfo($uid)
    {
        $pld = partner_basic_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPartnerGeneralInfo/{uid}",
     *     tags={"Api"},
     *     summary="Get Partner General Info",
     *     description="Use this endpoint to get general information about a partner.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPartnerGeneralInfo($uid)
    {
        $pld = partner_general_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getPartnerFinancialInfo/{uid}",
     *     tags={"Api"},
     *     summary="Get Partner Fiancial Info",
     *     description="Use this endpoint to get financial information about a partner.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPartnerFinancialInfo($uid)
    {
        $pld = partner_financial_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAnnouncements",
     *     tags={"Api"},
     *     summary="Get Announcements",
     *     description="Use this endpoint to get information about announcements.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve. Default is 5",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAnnouncements()
    {
        $start = 0;
        $count = 5;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $anns = announcements::skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $anns,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSchoolSession",
     *     tags={"Api"},
     *     summary="Set a school's Session. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a session.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="year", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSchSesn(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'year' => 'required',
        ]);
        $sch = school::where('sid', $request->schid)->first();
        if ($sch) {
            $sch->update([
                'cssn' => $request->year,
            ]);
            return response()->json([
                "status" => true,
                "message" => "Success"
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "School Not Found"
        ], 400);
    }


    /**
     * @OA\Post(
     *     path="/api/setSchoolTerm",
     *     tags={"Api"},
     *     summary="Set a school's current Term",
     *     description="This endpoint is used to set current term",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="no", type="string"),
     *             @OA\Property(property="name", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSchTrm(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'no' => 'required',
            'name' => 'required',
        ]);
        $sch = school::where('sid', $request->schid)->first();
        if ($sch) {
            $sch->update([
                'ctrm' => $request->no,
                'ctrmn' => $request->name,
            ]);
            return response()->json([
                "status" => true,
                "message" => "Success"
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "School Not Found"
        ], 400);
    }


    /**
     * @OA\Post(
     *     path="/api/setSchoolClassArm",
     *     tags={"Api"},
     *     summary="Set A Schools Class. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a class.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="schid", type="string",),
     *             @OA\Property(property="cls_id", type="string",),
     *             @OA\Property(property="name", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSchoolClassArm(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'cls_id' => 'required',
            'name' => 'required',
        ]);
        $data = [
            'schid' => $request->schid,
            'cls_id' => $request->cls_id,
            'name' => $request->name,
        ];
        $sch_cls = [];
        if ($request->has('id')) {
            $sch_cls = sch_cls::where('id', $request->id)->first();
            if ($sch_cls) {
                $sch_cls->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Class Not Found",
                ]);
            }
        } else {
            $sch_cls = sch_cls::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $sch_cls
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolArms/{schid}",
     *     tags={"Api"},
     *     summary="Get All Class Arms for a particular school",
     *     description="Use this endpoint to get a list of classes",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolArms($schid)
    {
        $sch_cls = sch_cls::where('schid', $schid)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $sch_cls,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolClassArms/{schid}/{clsid}",
     *     tags={"Api"},
     *     summary="Get Classes for a particular school",
     *     description="Use this endpoint to get a list of classes",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve. Default is 5",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolClassArms($schid, $clsid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $sch_cls = sch_cls::where('schid', $schid)->where('cls_id', $clsid)->take($count)->skip($start)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $sch_cls,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolArmsStat/{schid}/{clsid}",
     *     tags={"Api"},
     *     summary="Get total no for Classes for a particular school",
     *     description="Use this endpoint to get a total no of classes",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolArmsStat($schid, $clsid)
    {
        $total = sch_cls::where('schid', $schid)->where('cls_id', $clsid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getASchoolClassArm/{cid}",
     *     tags={"Api"},
     *     summary="Get a particular class",
     *     description="Use this endpoint to get a particular class",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="cid",
     *         in="path",
     *         required=true,
     *         description="Unique Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getASchoolClassArm($cid)
    {
        $cls = sch_cls::where('id', $cid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $cls,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSchoolClass",
     *     tags={"Api"},
     *     summary="Set A Schools Class. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a class.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="schid", type="string",),
     *             @OA\Property(property="clsid", type="string",),
     *             @OA\Property(property="name", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSchoolClass(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'clsid' => 'required',
            'name' => 'required',
        ]);
        $data = [
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'name' => $request->name,
        ];
        $school_class = [];
        if ($request->has('id')) {
            $school_class = school_class::where('id', $request->id)->first();
            if ($school_class) {
                $school_class->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Class Not Found",
                ]);
            }
        } else {
            $school_class = school_class::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $school_class
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolClasses/{schid}",
     *     tags={"Api"},
     *     summary="Get All Classes for a particular school",
     *     description="Use this endpoint to get a list of classes",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolClasses($schid)
    {
        $school_class = school_class::where('schid', $schid)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $school_class,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/setSchoolGradeInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set grading info about a school",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="a_h", type="string"),
     *             @OA\Property(property="a_l", type="string"),
     *             @OA\Property(property="b2_h", type="string"),
     *             @OA\Property(property="b2_l", type="string"),
     *             @OA\Property(property="b3_h", type="string"),
     *             @OA\Property(property="b3_l", type="string"),
     *             @OA\Property(property="c4_h", type="string"),
     *             @OA\Property(property="c4_l", type="string"),
     *             @OA\Property(property="c5_h", type="string"),
     *             @OA\Property(property="c5_l", type="string"),
     *             @OA\Property(property="c6_h", type="string"),
     *             @OA\Property(property="c6_l", type="string"),
     *             @OA\Property(property="d7_h", type="string"),
     *             @OA\Property(property="d7_l", type="string"),
     *             @OA\Property(property="e8_h", type="string"),
     *             @OA\Property(property="e8_l", type="string"),
     *             @OA\Property(property="f_h", type="string"),
     *             @OA\Property(property="f_l", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="staff data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setSchoolGradeInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "a_h" => "required",
            "a_l" => "required",
            "b2_h" => "required",
            "b2_l" => "required",
            "b3_h" => "required",
            "b3_l" => "required",
            "c4_h" => "required",
            "c4_l" => "required",
            "c5_h" => "required",
            "c5_l" => "required",
            "c6_h" => "required",
            "c6_l" => "required",
            "d7_h" => "required",
            "d7_l" => "required",
            "e8_h" => "required",
            "e8_l" => "required",
            "f_h" => "required",
            "f_l" => "required",
        ]);
        school_grade_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "a_h" => $request->a_h,
                "a_l" => $request->a_l,
                "b2_h" => $request->b2_h,
                "b2_l" => $request->b2_l,
                "b3_h" => $request->b3_h,
                "b3_l" => $request->b3_l,
                "c4_h" => $request->c4_h,
                "c4_l" => $request->c4_l,
                "c5_h" => $request->c5_h,
                "c5_l" => $request->c5_l,
                "c6_h" => $request->c6_h,
                "c6_l" => $request->c6_l,
                "d7_h" => $request->d7_h,
                "d7_l" => $request->d7_l,
                "e8_h" => $request->e8_h,
                "e8_l" => $request->e8_l,
                "f_h" => $request->f_h,
                "f_l" => $request->f_l,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getSchoolGradeInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a school's grading Info",
     *     description="Use this endpoint to get grading information about a staff.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolGradeInfo($uid)
    {
        $pld = school_grade_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    //--STUDENT

    /**
     * @OA\Post(
     *     path="/api/registerStudent",
     *     tags={"Unprotected"},
     *     summary="Register a new student",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string", description="School ID which this student belongs"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="term", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="sch3", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *             @OA\Property(property="password", type="string", description="The password for the student"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */


    public function registerStudent(Request $request)
    {
        //Data validation
        $request->validate([
            "schid" => "required",
            "email" => "required|email|unique:users,email",
            "password" => "required",
            "fname" => "required",
            "lname" => "required",
            "term" => "required",
            "ssn" => "required",
            "sch3" => "required",
            "stat" => "required",
        ]);
        if (strlen($request->password) < 6) {
            return response()->json([
                "status" => false,
                "message" => "Password must be at least 6 char",
            ], 400);
        }
        $typ = 'z';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        $tstd = null;
        if ($request->cuid) {
            $tstd = student::where("cuid", $request->cuid)->first();
        } else {
            if ($request->count) {
                $tstd = student::where("sch3", $request->sch3)->where("year", $request->ssn)
                    ->where("term", $request->term)->where("count", $request->count)->first();
            }
        }
        if (!$usr && !$tstd) {
            $usr = User::create([
                "email" => $request->email,
                "typ" => $typ,
                "verif" => '1',
                "password" => bcrypt($request->password),
            ]);
            $count = $request->count;
            if (!$count) {
                $count = student::where('schid', $request->schid)->count() + 1;
            }
            $ssn = $request->ssn;
            student::create([
                "sid" => strval($usr->id),
                "schid" => $request->schid,
                "fname" => $request->fname,
                "mname" => $request->mname,
                "lname" => $request->lname,
                "count" => strval($count),
                "year" => $ssn,
                "term" => $request->term,
                "sch3" => $request->sch3,
                "s_basic" => '0',
                "s_medical" => '0',
                "s_parent" => '0',
                "s_academic" => '0',
                "rfee" => $request->stat,
                "stat" => $request->stat,
                "cuid" => $request->cuid,
            ]);
            $sid = $request->sch3 . '/' . $ssn . '/' . $request->term . '/' . strval($count);
            // Wrap the email sending logic in a try-catch block
            try {
                if ($request->cuid) {
                    $data = [
                        'name' => $request->fname,
                        'subject' => 'Welcome Back, Your ID remains ' . $request->cuid,
                        'body' => "Welcome back to your school's platform. Your account was created successfully. If you havent already, please login to your dashboard using the link below and complete your student profile. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. Your Student ID is " . $request->cuid,
                        'link' => env('PORTAL_URL') . '/studentLogin' . '/' . $request->schid,
                    ];
                    Mail::to($request->email)->send(new SSSMails($data));
                } else {
                    $data = [
                        'name' => $request->fname,
                        'subject' => 'Welcome, Your ID is ' . $sid,
                        'body' => "Welcome to your school's platform. Your account was created successfully. If you havent already, please login to your dashboard using the link below and complete your student profile. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. Your Student ID is " . $sid,
                        'link' => env('PORTAL_URL') . '/studentLogin' . '/' . $request->schid,
                    ];
                    Mail::to($request->email)->send(new SSSMails($data));
                }
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }

            // Respond
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            return response()->json([
                "status" => true,
                "message" => "User created successfully",
                "token" => $token,
                "sid" => $sid,
                "user_id" => strval($usr->id)
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Account already exists",
        ], 400);
    }
    /**
     * @OA\Post(
     *     path="/api/studentLoginByEmail",
     *     tags={"Unprotected"},
     *     summary="Student Login to the application by Email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="your-jwt-token-here", description="This will contain a JWT token that must be passed with consequent request using bearer token"),
     *         )
     *     ),
     * )
     */
    public function studentLoginByEmail(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
        $typ = 'z';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if ($usr) {
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                $std = student::where('sid', strval($usr->id))->first();
                return response()->json([
                    "status" => true,
                    "message" => "Login successful",
                    "token" => $token,
                    "pld" => $usr,
                    "std" => $std,
                ]);
            }
        }
        // Respond
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/studentLoginByID",
     *     tags={"Unprotected"},
     *     summary="Student Login to the application",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="your-jwt-token-here", description="This will contain a JWT token that must be passed with consequent request using bearer token"),
     *         )
     *     ),
     * )
     */
    public function studentLoginByID(Request $request)
    {
        //Data validation
        $request->validate([
            "stid" => "required",
            "schid" => "required",
            "password" => "required",
        ]);
        $typ = 'z';
        $std = [];
        $compo = explode("/", $request->stid);
        if (count($compo) == 4) {
            $sch3 = $compo[0];
            $year = $compo[1];
            $term = $compo[2];
            $count = $compo[3];
            $std = student::where("schid", $request->schid)->where("year", $year)->where("term", $term)
                ->where("count", $count)->first();
        } else {
            $std = student::where("cuid", $request->stid)->first();
        }
        if ($std) {
            $usr = User::where("typ", $typ)->where("id", $std->sid)->first();
            $token = JWTAuth::attempt([
                "email" => $usr->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                return response()->json([
                    "status" => true,
                    "message" => "Login successful",
                    "token" => $token,
                    "pld" => $usr,
                ]);
            }
        }
        // Respond
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/admitStudent",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="admit/reject a student",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function admitStudent(Request $request)
    {
        //Data validation
        $request->validate([
            "stid" => "required",
            "stat" => "required",
            "schid" => "required",
        ]);
        $std = student::where('sid', $request->stid)->first();
        if ($std) {
            $std->update([
                "stat" => $request->stat
            ]);
            $usr = User::where('id', $std->sid)->first();
            // Wrap the email sending logic in a try-catch block
            try {
                $data = [
                    'name' => $std->fname,
                    'subject' => 'Application ' . ($request->stat == '1' ? 'Approved' : 'Decline'),
                    'body' => "Your application to our school has been " . ($request->stat == '1' ? 'approved' : 'decline'),
                    'link' => env('PORTAL_URL') . '/studentLogin' . '/' . $request->schid,
                ];
                Mail::to($usr->email)->send(new SSSMails($data));
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }

            return response()->json([
                "status" => true,
                "message" => "Success",
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Student Not Found",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/setAppFeePaid",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="set App Fee Paid",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setAppFeePaid(Request $request)
    {
        //Data validation
        $request->validate([
            "stid" => "required",
        ]);
        student::where('sid', $request->stid)->update([
            "rfee" => '1'
        ]);
        return response()->json([
            "status" => false,
            "message" => "Student Not Found",
        ], 400);
    }

    /**
     * @OA\Get(
     *     path="/api/getStudentsByStatus/{stat}/{schid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a list of students by thier status",
     *     description="Use this endpoint to get a list of students by thier status",
     *     @OA\Parameter(
     *         name="stat",
     *         in="path",
     *         required=true,
     *         description="Stat Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentsByStatus($stat, $schid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = student::where('schid', $schid)->where('stat', $stat)->orderBy('sid', 'desc')->skip($start)->take($count)->get();
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->sid;
            $academicData = student_academic_data::where('user_id', $user_id)->first();
            $basicData = student_basic_data::where('user_id', $user_id)->first();
            $pld[] = [
                's' => $member,
                'b' => $basicData,
                'a' => $academicData,
            ];
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStudentAtOnce",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set all info about a student",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="last_school", type="string"),
     *             @OA\Property(property="last_class", type="string"),
     *             @OA\Property(property="new_class", type="string"),
     *             @OA\Property(property="new_class_main", type="string"),
     *             @OA\Property(property="dob", type="string"),
     *             @OA\Property(property="sex", type="string"),
     *             @OA\Property(property="height", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="lga", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="term", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="sch3", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *             @OA\Property(property="password", type="string", description="The password for the student"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStudentAtOnce(Request $request)
    {
        //Data validation
        $request->validate([
            "schid" => "required",
            "email" => "required|email",
            "password" => "required",
            "fname" => "required",
            "lname" => "required",
            "term" => "required",
            "ssn" => "required",
            "sch3" => "required",
            "stat" => "required",
            "schid" => "required",
            "last_school" => "required",
            "last_class" => "required",
            "new_class" => "required",
            "new_class_main" => "required",
            "dob" => "required",
            "sex" => "required",
            "height" => "required",
            "country" => "required",
            "state" => "required",
            "lga" => "required",
            "addr" => "required",
        ]);
        if (strlen($request->password) < 6) {
            return response()->json([
                "status" => false,
                "message" => "Password must be at least 6 characters",
            ], 400);
        }
        $typ = 'z';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if (!$usr) {
            $usr = User::create([
                "email" => $request->email,
                "typ" => $typ,
                "verif" => '1',
                "password" => bcrypt($request->password),
            ]);
            $count = $request->count;
            if (!$count) {
                $count = student::where('schid', $request->schid)->count() + 1;
            }
            $ssn = $request->ssn;
            student::create([
                "sid" => strval($usr->id),
                "schid" => $request->schid,
                "fname" => $request->fname,
                "mname" => $request->mname,
                "lname" => $request->lname,
                "count" => strval($count),
                "year" => $ssn,
                "term" => $request->term,
                "sch3" => $request->sch3,
                "s_basic" => '0',
                "s_medical" => '0',
                "s_parent" => '0',
                "s_academic" => '0',
                "rfee" => $request->stat,
                "stat" => $request->stat,
                "cuid" => $request->cuid,
            ]);
            $sid = $request->sch3 . '/' . $ssn . '/' . $request->term . '/' . strval($count);
            // Wrap the email sending logic in a try-catch block
            try {
                if ($request->cuid) {
                    $data = [
                        'name' => $request->fname,
                        'subject' => 'Welcome Back, Your ID remains ' . $request->cuid,
                        'body' => "Welcome back to your school's platform. Your account was created successfully. If you havent already, please login to your dashboard using the link below and complete your student profile. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. Your Student ID is " . $request->cuid,
                        'link' => env('PORTAL_URL') . '/studentLogin' . '/' . $request->schid,
                    ];
                    Mail::to($request->email)->send(new SSSMails($data));
                } else {
                    $data = [
                        'name' => $request->fname,
                        'subject' => 'Welcome, Your ID is ' . $sid,
                        'body' => "Welcome to your school's platform. Your account was created successfully. If you havent already, please login to your dashboard using the link below and complete your student profile. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. Your Student ID is " . $sid,
                        'link' => env('PORTAL_URL') . '/studentLogin' . '/' . $request->schid,
                    ];
                    Mail::to($request->email)->send(new SSSMails($data));
                }
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }
            $user_id = strval($usr->id);
            $refreshSubjects = false;
            $oldData = student_academic_data::where('user_id', $user_id)->first();
            if ($oldData) {
                $refreshSubjects = $oldData->new_class_main != $request->new_class_main;
            } else {
                $refreshSubjects = true;
            }
            student_academic_data::updateOrCreate(
                ["user_id" => $user_id,],
                [
                    "last_school" => $request->last_school,
                    "last_class" => $request->last_class,
                    "new_class" => $request->new_class,
                    "new_class_main" => $request->new_class_main,
                ]
            );
            if ($refreshSubjects) { //Delete all subjs and set new, comps ones
                student_subj::where('stid', $user_id)->delete();
                // $schid = $request->schid;
                // $clsid = $request->new_class_main;
                // $members = class_subj::where("schid", $schid)->where("clsid", $clsid)->where("comp", '1')->get();
                // $pld = [];
                // foreach ($members as $member) {
                //     $sbj = $member->subj_id;
                //     $stid = $user_id;
                //     student_subj::updateOrCreate(
                //         ["uid"=> $sbj.$stid],
                //         [
                //         "stid"=> $stid,
                //         "sbj"=> $sbj,
                //         "comp"=> $member->comp,
                //         "schid"=> $member->schid,
                //     ]);
                // }
            }
            student_basic_data::updateOrCreate(
                ["user_id" => $user_id,],
                [
                    "dob" => $request->dob,
                    "sex" => $request->sex,
                    "height" => $request->height,
                    "country" => $request->country,
                    "state" => $request->state,
                    "lga" => $request->lga,
                    "addr" => $request->addr,
                ]
            );
            student::where('sid', $user_id)->update([
                "s_basic" => '1',
                "s_academic" => '1'
            ]);
            //Set paid acceptance fee
            $uid = $user_id . $request->schid . $request->new_class_main;
            afeerec::updateOrCreate(
                ["uid" => $uid,],
                [
                    "stid" => $user_id,
                    "schid" => $request->schid,
                    "clsid" => $request->new_class_main,
                    "amt" => 0,
                ]
            );
            return response()->json([
                "status" => true,
                "message" => "Success",
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Account already exists",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/setStudentBasicInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set basic info about a student data. Pass 1/0 for stat depending of if application is approved/now",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="dob", type="string"),
     *             @OA\Property(property="sex", type="string"),
     *             @OA\Property(property="height", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="lga", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    // public function setStudentBasicInfo(Request $request){
    //     //Data validation
    //     $request->validate([
    //         "user_id"=> "required",
    //         "dob"=> "required",
    //         "sex"=> "required",
    //         "height"=> "required",
    //         "country"=> "required",
    //         "state"=> "required",
    //         "lga"=> "required",
    //         "addr"=> "required",
    //         "fname"=> "required",
    //         "lname"=> "required",
    //     ]);
    //     student_basic_data::updateOrCreate(
    //         ["user_id"=> $request->user_id,],
    //         [
    //         "dob"=> $request->dob,
    //         "sex"=> $request->sex,
    //         "height"=> $request->height,
    //         "country"=> $request->country,
    //         "state"=> $request->state,
    //         "lga"=> $request->lga,
    //         "addr"=> $request->addr,
    //     ]);
    //     student::where('sid',$request->user_id)->update([
    //         "s_basic"=>'1',
    //         "fname"=> $request->fname,
    //         "lname"=> $request->lname,
    //         "mname"=> $request->mname,
    //     ]);
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Success",
    //     ]);
    // }


    public function setStudentBasicInfo(Request $request)
    {
        // Data validation
        $request->validate([
            "user_id" => "required",
            "dob" => "required",
            "sex" => "required",
            "height" => "required",
            "country" => "required",
            "state" => "required",
            "lga" => "required",
            "addr" => "required",
            "fname" => "required",
            "lname" => "required",
        ]);

        // Update or Create student_basic_data
        student_basic_data::updateOrCreate(
            ["user_id" => $request->user_id],
            [
                "dob" => $request->dob,
                "sex" => $request->sex,
                "height" => $request->height,
                "country" => $request->country,
                "state" => $request->state,
                "lga" => $request->lga,
                "addr" => $request->addr,
            ]
        );

        // Update student table
        student::where('sid', $request->user_id)->update([
            "s_basic" => '1',
            "fname" => $request->fname,
            "lname" => $request->lname,
            "mname" => $request->mname,
        ]);

        // Update old_student table
        old_student::where('sid', $request->user_id)->update([
            "fname" => $request->fname,
            "mname" => $request->mname,
            "lname" => $request->lname,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getStudentBasicInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's Basic Info",
     *     description="Use this endpoint to get basic information about a student.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentBasicInfo($uid)
    {
        $pld = student_basic_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStudentSubject",
     *     summary="Assign a subject to a student",
     *     description="This endpoint assigns a single subject to a single student. If the student-subject combination already exists, it will be skipped.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"uid","stid","sbj","comp","schid"},
     *             @OA\Property(property="uid", type="integer", example=1, description="User ID of the admin/teacher assigning the subject"),
     *             @OA\Property(property="stid", type="integer", example=101, description="Single student ID"),
     *             @OA\Property(property="sbj", type="integer", example=201, description="Single subject ID"),
     *             @OA\Property(property="comp", type="string", example="1", description="Compulsory or elective flag"),
     *             @OA\Property(property="schid", type="integer", example=12, description="School ID"),
     *             @OA\Property(property="clsid", type="integer", nullable=true, example=11, description="Class ID (optional)"),
     *             @OA\Property(property="trm", type="string", nullable=true, example="2", description="Term (optional)"),
     *             @OA\Property(property="ssn", type="string", nullable=true, example="2025", description="Session (optional)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Subject assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subjects assignment completed."),
     *             @OA\Property(
     *                 property="assigned",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="stid", type="integer", example=101),
     *                     @OA\Property(property="sbj", type="integer", example=201)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="skipped",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="stid", type="integer", example=101),
     *                     @OA\Property(property="sbj", type="integer", example=202)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */




    //////////////////////////////////////
    public function setStudentSubject(Request $request)
    {
        // Validate request
        $request->validate([
            "uid"   => "required",
            "stid"  => "required", // single or array of student IDs
            "sbj"   => "required", // single or array of subject IDs
            "comp"  => "required",
            "schid" => "required",
            "clsid" => "nullable",
            "trm"   => "nullable",
            "ssn"   => "nullable"
        ]);

        $students = is_array($request->stid) ? $request->stid : [$request->stid];
        $subjects = is_array($request->sbj)  ? $request->sbj  : [$request->sbj];

        $assigned = [];
        $skipped = [];

        foreach ($students as $stid) {
            foreach ($subjects as $sbj) {
                $exists = student_subj::where('stid', $stid)
                    ->where('sbj', $sbj)
                    ->where('schid', $request->schid)
                    ->when($request->clsid, fn($q) => $q->where('clsid', $request->clsid))
                    ->when($request->trm, fn($q) => $q->where('trm', $request->trm))
                    ->when($request->ssn, fn($q) => $q->where('ssn', $request->ssn))
                    ->exists();

                if (!$exists) {
                    student_subj::create([
                        "uid"   => $request->uid,
                        "stid"  => $stid,
                        "sbj"   => $sbj,
                        "comp"  => $request->comp,
                        "schid" => $request->schid,
                        "clsid" => $request->clsid,
                        "trm"   => $request->trm,
                        "ssn"   => $request->ssn
                    ]);
                    $assigned[] = ["stid" => $stid, "sbj" => $sbj];
                } else {
                    $skipped[] = ["stid" => $stid, "sbj" => $sbj];
                }
            }
        }

        return response()->json([
            "status"  => true,
            "message" => "Subjects assignment completed.",
            "assigned" => $assigned,
            "skipped"  => $skipped,
        ]);
    }




    // public function setStudentSubject(Request $request){
    //     // Data validation
    //     $request->validate([
    //         "uid"   => "required",
    //         "stid"  => "required",
    //         "sbj"   => "required",
    //         "comp"  => "required",
    //         "schid" => "required",
    //         "clsid" => "nullable",
    //         "trm" => "nullable",
    //         "ssn" => "nullable"
    //     ]);
    //     $count = student_subj::where('stid',$request->stid)
    //                     ->where('sbj',$request->sbj)
    //                     ->where('schid', $request->schid)
    //                     ->count(); // need to map term session and clsid if once pass from request
    //     if($count){
    //         return response()->json([
    //             "status"  => false,
    //             "message" => "Subject Already Exist",
    //         ]);
    //     }

    //     student_subj::create([
    //         "uid"   => $request->uid,
    //         "stid"  => $request->stid,
    //         "sbj"   => $request->sbj,
    //         "comp"  => $request->comp,
    //         "schid" => $request->schid,
    //         "clsid" => $request->clsid,
    //         "trm" => $request->trm,
    //         "ssn" => $request->ssn
    //     ]);

    //     return response()->json([
    //         "status"  => true,
    //         "message" => "Subject Inserted Successfully",
    //     ]);
    // }


    /**
     * @OA\Get(
     *     path="/api/getStudentSubjects/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's subjects",
     *     description="Use this endpoint to get subjects of a student.",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentSubjects($stid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = student_subj::where("stid", $stid)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }





    /**
     * @OA\Delete(
     *     path="/api/deleteStudentSubject/{uid}/{sbj}",
     *     summary="Delete a student's subject if the score is 0",
     *     description="Deletes a subject assigned to a student if the score is exactly 0. If the subject has a score greater than 0, deletion is not allowed.",
     *     operationId="deleteStudentSubject",
     *      security={{"bearerAuth": {}}},
     *     tags={"Api"},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="The unique ID of the student-subject association",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbj",
     *         in="path",
     *         required=true,
     *         description="The subject code or name assigned to the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subject deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Subject not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Subject not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete subject due to score conditions",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete subject. It has existing scores greater than 0.")
     *         )
     *     )
     * )
     */
    public function deleteStudentSubject($uid, $sbj)
    {
        // Retrieve the specific subject assigned to the student
        $subject = student_subj::where('uid', $uid)->where('sbj', $sbj)->first();

        if (!$subject) {
            return response()->json([
                "status" => false,
                "message" => "Subject not found."
            ], 404);
        }

        // Check if the subject has any score that is NOT zero or null
        $hasValidScores = std_score::where('stid', $subject->stid)
            ->where('sbj', $sbj)
            ->where(function ($query) {
                $query->where('scr', '>', 0);
            })
            ->exists();

        if ($hasValidScores) {
            return response()->json([
                "status" => false,
                "message" => "Cannot delete subject. It has scores greater than 0."
            ], 400);
        }

        // Delete the student subject and any zero or null scores
        std_score::where('stid', $subject->stid)
            ->where('sbj', $sbj)
            ->where(function ($query) {
                $query->whereNull('scr')
                    ->orWhere('scr', '=', 0);
            })->delete();

        $subject->delete();

        return response()->json([
            "status" => true,
            "message" => "Subject and related zero/null scores deleted successfully."
        ]);
    }




    /**
     * @OA\Post(
     *     path="/api/setResultMeta",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set metadata for results",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *             @OA\Property(property="ntrd", type="string"),
     *             @OA\Property(property="sdob", type="string"),
     *             @OA\Property(property="spos", type="string"),
     *             @OA\Property(property="subj_pos", type="string"),
     *             @OA\Property(property="num_of_days", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setResultMeta(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "schid" => "required",
            "ssn" => "required",
            "trm" => "required",
            "ntrd" => "required",
            "sdob" => "required",
            "spos" => "required",
            "subj_pos" => "nullable",
            "num_of_days" => "nullable",
        ]);

        result_meta::updateOrCreate(
            ["uid" => $request->uid,],
            [
                "schid" => $request->schid,
                "ssn" => $request->ssn,
                "trm" => $request->trm,
                "ntrd" => $request->ntrd,
                "sdob" => $request->sdob,
                "spos" => $request->spos,
                "subj_pos" => $request->subj_pos ?? 'y',
                "num_of_days" => $request->num_of_days,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getResultMeta/{schid}/{ssn}/{trm}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's subjects",
     *     description="Use this endpoint to get subjects of a student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getResultMeta($schid, $ssn, $trm)
    {
        $pld = result_meta::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }



    /**
     * @OA\Post(
     *     path="/api/setClassSubject",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set class compulsory and elective subjects",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="subj_id", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="comp", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="sesn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="class data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setClassSubject(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "schid" => "required",
            "subj_id" => "required",
            "name" => "required",
            "comp" => "required",
            "clsid" => "required",
            "sesn" => "required",
            "trm" => "required",
        ]);
        class_subj::updateOrCreate(
            ["uid" => $request->uid,],
            [
                "schid" => $request->schid,
                "subj_id" => $request->subj_id,
                "name" => $request->name,
                "comp" => $request->comp,
                "clsid" => $request->clsid,
                "sesn" => $request->sesn,
                "trm" => $request->trm,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getClassSubjects/{schid}/{clsid}/{sesn}/{trm}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a class's subjects",
     *     description="Use this endpoint to get subjects of a class.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *       @OA\Parameter(
     *         name="sesn",
     *         in="path",
     *         description="Academic session",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         description="Academic term",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassSubjects($schid, $clsid, $sesn, $trm)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = class_subj::where("schid", $schid)->where("clsid", $clsid)->where("sesn", $sesn)->where("trm", $trm)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClassSubjectsByStaff/{schid}/{clsid}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a class's subjects",
     *     description="Use this endpoint to get subjects of a class.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Staff Id",
     *         @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassSubjectsByStaff($schid, $clsid, $stid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
            ->where('class_subj.schid', $schid)
            ->where('class_subj.clsid', $clsid)
            ->where('staff_subj.stid', $stid)
            ->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClassSubject/{schid}/{clsid}/{sbid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a class's subjects",
     *     description="Use this endpoint to get subjects of a class.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbid",
     *         in="path",
     *         required=true,
     *         description="Subject Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassSubject($schid, $clsid, $sbid)
    {
        $pld = class_subj::where("schid", $schid)->where("clsid", $clsid)->where('subj_id', $sbid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteClassSubject/{uid}",
     *     tags={"Api"},
     *     summary="Delete a class subject",
     *     description="Use this endpoint to delete a class subject",
     *
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteClassSubject($uid)
    {
        $pld = class_subj::where('uid', $uid)->delete();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setClassMark",
     *     tags={"Api"},
     *     summary="Set a Class Mark. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a class mark.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="ise", type="string"),
     *             @OA\Property(property="pt", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setClassMark(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'ise' => 'required',
            'pt' => 'required',
            'schid' => 'required',
            'clsid' => 'required',
            'ssn' => 'required',
            'trm' => 'required',
        ]);
        $data = [
            'name' => $request->name,
            'ise' => $request->ise,
            'pt' => $request->pt,
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'ssn' => $request->ssn,
            'trm' => $request->trm,
        ];
        $class_mark = [];
        if ($request->has('id')) {
            $class_mark = sch_mark::where('id', $request->id)->first();
            if ($class_mark) {
                $class_mark->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Mark Not Found",
                ]);
            }
        } else {
            $class_mark = sch_mark::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $class_mark
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClassMarks/{schid}/{clsid}/{ssn}/{trm}",
     *     tags={"Api"},
     *     summary="Get All Marks for this Class",
     *     description="Use this endpoint to get a list of class marks",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),@OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassMarks($schid, $clsid, $ssn, $trm)
    {
        $pld = sch_mark::where("schid", $schid)->where("clsid", $clsid)->where("ssn", $ssn)->where("trm", $trm)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setArmResultConf",
     *     tags={"Api"},
     *     summary="Confirm a result. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to confirm a class arm result.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="arm", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="sbid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *             @OA\Property(property="rmk", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setArmResultConf(Request $request)
    {
        $request->validate([
            'arm' => 'required',
            'stid' => 'required',
            'schid' => 'required',
            'clsid' => 'required',
            'sbid' => 'required',
            'ssn' => 'required',
            'trm' => 'required',
            'rmk' => 'required',
            'stat' => 'required',
        ]);
        $data = [
            'arm' => $request->arm,
            'stid' => $request->stid,
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'sbid' => $request->sbid,
            'ssn' => $request->ssn,
            'trm' => $request->trm,
            'rmk' => $request->rmk,
            'stat' => $request->stat,
        ];
        $arm_result_conf = [];
        if ($request->has('id')) {
            $arm_result_conf = arm_result_conf::where('id', $request->id)->first();
            if ($arm_result_conf) {
                $arm_result_conf->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Result Conf Not Found",
                ]);
            }
        } else {
            $arm_result_conf = arm_result_conf::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            // "pld"=> $arm_result_conf
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getArmResultConf/{schid}/{clsid}/{sbid}/{ssn}/{trm}/{arm}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get result conf for this session/term",
     *     description="Use this endpoint to get result conf for this session/term",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbid",
     *         in="path",
     *         required=true,
     *         description="Subject Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="arm",
     *         in="path",
     *         required=true,
     *         description="Arm Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getArmResultConf($schid,$clsid,$sbid,$ssn,$trm,$arm){
    //     $pld = arm_result_conf::where("schid", $schid)->where("clsid", $clsid)->where("sbid", $sbid)->where("ssn", $ssn)->where("trm", $trm)->where("arm", $arm)->first();
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Success",
    //         "pld"=> $pld,
    //     ]);
    // }

    public function getArmResultConf($schid, $clsid, $sbid, $ssn, $trm, $arm)
    {
        $pld = arm_result_conf::join('student', 'arm_result_conf.schid', '=', 'student.schid')
            ->where('arm_result_conf.schid', $schid)
            ->where('arm_result_conf.clsid', $clsid)
            ->where('arm_result_conf.sbid', $sbid)
            ->where('arm_result_conf.ssn', $ssn)
            ->where('arm_result_conf.trm', $trm)
            ->where('arm_result_conf.arm', $arm)
            ->where('student.status', 'active') // Ensuring only active students
            ->select('arm_result_conf.*') // Selecting only arm_result_conf fields
            ->first();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStudentResultsByArm/{schid}/{clsid}/{ssn}/{trm}/{arm}",
     *     tags={"Api"},
     *
     *     summary="Get students result",
     *     description="Use this endpoint to get students result.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="arm",
     *         in="path",
     *         required=true,
     *         description="Arm Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getStudentResultsByArm($schid,$clsid,$ssn,$trm,$arm){
    //         $members = student::join('old_student', 'student.sid', '=', 'old_student.sid')
    //         ->where('student.schid', $schid)
    //         ->where('student.stat', "1")
    //         ->where('student.status', "active")
    //         ->where('old_student.ssn', $ssn)
    //         ->where('old_student.clsm', $clsid)
    //         ->where('old_student.status', "active")
    //         ->where('old_student.clsa', $arm)
    //         ->get();
    //         $totalStd = count($members);
    //         $cstds = [];
    //         $relevantSubjects = [];
    //         $clsSbj = [];
    // 		//BUG Fix on 19/03/2025
    //         $relevantClassSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsid)
    //             ->pluck('sbj');
    //         //BUG Fix End on 19/03/2025

    //         foreach ($members as $member) {
    //             $user_id = $member->sid;
    //             $res = student_res::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)
    //             ->where("clsm", $clsid)->where("clsa", $arm)->where("stid", $user_id)->first();

    //             if(!$res || $res->stat != "2"){
    //                 $academicData = student_academic_data::where('user_id', $user_id)->first();
    //                 $basicData = student_basic_data::where('user_id', $user_id)->first();
    //                 $psy = student_psy::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)
    //                 ->where("clsm", $clsid)->where("clsa", $arm)->where("stid", $user_id)->first();

    //                 $std = old_student::where("schid", $schid)->where("ssn", $ssn)->where("clsm", $clsid)->where("status", "active")->where("clsa", $arm)->where("sid", $user_id)->first();
    // 				$studentSubjects = student_subj::where('stid', $user_id)->whereIn('sbj', $relevantClassSubjects)->pluck('sbj'); // bug 19/08/2025
    //                 $allScores = std_score::where('stid',$user_id)
    //                 ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->whereIn("sbj", $studentSubjects)->where("clsid", $clsid)->get();
    //                 $mySbjs = [];
    //                 foreach($allScores as $scr){
    //                     $sbid = $scr->sbj;
    //                     if (!in_array($scr->sbj, $mySbjs)) {
    //                         $mySbjs[] = $scr->sbj;
    //                     }
    //                     if (!in_array($scr->sbj, $relevantSubjects)) {
    //                         $schSbj = subj::where('id',$scr->sbj)->first();
    //                         $clsSbj[] = $schSbj;
    //                         $relevantSubjects[] = $scr->sbj;
    //                     }
    //                 }
    //                 $subjectScores = [];
    //                 foreach($mySbjs as $sbid){
    //                     $subjectScores[$sbid] = [];
    //                 }
    //                 $scores = [];
    //                 foreach($allScores as $scr){
    //                     $sbid = $scr->sbj;
    //                     $subjectScores[$sbid][] = $scr;
    //                 }
    //                 $positions = [];
    //                 foreach($mySbjs as $sbid){
    //                     $scores[] = [
    //                         'sbid' => $sbid,
    //                         'scores' => $subjectScores[$sbid]
    //                     ];
    //                     $subjectPosition = student_sub_res::where('stid',$user_id)->where('sbj', $sbid)
    //                     ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsid)
    //                     ->where("clsa", $arm)->first();
    //                     $positions[] = [
    //                         'sbid' => $sbid,
    //                         // 'pos' => $subjectPosition->pos,
    //                             'pos' => $subjectPosition ? $subjectPosition->pos : null, // âœ… Prevents the error
    //                     ];
    //                 }
    //                 $psyexist = student_psy::where([
    //                     ['schid', $schid],
    //                     ['ssn', $ssn],
    //                     ['trm', $trm],
    //                     ['clsm', $clsid],
    //                     ['stid', $user_id]
    //                 ])->exists();

    //                 $resexist = student_res::where([
    //                     ['schid', $schid],
    //                     ['ssn', $ssn],
    //                     ['trm', $trm],
    //                     ['clsm', $clsid],
    //                     ['stid', $user_id]
    //                 ])->value('stat') ?? "0";

    //                 $studentres = [
    //                     'std'=> $std,
    //                     'sbj'=> $mySbjs,
    //                     'scr'=> $scores,
    //                     'psy'=> $psyexist,
    //                     'res'=> $resexist,
    //                 ];

    //                 $cstds[] = [
    //                     's'=> $member,
    //                     'b'=> $basicData,
    //                     'a'=> $academicData,
    //                     'p'=> $psy,
    //                     'r'=> $res,
    //                     'rs'=> $studentres,
    //                     'cnt'=> $totalStd,
    //                     'spos'=> $positions
    //                 ];
    //             }
    //         }
    //         $pld = [
    //             'std-pld'=>$cstds,
    //             'cls-sbj' => $clsSbj
    //         ];
    //         return response()->json([
    //             "status"=> true,
    //             "message"=> "Success",
    //             "pld"=> $pld,
    //  ]);
    // }

    // public function getStudentResultsByArm($schid, $clsid, $ssn, $trm, $arm) {
    //     $members = student::join('old_student', 'student.sid', '=', 'old_student.sid')
    //         ->where('student.schid', $schid)
    //         ->where('student.stat', "1")
    //         ->where('student.status', "active")
    //         ->where('old_student.ssn', $ssn)
    //         ->where('old_student.clsm', $clsid)
    //         ->where('old_student.status', "active")
    //         ->where('old_student.clsa', $arm)
    //         ->get();

    //     $totalStd = count($members);
    //     $cstds = [];
    //     $relevantSubjects = [];
    //     $clsSbj = [];

    //     $relevantClassSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //         ->where('class_subj.schid', $schid)
    //         ->where('class_subj.clsid', $clsid)
    //         ->pluck('sbj');

    //     foreach ($members as $member) {
    //         $user_id = $member->sid;
    //         $res = student_res::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)
    //             ->where("clsm", $clsid)->where("clsa", $arm)->where("stid", $user_id)->first();

    //         if (!$res || $res->stat != "2") {
    //             $academicData = student_academic_data::where('user_id', $user_id)->first();
    //             $basicData = student_basic_data::where('user_id', $user_id)->first();
    //             $psy = student_psy::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)
    //                 ->where("clsm", $clsid)->where("clsa", $arm)->where("stid", $user_id)->first();

    //             $std = old_student::where("schid", $schid)->where("ssn", $ssn)->where("clsm", $clsid)
    //                 ->where("status", "active")->where("clsa", $arm)->where("sid", $user_id)->first();

    //             $studentSubjects = student_subj::where('stid', $user_id)
    //                 ->whereIn('sbj', $relevantClassSubjects)
    //                 ->pluck('sbj');

    //         $allScores = std_score::where('stid', $user_id)
    //             ->where("schid", $schid)
    //             ->where("ssn", $ssn)
    //             ->where("trm", $trm)
    //             ->whereIn("sbj", $studentSubjects)
    //             ->where("clsid", $clsid)
    //             ->whereNotNull('scr')
    //             ->where('scr', '>', 0) // Exclude zero or null scores
    //             ->get();


    //             $mySbjs = [];
    //             foreach ($allScores as $scr) {
    //                 $sbid = $scr->sbj;
    //                 if (!in_array($sbid, $mySbjs)) {
    //                     $mySbjs[] = $sbid;
    //                 }
    //                 if (!in_array($sbid, $relevantSubjects)) {
    //                     $schSbj = subj::where('id', $sbid)->first();
    //                     $clsSbj[] = $schSbj;
    //                     $relevantSubjects[] = $sbid;
    //                 }
    //             }

    //             $subjectScores = [];
    //             foreach ($mySbjs as $sbid) {
    //                 $subjectScores[$sbid] = [];
    //             }

    //             $scores = [];
    //             foreach ($allScores as $scr) {
    //                 $sbid = $scr->sbj;
    //                 $subjectScores[$sbid][] = $scr;
    //             }

    //             $positions = [];
    //             foreach ($mySbjs as $sbid) {
    //                 $scores[] = [
    //                     'sbid' => $sbid,
    //                     'scores' => $subjectScores[$sbid]
    //                 ];
    //                 $subjectPosition = student_sub_res::where('stid', $user_id)
    //                     ->where('sbj', $sbid)
    //                     ->where("schid", $schid)
    //                     ->where("ssn", $ssn)
    //                     ->where("trm", $trm)
    //                     ->where("clsm", $clsid)
    //                     ->where("clsa", $arm)
    //                     ->first();

    //                 $positions[] = [
    //                     'sbid' => $sbid,
    //                     'pos' => $subjectPosition ? $subjectPosition->pos : null,
    //                 ];
    //             }



    //             $psyexist = student_psy::where([
    //                 ['schid', $schid],
    //                 ['ssn', $ssn],
    //                 ['trm', $trm],
    //                 ['clsm', $clsid],
    //                 ['stid', $user_id]
    //             ])->exists();

    //             $resexist = student_res::where([
    //                 ['schid', $schid],
    //                 ['ssn', $ssn],
    //                 ['trm', $trm],
    //                 ['clsm', $clsid],
    //                 ['stid', $user_id]
    //             ])->value('stat') ?? "0";

    //             $studentres = [
    //                 'std' => $std,
    //                 'sbj' => $mySbjs,
    //                 'scr' => $scores,
    //                 'psy' => $psyexist,
    //                 'res' => $resexist,
    //             ];

    //             $cstds[] = [
    //                 's' => $member,
    //                 'b' => $basicData,
    //                 'a' => $academicData,
    //                 'p' => $psy,
    //                 'r' => $res,
    //                 'rs' => $studentres,
    //                 'cnt' => $totalStd,
    //                 'spos' => $positions
    //             ];
    //         }
    //     }

    //     // Get the number of fails (nof) from the result_meta table
    //     $nof = result_meta::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //     ])->value('num_of_days') ?? 0;

    //     $presentCountQuery = \DB::table('attendances')
    //     ->where('schid', $schid)
    //     ->where('ssn', $ssn)
    //     ->where('trm', $trm)
    //     ->where('clsm', $clsid)
    //     ->where('clsa', $arm)
    //     ->where('sid', $std->sid);

    //     // Check if any attendance exists for this student
    //     $attendanceExists = $presentCountQuery->exists();

    //     if ($attendanceExists) {
    //         $presentCount = $presentCountQuery->where('status', 1)->count();
    //         $absentCount = max(0, $nof - $presentCount);
    //     } else {
    //         $presentCount = null;
    //         $absentCount = null;
    //     }


    //     $pld = [
    //         'std-pld' => $cstds,
    //         'cls-sbj' => $clsSbj,
    //         'num_of_days' => $nof,
    //         'present_days' => $presentCount,
    //         'absent_days' => $absentCount,

    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" =>  $pld,
    //     ]);
    // }

    ///////////////

    public function getStudentResultsByArm($schid, $clsid, $ssn, $trm, $arm)
    {
        $members = student::join('old_student', 'student.sid', '=', 'old_student.sid')
            ->where('student.schid', $schid)
            ->where('student.stat', "1")
            ->where('student.status', "active")
            ->where('old_student.ssn', $ssn)
            ->where('old_student.clsm', $clsid)
            ->where('old_student.status', "active")
            ->where('old_student.clsa', $arm)
            ->get();

        $totalStd = count($members);
        $cstds = [];
        $relevantSubjects = [];
        $clsSbj = [];

        $relevantClassSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
            ->where('class_subj.schid', $schid)
            ->where('class_subj.clsid', $clsid)
            ->pluck('sbj');

        $nof = result_meta::where([
            ['schid', $schid],
            ['ssn', $ssn],
            ['trm', $trm],
        ])->value('num_of_days') ?? 0;

        foreach ($members as $member) {
            $user_id = $member->sid;

            $res = student_res::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsm", $clsid)
                ->where("clsa", $arm)
                ->where("stid", $user_id)
                ->first();

            $academicData = student_academic_data::where('user_id', $user_id)->first();
            $basicData = student_basic_data::where('user_id', $user_id)->first();
            $psy = student_psy::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsm", $clsid)
                ->where("clsa", $arm)
                ->where("stid", $user_id)
                ->first();

            $std = old_student::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("clsm", $clsid)
                ->where("status", "active")
                ->where("clsa", $arm)
                ->where("sid", $user_id)
                ->first();

            $studentSubjects = student_subj::where('stid', $user_id)
                ->whereIn('sbj', $relevantClassSubjects)
                ->pluck('sbj');

            $allScores = std_score::where('stid', $user_id)
                ->where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->whereIn("sbj", $studentSubjects)
                ->where("clsid", $clsid)
                ->whereNotNull('scr')
                ->where('scr', '>', 0)
                ->get();

            $mySbjs = [];
            foreach ($allScores as $scr) {
                $sbid = $scr->sbj;
                if (!in_array($sbid, $mySbjs)) {
                    $mySbjs[] = $sbid;
                }
                if (!in_array($sbid, $relevantSubjects)) {
                    $schSbj = subj::where('id', $sbid)->first();
                    if ($schSbj) {
                        $clsSbj[] = $schSbj;
                        $relevantSubjects[] = $sbid;
                    }
                }
            }

            $subjectScores = [];
            foreach ($mySbjs as $sbid) {
                $subjectScores[$sbid] = [];
            }

            $scores = [];
            foreach ($allScores as $scr) {
                $sbid = $scr->sbj;
                $subjectScores[$sbid][] = $scr;
            }

            $positions = [];
            foreach ($mySbjs as $sbid) {
                $scores[] = [
                    'sbid' => $sbid,
                    'scores' => $subjectScores[$sbid]
                ];
                $subjectPosition = student_sub_res::where('stid', $user_id)
                    ->where('sbj', $sbid)
                    ->where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsm", $clsid)
                    ->where("clsa", $arm)
                    ->first();

                $positions[] = [
                    'sbid' => $sbid,
                    'pos' => $subjectPosition ? $subjectPosition->pos : null,
                ];
            }

            $psyexist = student_psy::where([
                ['schid', $schid],
                ['ssn', $ssn],
                ['trm', $trm],
                ['clsm', $clsid],
                ['stid', $user_id]
            ])->exists();

            $resexist = $res->stat ?? "0";

            // Individual attendance logic
            $presentCount = 0;
            $absentCount = 0;

            $attendanceQuery = \DB::table('attendances')
                ->where('schid', $schid)
                ->where('ssn', $ssn)
                ->where('trm', $trm)
                ->where('sid', $user_id);

            if ($attendanceQuery->exists()) {
                $presentCount = $attendanceQuery->where('status', '1')->count();
                $absentCount = max(0, $nof - $presentCount);
            }

            $studentres = [
                'std' => $std,
                'sbj' => $mySbjs,
                'scr' => $scores,
                'psy' => $psyexist,
                'res' => $resexist,
                'present_days' => $presentCount,
                'absent_days' => $absentCount,
            ];

            $cstds[] = [
                's' => $member,
                'b' => $basicData,
                'a' => $academicData,
                'p' => $psy,
                'r' => $res,
                'rs' => $studentres,
                'cnt' => $totalStd,
                'spos' => $positions
            ];
        }

        $pld = [
            'std-pld' => $cstds,
            'cls-sbj' => $clsSbj,
            'num_of_days' => $nof,
        ];

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" =>  $pld,
        ]);
    }


    /////////////////////////////////////////////////
    // public function getStudentResultsByArm($schid, $clsid, $ssn, $trm, $arm)
    // {
    //     $members = student::join('old_student', 'student.sid', '=', 'old_student.sid')
    //         ->where('student.schid', $schid)
    //         ->where('student.stat', "1")
    //         ->where('student.status', "active")
    //         ->where('old_student.ssn', $ssn)
    //         ->where('old_student.clsm', $clsid)
    //         ->where('old_student.status', "active")
    //         ->where('old_student.clsa', $arm)
    //         ->get();

    //     $totalStd = count($members);
    //     $cstds = [];
    //     $relevantSubjects = [];
    //     $clsSbj = [];

    //     $relevantClassSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //         ->where('class_subj.schid', $schid)
    //         ->where('class_subj.clsid', $clsid)
    //         ->pluck('sbj');

    //     $nof = result_meta::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //     ])->value('num_of_days') ?? 0;

    //     foreach ($members as $member) {
    //         $user_id = $member->sid;

    //         $res = student_res::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)
    //             ->where("clsm", $clsid)->where("clsa", $arm)->where("stid", $user_id)->first();

    //         $academicData = student_academic_data::where('user_id', $user_id)->first();
    //         $basicData = student_basic_data::where('user_id', $user_id)->first();
    //         $psy = student_psy::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)
    //             ->where("clsm", $clsid)->where("clsa", $arm)->where("stid", $user_id)->first();

    //         $std = old_student::where("schid", $schid)->where("ssn", $ssn)->where("clsm", $clsid)
    //             ->where("status", "active")->where("clsa", $arm)->where("sid", $user_id)->first();

    //         $studentSubjects = student_subj::where('stid', $user_id)
    //             ->whereIn('sbj', $relevantClassSubjects)
    //             ->pluck('sbj');

    //         $allScores = std_score::where('stid', $user_id)
    //             ->where("schid", $schid)
    //             ->where("ssn", $ssn)
    //             ->where("trm", $trm)
    //             ->whereIn("sbj", $studentSubjects)
    //             ->where("clsid", $clsid)
    //             ->whereNotNull('scr')
    //             ->where('scr', '>', 0)
    //             ->get();

    //         $mySbjs = [];
    //         foreach ($allScores as $scr) {
    //             $sbid = $scr->sbj;
    //             if (!in_array($sbid, $mySbjs)) {
    //                 $mySbjs[] = $sbid;
    //             }
    //             if (!in_array($sbid, $relevantSubjects)) {
    //                 $schSbj = subj::where('id', $sbid)->first();
    //                 $clsSbj[] = $schSbj;
    //                 $relevantSubjects[] = $sbid;
    //             }
    //         }

    //         $subjectScores = [];
    //         foreach ($mySbjs as $sbid) {
    //             $subjectScores[$sbid] = [];
    //         }

    //         $scores = [];
    //         foreach ($allScores as $scr) {
    //             $sbid = $scr->sbj;
    //             $subjectScores[$sbid][] = $scr;
    //         }

    //         $positions = [];
    //         foreach ($mySbjs as $sbid) {
    //             $scores[] = [
    //                 'sbid' => $sbid,
    //                 'scores' => $subjectScores[$sbid]
    //             ];
    //             $subjectPosition = student_sub_res::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsid)
    //                 ->where("clsa", $arm)
    //                 ->first();

    //             $positions[] = [
    //                 'sbid' => $sbid,
    //                 'pos' => $subjectPosition ? $subjectPosition->pos : null,
    //             ];
    //         }

    //         $psyexist = student_psy::where([
    //             ['schid', $schid],
    //             ['ssn', $ssn],
    //             ['trm', $trm],
    //             ['clsm', $clsid],
    //             ['stid', $user_id]
    //         ])->exists();

    //         $resexist = $res->stat ?? "0";

    //         // âœ… Per-student attendance calculation
    //         $presentCount = \DB::table('attendances')
    //             ->where('schid', $schid)
    //             ->where('ssn', $ssn)
    //             ->where('trm', $trm)
    //             ->where('clsm', $clsid)
    //             ->where('clsa', $arm)
    //             ->where('sid', $user_id)
    //             ->where('status', 1)
    //             ->count();

    //         $absentCount = max(0, $nof - $presentCount);

    //         $studentres = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //             'scr' => $scores,
    //             'psy' => $psyexist,
    //             'res' => $resexist,
    //         ];

    //         $cstds[] = [
    //             's' => $member,
    //             'b' => $basicData,
    //             'a' => $academicData,
    //             'p' => $psy,
    //             'r' => $res,
    //             'rs' => $studentres,
    //             'cnt' => $totalStd,
    //             'spos' => $positions,
    //             'present_days' => $presentCount,
    //             'absent_days' => $absentCount,
    //         ];
    //     }

    //     $pld = [
    //         'std-pld' => $cstds,
    //         'cls-sbj' => $clsSbj,
    //         'num_of_days' => $nof,
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" =>  $pld,
    //     ]);
    // }





    /**
     * @OA\Post(
     *     path="/api/setStudentScore",
     *     tags={"Api"},
     *     summary="Set a student score. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a student score.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="scr", type="integer"),
     *             @OA\Property(property="sbj", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *             @OA\Property(property="aid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setStudentScore(Request $request)
    {
        $request->validate([
            'stid' => 'required',
            'scr' => 'required',
            'sbj' => 'required',
            'schid' => 'required',
            'clsid' => 'required',
            'ssn' => 'required',
            'trm' => 'required',
            'aid' => 'required',
        ]);
        $data = [
            'stid' => $request->stid,
            'scr' => $request->scr,
            'sbj' => $request->sbj,
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'ssn' => $request->ssn,
            'trm' => $request->trm,
            'aid' => $request->aid,
        ];
        $std_score = [];
        if ($request->has('id')) {
            $std_score = std_score::where('id', $request->id)->first();
            if ($std_score) {
                $std_score->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Score Not Found",
                ]);
            }
        } else {
            $std_score = std_score::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $std_score
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/studentHasExamRecord/{schid}/{clsid}/{ssn}/{trm}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Check if a student has any exam record for this session/term",
     *     description="Use this endpoint to Check if a student has any exam record for this session/term",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),@OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function studentHasExamRecord($schid, $clsid, $ssn, $trm, $stid)
    {
        $pld = std_score::where("stid", $stid)->where("schid", $schid)->where("clsid", $clsid)->where("ssn", $ssn)->where("trm", $trm)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setClassGrade",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set class grade",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="grd", type="string"),
     *             @OA\Property(property="g0", type="string"),
     *             @OA\Property(property="g1", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="class data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setClassGrade(Request $request)
    {
        //Data validation
        $request->validate([
            "grd" => "required",
            "g0" => "required",
            "g1" => "required",
            "schid" => "required",
            "clsid" => "required",
            "ssn" => "required",
            "trm" => "required",
        ]);
        $uid = $request->schid . $request->clsid . $request->ssn . $request->trm . $request->grd;
        sch_grade::updateOrCreate(
            ["uid" => $uid,],
            [
                "grd" => $request->grd,
                "g0" => $request->g0,
                "g1" => $request->g1,
                "schid" => $request->schid,
                "clsid" => $request->clsid,
                "ssn" => $request->ssn,
                "trm" => $request->trm,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getClassGrades/{schid}/{clsid}/{ssn}/{trm}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a class's grades",
     *     description="Use this endpoint to get grades of a class.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="string")
     *     ),@OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassGrades($schid, $clsid, $ssn, $trm)
    {
        $pld = sch_grade::where("schid", $schid)->where("clsid", $clsid)->where("ssn", $ssn)->where("trm", $trm)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStudent",
     *     tags={"Api"},
     *
     *     summary="Get a student",
     *     description="Use this endpoint to get a student.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="query",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="combined",
     *         in="query",
     *         required=false,
     *         description="should be combined?",
     *         @OA\Schema(type="boolean")
     *     ),
     *      @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudent()
    {
        $combined = false;
        if (request()->has('combined')) {
            $combined = request()->input('combined');
        }
        $uid = '';
        if (request()->has('uid')) {
            $uid = request()->input('uid');
        }
        $schid = '';
        if (request()->has('schid')) {
            $schid = request()->input('schid');
        }
        if ($uid == '' || $schid == '') {
            return response()->json([
                "status" => false,
                "message" => "No UID/School ID provided",
            ], 400);
        }
        $pld = [];
        if ($combined) {
            $members = [];
            $compo = explode("/", $uid);
            if (count($compo) == 4) {
                $sch3 = $compo[0];
                $year = $compo[1];
                $term = $compo[2];
                $count = $compo[3];
                $members = student::where("schid", $schid)->where("stat", "1")->where("sch3", $sch3)->where("year", $year)->where("term", $term)
                    ->where("count", $count)->get();
            } else {
                $members = student::where("schid", $schid)->where("stat", "1")->where("cuid", $uid)->get();
            }
            foreach ($members as $member) {
                $user_id = $member->sid;
                $academicData = student_academic_data::where('user_id', $user_id)->first();
                $basicData = student_basic_data::where('user_id', $user_id)->first();
                $pld[] = [
                    's' => $member,
                    'b' => $basicData,
                    'a' => $academicData,
                ];
            }
        } else {
            $pld = student::where("schid", $schid)->where("stat", "1")->where("sid", $uid)->first();
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStudentMedicalInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set medical info about a student",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="hospital", type="string"),
     *             @OA\Property(property="blood", type="string"),
     *             @OA\Property(property="geno", type="string"),
     *             @OA\Property(property="hiv", type="string"),
     *             @OA\Property(property="malaria", type="string"),
     *             @OA\Property(property="typha", type="string"),
     *             @OA\Property(property="tb", type="string"),
     *             @OA\Property(property="heart", type="string"),
     *             @OA\Property(property="liver", type="string"),
     *             @OA\Property(property="vdrl", type="string"),
     *             @OA\Property(property="hbp", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStudentMedicalInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "hospital" => "required",
            "blood" => "required",
            "geno" => "required",
            "hiv" => "required",
            "malaria" => "required",
            "typha" => "required",
            "tb" => "required",
            "heart" => "required",
            "liver" => "required",
            "vdrl" => "required",
            "hbp" => "required",
        ]);
        student_medical_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "hospital" => $request->hospital,
                "blood" => $request->blood,
                "geno" => $request->geno,
                "hiv" => $request->hiv,
                "malaria" => $request->malaria,
                "typha" => $request->typha,
                "tb" => $request->tb,
                "heart" => $request->heart,
                "liver" => $request->liver,
                "vdrl" => $request->vdrl,
                "hbp" => $request->hbp,
            ]
        );
        student::where('sid', $request->user_id)->update([
            "s_medical" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStudentMedicalInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's Medical Info",
     *     description="Use this endpoint to get medical information about a student.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentMedicalInfo($uid)
    {
        $pld = student_medical_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/setStudentParentInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set parent/guardian info about a student",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="sex", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="eml", type="string"),
     *             @OA\Property(property="relation", type="string"),
     *             @OA\Property(property="job", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="origin", type="string"),
     *             @OA\Property(property="residence", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStudentParentInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "fname" => "required",
            "lname" => "required",
            "mname" => "required",
            "sex" => "required",
            "phn" => "required",
            "eml" => "required",
            "relation" => "required",
            "job" => "required",
            "addr" => "required",
            "origin" => "required",
            "residence" => "required",
        ]);
        student_parent_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "fname" => $request->fname,
                "lname" => $request->lname,
                "mname" => $request->mname,
                "sex" => $request->sex,
                "phn" => $request->phn,
                "eml" => $request->eml,
                "relation" => $request->relation,
                "job" => $request->job,
                "addr" => $request->addr,
                "origin" => $request->origin,
                "residence" => $request->residence,
            ]
        );
        student::where('sid', $request->user_id)->update([
            "s_parent" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStudentParentInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's Medical Info",
     *     description="Use this endpoint to get medical information about a student.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentParentInfo($uid)
    {
        $pld = student_parent_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/setStudentAcademicInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set academic info about a student. Please pass an id for the classes as retrieved from getClasses. Dont just pass plain like JSS 1",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="last_school", type="string"),
     *             @OA\Property(property="last_class", type="string"),
     *             @OA\Property(property="new_class", type="string"),
     *             @OA\Property(property="new_class_main", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="suid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStudentAcademicInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "schid" => "required",
            "last_school" => "required",
            "last_class" => "required",
            "new_class" => "required",
            "new_class_main" => "required",
            "ssn" => "required",
            "trm" => "required",
            "suid" => "required",
        ]);
        $refreshSubjects = false;
        $oldData = student_academic_data::where('user_id', $request->user_id)->first();
        if ($oldData) {
            $refreshSubjects = $oldData->new_class_main != $request->new_class_main;
        } else {
            $refreshSubjects = true;
        }
        student_academic_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "last_school" => $request->last_school,
                "last_class" => $request->last_class,
                "new_class" => $request->new_class,
                "new_class_main" => $request->new_class_main,
            ]
        );
        if ($refreshSubjects) { //Delete all subjs and set new, comps ones
            student_subj::where('stid', $request->user_id)->delete();
            // $schid = $request->schid;
            // $clsid = $request->new_class_main;
            // $members = class_subj::where("schid", $schid)->where("clsid", $clsid)->where("comp", '1')->get();
            // $pld = [];
            // foreach ($members as $member) {
            //     $sbj = $member->subj_id;
            //     $stid = $request->user_id;
            //     student_subj::updateOrCreate(
            //         ["uid"=> $sbj.$stid],
            //         [
            //         "stid"=> $stid,
            //         "sbj"=> $sbj,
            //         "comp"=> $member->comp,
            //         "schid"=> $member->schid,
            //     ]);
            // }
        }
        $std = student::where('sid', $request->user_id)->first();
        if ($request->new_class != 'NIL') { //Class Arm Specified
            //--- RECORD IN OLD DATA SO DATA SHOWS UP IN CLASS DIST.
            $uid = $request->ssn . $request->user_id;
            old_student::updateOrCreate(
                ["uid" => $uid,],
                [
                    'sid' => $request->user_id,
                    'schid' => $request->schid,
                    'fname' => $std->fname,
                    'mname' => $std->mname,
                    'lname' => $std->lname,
                    'suid' => $request->suid,
                    'ssn' => $request->ssn,
                    'trm' => $request->trm,
                    'clsm' => $request->new_class_main,
                    'clsa' => $request->new_class,
                    'more' => "",
                ]
            );
        }
        $std->update([
            "s_academic" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStudentAcademicInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's Academic Info",
     *     description="Use this endpoint to get academic information about a student.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentAcademicInfo($uid)
    {
        $pld = student_academic_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setOldStudentInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set info about an old student data. Specify id if you wish to update",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="sid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="suid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="clsm", type="string"),
     *             @OA\Property(property="clsa", type="string"),
     *             @OA\Property(property="more", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student old data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setOldStudentInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "sid" => "required",
            "schid" => "required",
            "fname" => "required",
            "mname" => "required",
            "lname" => "required",
            "suid" => "required",
            "ssn" => "required",
            "clsm" => "required",
            "clsa" => "required",
            "more" => "required",
        ]);
        old_student::updateOrCreate(
            ["uid" => $request->uid,],
            [
                'sid' => $request->sid,
                'schid' => $request->schid,
                'fname' => $request->fname,
                'mname' => $request->mname,
                'lname' => $request->lname,
                'suid' => $request->suid,
                'ssn' => $request->ssn,
                'clsm' => $request->clsm,
                'clsa' => $request->clsa,
                'more' => $request->more,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Info Updated"
        ]);
    }


    // public function getOldStudents($schid, $ssn, $clsm, $clsa){
    //     $pld = [];
    //     if($clsa=='-1'){
    //         $pld = old_student::where("schid", $schid)->where("ssn", $ssn)->where("clsm", $clsm)->get();
    //     }else{
    //         $pld = old_student::where("schid", $schid)->where("ssn", $ssn)->where("clsm", $clsm)->where("clsa", $clsa)->get();
    //     }
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Success",
    //         "pld"=> $pld,
    //     ]);
    // }






    /**
     * @OA\Get(
     *     path="/api/getOldStudents/{schid}/{ssn}/{trm}/{clsm}/{clsa}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's Basic Info",
     *     description="Use this endpoint to get basic information about an old student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getOldStudents($schid, $ssn, $trm, $clsm, $clsa)
    // {
    //     $pld = [];
    //     if ($clsa == '-1') {
    //         $pld = old_student::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)->where("status", "active")->get();
    //     } else {
    //         $pld = old_student::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)->where("status", "active")->where("clsa", $clsa)->get();
    //     }
    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }

    public function getOldStudents($schid, $ssn, $trm, $clsm, $clsa)
    {
        $query = old_student::leftJoin('student_academic_data', 'old_student.sid', '=', 'student_academic_data.user_id')
            ->where("old_student.schid", $schid)
            ->where("old_student.ssn", $ssn)
            ->where("old_student.trm", $trm)
            ->where("old_student.clsm", $clsm)
            ->where("old_student.status", "active");

        if ($clsa != '-1') {
            $query->where("old_student.clsa", $clsa);
        }

        $pld = $query->select(
            'old_student.*',
            'student_academic_data.last_class'
        )
            ->get();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }





    /**
     * @OA\Get(
     *     path="/api/getOldStudent/{schid}/{ssn}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's Basic Info",
     *     description="Use this endpoint to get basic information about an old student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getOldStudent($schid, $ssn, $stid)
    {
        $pld = [];
        $pld = old_student::where("schid", $schid)->where("ssn", $ssn)->where("sid", $stid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getOldStudentsStat/{schid}/{ssn}/{clsm}/{clsa}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's stats",
     *     description="Use this endpoint to get stats information about an old student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function getOldStudentsStat($schid, $ssn, $clsm, $clsa)
    {
        $male = 0;
        $female = 0;
        if ($clsa == '-1') {
            $male = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
                ->where('old_student.schid', $schid)
                ->where('old_student.ssn', $ssn)
                ->where('status', 'active')
                ->where('old_student.clsm', $clsm)
                ->where('student_basic_data.sex', 'M')
                ->count();
            $female = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
                ->where('old_student.schid', $schid)
                ->where('old_student.ssn', $ssn)
                ->where('status', 'active')
                ->where('old_student.clsm', $clsm)
                ->where('student_basic_data.sex', 'F')
                ->count();
        } else {
            $male = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
                ->where('old_student.schid', $schid)
                ->where('old_student.ssn', $ssn)
                ->where('old_student.clsm', $clsm)
                ->where('status', 'active')
                ->where('old_student.clsa', $clsa)
                ->where('student_basic_data.sex', 'M')
                ->count();
            $female = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
                ->where('old_student.schid', $schid)
                ->where('old_student.ssn', $ssn)
                ->where('old_student.clsm', $clsm)
                ->where('status', 'active')
                ->where('old_student.clsa', $clsa)
                ->where('student_basic_data.sex', 'F')
                ->count();
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "male" => $male,
                "female" => $female,
            ]
        ]);
    }



    // public function getOldStudentsStat($schid, $ssn, $clsm, $clsa)
    // {
    //     $male = 0;
    //     $female = 0;
    //     if ($clsa == '-1') {
    //         $male = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
    //             ->where('old_student.schid', $schid)
    //             ->where('old_student.ssn', $ssn)
    //             ->where('status', 'active')
    //             ->where('old_student.clsm', $clsm)
    //             ->where('student_basic_data.sex', 'M')
    //             ->count();
    //         $female = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
    //             ->where('old_student.schid', $schid)
    //             ->where('old_student.ssn', $ssn)
    //             ->where('status', 'active')
    //             ->where('old_student.clsm', $clsm)
    //             ->where('student_basic_data.sex', 'F')
    //             ->count();
    //     } else {
    //         $male = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
    //             ->where('old_student.schid', $schid)
    //             ->where('old_student.ssn', $ssn)
    //             ->where('old_student.clsm', $clsm)
    //             ->where('status', 'active')
    //             ->where('old_student.clsa', $clsa)
    //             ->where('student_basic_data.sex', 'M')
    //             ->count();
    //         $female = old_student::join('student_basic_data', 'old_student.sid', '=', 'student_basic_data.user_id')
    //             ->where('old_student.schid', $schid)
    //             ->where('old_student.ssn', $ssn)
    //             ->where('old_student.clsm', $clsm)
    //             ->where('status', 'active')
    //             ->where('old_student.clsa', $clsa)
    //             ->where('student_basic_data.sex', 'F')
    //             ->count();
    //     }

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => [
    //             "male" => $male,
    //             "female" => $female,
    //         ]
    //     ]);
    // }




    /**
     * @OA\Get(
     *     path="/api/getOldStudentInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's Basic Info",
     *     description="Use this endpoint to get basic information about an old student.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="uid of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getOldStudentInfo($uid)
    {
        $pld = old_student::where("uid", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStudentPsy",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set info about an student psychometry. Specify id if you wish to update",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="punc", type="string"),
     *             @OA\Property(property="hon", type="string"),
     *             @OA\Property(property="pol", type="string"),
     *             @OA\Property(property="neat", type="string"),
     *             @OA\Property(property="pers", type="string"),
     *             @OA\Property(property="rel", type="string"),
     *             @OA\Property(property="dil", type="string"),
     *             @OA\Property(property="cre", type="string"),
     *             @OA\Property(property="pat", type="string"),
     *             @OA\Property(property="verb", type="string"),
     *             @OA\Property(property="gam", type="string"),
     *             @OA\Property(property="musc", type="string"),
     *             @OA\Property(property="drw", type="string"),
     *             @OA\Property(property="wrt", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *             @OA\Property(property="clsm", type="string"),
     *             @OA\Property(property="clsa", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student old data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStudentPsy(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "punc" => "required",
            "hon" => "required",
            "pol" => "required",
            "neat" => "required",
            "pers" => "required",
            "rel" => "required",
            "dil" => "required",
            "cre" => "required",
            "pat" => "required",
            "verb" => "required",
            "gam" => "required",
            "musc" => "required",
            "drw" => "required",
            "wrt" => "required",
            "stid" => "required",
            "schid" => "required",
            "ssn" => "required",
            "trm" => "required",
            "clsm" => "required",
            "clsa" => "required",
        ]);
        student_psy::updateOrCreate(
            ["uid" => $request->uid,],
            [
                'punc' => $request->punc,
                'hon' => $request->hon,
                'pol' => $request->pol,
                'neat' => $request->neat,
                'pers' => $request->pers,
                'rel' => $request->rel,
                'dil' => $request->dil,
                'cre' => $request->cre,
                'pat' => $request->pat,
                'verb' => $request->verb,
                'gam' => $request->gam,
                'musc' => $request->musc,
                'drw' => $request->drw,
                'wrt' => $request->wrt,
                'stid' => $request->stid,
                'schid' => $request->schid,
                'ssn' => $request->ssn,
                'trm' => $request->trm,
                'clsm' => $request->clsm,
                'clsa' => $request->clsa,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Info Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStudentPsy/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's psychometry",
     *     description="Use this endpoint to get ...",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Id of the term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentPsy($schid, $ssn, $trm, $clsm, $clsa, $stid)
    {
        $pld = student_psy::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)->where("clsa", $clsa)->where("stid", $stid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }





    //////////////////////////////////////////////////////////////////////////////////////////////////



    /**
     * @OA\Post(
     *     path="/api/setStudentRes",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set info about an student result. Specify id if you wish to update",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *             @OA\Property(property="com", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *             @OA\Property(property="clsm", type="string"),
     *             @OA\Property(property="clsa", type="string"),
     *             @OA\Property(property="pos", type="string"),
     *             @OA\Property(property="avg", type="string"),
     *             @OA\Property(property="cavg", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student old data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */

    // public function setStudentRes(Request $request){
    //     //Data validation
    //     $request->validate([
    //         "uid"=> "required",
    //         "stat"=> "required",
    //         "com" => "required",
    //         "stid" => "required",
    //         "schid" => "required",
    //         "ssn" => "required",
    //         "trm" => "required",
    //         "clsm" => "required",
    //         "clsa" => "required",
    //         "pos" => "numeric",
    //         'avg' => 'numeric|min:0|max:100',
    //         'cavg' => 'numeric|min:0|max:100',
    //     ]);
    //     student_res::updateOrCreate(
    //         ["uid"=> $request->uid,],
    //         [
    //         'stat' => $request->stat,
    //         'com' => $request->com,
    //         'stid' => $request->stid,
    //         'schid' => $request->schid,
    //         'ssn' => $request->ssn,
    //         'trm' => $request->trm,
    //         'clsm' => $request->clsm,
    //         'clsa' => $request->clsa,
    //         'pos' => $request->pos,
    //         'avg' => $request->avg,
    //         'cavg' => $request->cavg,
    //     ]);
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Info Updated"
    //     ]);
    // }



    public function setStudentRes(Request $request)
    {
        // Retrieve the authenticated user
        $user = auth()->user();

        // Allow School Admin (typ = 'a' or 's') to add comments
        if (in_array($user->typ, ['a', 's'])) {
            return $this->saveStudentRes($request);
        }

        // Find the corresponding staff record
        $staff = staff::where('sid', $user->id)->first();

        if (!$staff) {
            return response()->json([
                "status" => false,
                "message" => "Unauthorized: Staff not found."
            ], 403);
        }

        // Retrieve the role names from the staff_role table
        $roleNames = staff_role::whereIn('id', [$staff->role, $staff->role2])
            ->pluck('name')
            ->toArray();

        // Allow Form Teachers, Head Teacher, Class Teachers, and Principals to comment
        $allowedRoles = ['Form Teacher', 'Head Teacher', 'Class Teacher', 'Principal'];

        if (!array_intersect($allowedRoles, $roleNames)) {
            return response()->json([
                "status" => false,
                "message" => "Unauthorized: Only Form Teachers, Head Teacher, Class Teachers, Principals, or Admins can add comments."
            ], 403);
        }

        // If the staff is a Class Teacher, ensure they are assigned to the correct class arm
        if (in_array('Class Teacher', $roleNames)) {
            $assignedClassArm = staff_class_arm::where('stid', $staff->sid)
                ->where('cls', $request->clsm) // Ensure same class
                ->where('arm', $request->clsa) // Ensure same class arm
                ->exists();

            if (!$assignedClassArm) {
                return response()->json([
                    "status" => false,
                    "message" => "Unauthorized: You are not assigned to this class arm."
                ], 403);
            }
        }

        return $this->saveStudentRes($request);
    }


    /**
     * Save or update student results
     */
    private function saveStudentRes(Request $request)
    {
        $request->validate([
            "uid" => "required",
            "stat" => "required",
            "com" => "required",
            "stid" => "required",
            "schid" => "required",
            "ssn" => "required",
            "trm" => "required",
            "clsm" => "required",
            "clsa" => "required",
            "pos" => "numeric",
            'avg' => 'numeric|min:0|max:100',
            'cavg' => 'numeric|min:0|max:100',
        ]);

        student_res::updateOrCreate(
            [
                'uid' => $request->uid, // only match on UID, don't update it
            ],
            [
                'stat' => $request->stat,
                'com' => $request->com,
                'stid' => $request->stid,
                'schid' => $request->schid,
                'ssn' => $request->ssn,
                'trm' => $request->trm,
                'clsm' => $request->clsm,
                'clsa' => $request->clsa,
                'pos' => $request->pos,
                'avg' => $request->avg,
                'cavg' => $request->cavg,
            ]
        );

        return response()->json([
            "status" => true,
            "message" => "Info Updated"
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getStudentRes/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's psychometry",
     *     description="Use this endpoint to get ...",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Id of the term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentRes($schid, $ssn, $trm, $clsm, $clsa, $stid)
    {
        $pld = student_res::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)->where("clsa", $clsa)->where("stid", $stid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStudentSubjPos",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set info about an student subject position.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uidprfx", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *             @OA\Property(property="clsm", type="string"),
     *             @OA\Property(property="clsa", type="string"),
     *             @OA\Property(property="sbjpos", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student old data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStudentSubjPos(Request $request)
    {
        //Data validation
        $request->validate([
            "uidprfx" => "required",
            "stid" => "required",
            "schid" => "required",
            "ssn" => "required",
            "trm" => "required",
            "clsm" => "required",
            "clsa" => "required",
            "sbjpos" => "required",
        ]);
        $sbjPosCombo = explode('~', $request->sbjpos);
        foreach ($sbjPosCombo as $spc) {
            if (strlen($spc) > 2) {
                $meta = explode('-', $spc);
                $sbid = $meta[0];
                $sbPos = $meta[1];
                student_sub_res::updateOrCreate(
                    ["uid" => $request->uidprfx . $sbid,],
                    [
                        "stid" => $request->stid,
                        "schid" => $request->schid,
                        "ssn" => $request->ssn,
                        "trm" => $request->trm,
                        "clsm" => $request->clsm,
                        "clsa" => $request->clsa,
                        "sbj" => $sbid,
                        "pos" => $sbPos,
                    ]
                );
            }
        }
        return response()->json([
            "status" => true,
            "message" => "Info Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStudentSubjPos/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's Subject Positions",
     *     description="Use this endpoint to get a student's Subject Positions",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentSubjPos($schid, $ssn, $trm, $clsm, $clsa, $stid)
    {
        $studentSubjects = student_subj::join('class_subj', 'student_subj.sbj', '=', 'class_subj.subj_id')
            ->where('class_subj.schid', $schid)
            ->where('class_subj.clsid', $clsm)
            ->where('student_subj.stid', $stid)
            ->get();
        $positions = [];
        foreach ($studentSubjects as $sbj) {
            $sbid = $sbj->sbj;
            $subjectPosition = student_sub_res::where('stid', $stid)->where('sbj', $sbid)
                ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)
                ->where("clsa", $clsa)->first();
            if ($subjectPosition) {
                $positions[] = [
                    'sbid' => $sbid,
                    'pos' => $subjectPosition->pos,
                ];
            }
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $positions,
        ]);
    }


    //---STAFF

    /**
     * @OA\Post(
     *     path="/api/registerStaff",
     *     tags={"Unprotected"},
     *     summary="Register a new staff for a school",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string", description="School ID which this staff belongs"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="sch3", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="password", type="string", description="The password for the staff"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */

    public function registerStaff(Request $request)
    {
        //Data validation
        $request->validate([
            "schid" => "required",
            "email" => "required|email|unique:users,email",
            "password" => "required",
            "fname" => "required",
            "lname" => "required",
            "sch3" => "required",
            "stat" => "required",
            "role" => "required",
        ]);
        if (strlen($request->password) < 6) {
            return response()->json([
                "status" => false,
                "message" => "Password must be at least 6 char",
            ], 400);
        }
        $typ = 'w';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if (!$usr) {
            $usr = User::create([
                "email" => $request->email,
                "typ" => $typ,
                "verif" => $request->stat,
                "password" => bcrypt($request->password),
            ]);
            $count = $request->count;
            if (!$count) {
                $count = staff::where('schid', $request->schid)->count() + 1;
            }
            staff::create([
                "sid" => strval($usr->id),
                "schid" => $request->schid,
                "fname" => $request->fname,
                "mname" => $request->mname,
                "lname" => $request->lname,
                "count" => strval($count),
                "sch3" => $request->sch3,
                "stat" => $request->stat,
                "cuid" => $request->cuid,
                "role" => "*" . $request->role,
                "role2" => '-1',
                "s_basic" => '0',
                "s_prof" => '0',
            ]);
            $sid = $request->sch3 . '/' . 'STAFF' . '/' . strval($count);
            // Wrap the email sending logic in a try-catch block
            try {
                if ($request->cuid) {
                    $data = [
                        'name' => $request->fname,
                        'subject' => 'Welcome Back, Your ID remains ' . $request->cuid,
                        'body' => "Welcome back to your school's platform. Your account was created successfully. If you havent already, please login to your dashboard using the link below and complete your staff profile. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. Your Staff ID is " . $request->cuid,
                        'link' => env('PORTAL_URL') . '/staffLogin' . '/' . $request->schid,
                    ];
                    Mail::to($request->email)->send(new SSSMails($data));
                } else {
                    $data = [
                        'name' => $request->fname,
                        'subject' => 'Welcome, Your ID is ' . $sid,
                        'body' => "Welcome to your school's platform. Your account was created successfully. If you havent already, please login to your dashboard using the link below and complete your staff profile. If the link isnt clickable, please copy the link to your browser. If this arrived in spam folder, please mark as Not Spam. Your Staff ID is " . $sid,
                        'link' => env('PORTAL_URL') . '/staffLogin' . '/' . $request->schid,
                    ];
                    Mail::to($request->email)->send(new SSSMails($data));
                }
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }
            // Respond
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            return response()->json([
                "status" => true,
                "message" => "User created successfully",
                "token" => $token,
                "sid" => $sid,
                "user_id" => strval($usr->id)
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Account already exists",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/staffLoginByEmail",
     *     tags={"Unprotected"},
     *     summary="Staff Login to the application by Email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="your-jwt-token-here", description="This will contain a JWT token that must be passed with consequent request using bearer token"),
     *         )
     *     ),
     * )
     */
    public function staffLoginByEmail(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
        $typ = 'w';
        $usr = User::where("typ", $typ)->where("email", $request->email)->first();
        if ($usr) {
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                $stf = staff::where('sid', strval($usr->id))->first();
                return response()->json([
                    "status" => true,
                    "message" => "Login successful",
                    "token" => $token,
                    "pld" => $usr,
                    "std" => $stf,
                ]);
            }
        }
        // Respond
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/staffLoginByID",
     *     tags={"Unprotected"},
     *     summary="Staff Login to the application",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="your-jwt-token-here", description="This will contain a JWT token that must be passed with consequent request using bearer token"),
     *         )
     *     ),
     * )
     */
    public function staffLoginByID(Request $request)
    {
        //Data validation
        $request->validate([
            "stid" => "required",
            "schid" => "required",
            "password" => "required",
        ]);
        $typ = 'w';
        $stf = [];
        $compo = explode("/", $request->stid);
        if (count($compo) == 3) {
            $sch3 = $compo[0];
            $count = $compo[2];
            $stf = staff::where("schid", $request->schid)->where("count", $count)->where("status", "active")->first();
        } else {
            $stf = staff::where("cuid", $request->stid)->where("status", "active")->first();
        }
        if ($stf) {
            $usr = User::where("typ", $typ)->where("id", $stf->sid)->first();
            $token = JWTAuth::attempt([
                "email" => $usr->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                return response()->json([
                    "status" => true,
                    "message" => "Login successful",
                    "token" => $token,
                    "pld" => $usr,
                ]);
            }
        }
        // Respond
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/admitStaff",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="admit/reject a staff",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="role2", type="string"),
     *             @OA\Property(property="role_name", type="string"),
     *             @OA\Property(property="role2_name", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Student data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function admitStaff(Request $request)
    {
        //Data validation
        $request->validate([
            "stid" => "required",
            "stat" => "required",
            "role" => "required",
            "role2" => "required",
            "role_name" => "required",
            "role2_name" => "required",
            "schid" => "required",
        ]);
        $stf = staff::where('sid', $request->stid)->first();
        if ($stf) {
            $stf->update([
                "stat" => $request->stat,
                "role" => $request->role,
                "role2" => $request->role2,
            ]);
            $usr = User::where('id', $stf->sid)->first();
            // Wrap the email sending logic in a try-catch block
            try {
                $data = [
                    'name' => $stf->fname,
                    'subject' => 'Application ' . ($request->stat == '1' ? 'Approved' : 'Decline'),
                    'body' => "Your application to our school as a " . $request->role_name . " has been " . ($request->stat == '1' ? 'approved' : 'decline') . ($request->role2 != '-1' ? '. We also assigned you the role: ' . $request->role2_name : '.'),
                    'link' => env('PORTAL_URL') . '/staffLogin' . '/' . $request->schid,
                ];
                Mail::to($usr->email)->send(new SSSMails($data));
            } catch (\Exception $e) {
                // Log the email error, but don't stop the process
                Log::error('Failed to send email: ' . $e->getMessage());
            }

            return response()->json([
                "status" => true,
                "message" => "Success",
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Student Not Found",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/setStaffBasicInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set basic info about a staff data. Pass 1/0 for stat depending of if application is approved/now",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="dob", type="string"),
     *             @OA\Property(property="sex", type="string"),
     *             @OA\Property(property="town", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="lga", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="kin_name", type="string"),
     *             @OA\Property(property="kin_phn", type="string"),
     *             @OA\Property(property="kin_relation", type="string"),
     *             @OA\Property(property="stat", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="staff data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStaffBasicInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            "dob" => "required",
            "sex" => "required",
            "town" => "required",
            "country" => "required",
            "state" => "required",
            "lga" => "required",
            "addr" => "required",
            "phn" => "required",
            "kin_name" => "required",
            "kin_phn" => "required",
            "kin_relation" => "required",
        ]);
        staff_basic_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "dob" => $request->dob,
                "sex" => $request->sex,
                "town" => $request->town,
                "country" => $request->country,
                "state" => $request->state,
                "lga" => $request->lga,
                "addr" => $request->addr,
                "phn" => $request->phn,
                "kin_name" => $request->kin_name,
                "kin_phn" => $request->kin_phn,
                "kin_relation" => $request->kin_relation,
            ]
        );
        staff::where('sid', $request->user_id)->update([
            "s_basic" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStaffBasicInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff's Basic Info",
     *     description="Use this endpoint to get basic information about a staff.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffBasicInfo($uid)
    {
        $pld = staff_basic_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStaffProfInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set professional info about a staff data. Pass 1/0 for stat depending of if application is approved/now",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="string"),
     *             @OA\Property(property="grad_date", type="string"),
     *             @OA\Property(property="univ", type="string"),
     *             @OA\Property(property="area", type="string"),
     *             @OA\Property(property="qual", type="string"),
     *             @OA\Property(property="trcn", type="string"),
     *             @OA\Property(property="hqual", type="string"),
     *             @OA\Property(property="place_first_appt", type="string"),
     *             @OA\Property(property="last_employment", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="staff data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStaffProfInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "user_id" => "required",
            //"grad_date" => "nullable",
            // "univ" => "nullable",
            "area" => "required",
            // "qual" => "nullable",
            // "trcn" => "nullable",
            // "hqual" => "nullable",
            "place_first_appt" => "required",
            "last_employment" => "required",
        ]);
        staff_prof_data::updateOrCreate(
            ["user_id" => $request->user_id,],
            [
                "grad_date" => $request->grad_date,
                "univ" => $request->univ,
                "area" => $request->area,
                "qual" => $request->qual,
                "trcn" => $request->trcn,
                "hqual" => $request->hqual,
                "place_first_appt" => $request->place_first_appt,
                "last_employment" => $request->last_employment,
            ]
        );
        staff::where('sid', $request->user_id)->update([
            "s_prof" => '1'
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStaffProfInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff's Professional Info",
     *     description="Use this endpoint to get professional information about a staff.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffProfInfo($uid)
    {
        $pld = staff_prof_data::where("user_id", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStaffSubject",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set staff subjects",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="sbj", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Staff data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStaffSubject(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "stid" => "required",
            "sbj" => "required",
            "schid" => "required",
        ]);
        staff_subj::updateOrCreate(
            ["uid" => $request->uid,],
            [
                "stid" => $request->stid,
                "sbj" => $request->sbj,
                "schid" => $request->schid,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getOldStudentsAndSubject/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's Basic Info",
     *     description="Use this endpoint to get basic information about an old student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stf",
     *         in="path",
     *         required=true,
     *         description="Staff ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    // public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];

    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     $relevantSubjects = [];
    //     if ($stf == "-1" || $stf == "-2") {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj');
    //     } else {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');
    //     }

    //     $stdPld = [];

    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $studentSubjects = student_subj::where('stid', $user_id)
    //             ->whereIn('sbj', $relevantSubjects)
    //             ->get();

    //         $mySbjs = [];
    //         $scores = [];

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;

    //             // Fetch scores for this subject
    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where('schid', $schid)
    //                 ->where('ssn', $ssn)
    //                 ->where('trm', $trm)
    //                 ->where('clsid', $clsm)
    //                 ->get();

    //             if ($subjectScores->isEmpty()) {
    //                 // Subject was offered, but no score entered â€” use 0
    //                 $subjectScores = collect([
    //                     (object)[
    //                         'stid' => $user_id,
    //                         'sbj' => $sbid,
    //                         'scr' => 0,
    //                         'note' => 'Auto-generated zero score' // optional
    //                     ]
    //                 ]);
    //             }

    //             $mySbjs[] = $sbid;
    //             $scores[] = [
    //                 'sbid' => $sbid,
    //                 'scores' => $subjectScores
    //             ];

    //         }

    //         $psy = false;
    //         $res = "0";
    //         $rinfo = [];

    //         if ($stf == "-2") {
    //             $psy = student_psy::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->exists();

    //             $rinfo = student_res::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->first();

    //             if ($rinfo) {
    //                 $res = $rinfo->stat;
    //             }
    //         }

    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //             'scr' => $scores,
    //             'psy' => $psy,
    //             'res' => $res,
    //             'rinfo' => $rinfo,
    //         ];
    //     }

    //     // Get unique class subjects that are actually used
    //     $clsSbj = [];
    //     $temKeep = [];

    //     foreach ($relevantSubjects as $sbid) {
    //         if (!in_array($sbid, $temKeep)) {
    //             $temKeep[] = $sbid;
    //             $schSbj = subj::where('id', $sbid)->first();
    //             if ($schSbj) {
    //                 $clsSbj[] = $schSbj;
    //             }
    //         }
    //     }

    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj // Optional, keep if needed
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    ////////////////////////////////////////////////////////////////////////////////

    //     public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];

    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     $relevantSubjects = [];
    //     if ($stf == "-1" || $stf == "-2") {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj');
    //     } else {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');
    //     }

    //     $stdPld = [];

    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $studentSubjects = student_subj::where('stid', $user_id)
    //             ->whereIn('sbj', $relevantSubjects)
    //             ->get();

    //         $mySbjs = [];
    //         $scores = [];

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;

    //             // Fetch scores for this subject
    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where('schid', $schid)
    //                 ->where('ssn', $ssn)
    //                 ->where('trm', $trm)
    //                 ->where('clsid', $clsm)
    //                 ->get();

    //             // Exclude if all scores are 0 or null, only for admin
    //             if (($stf == "-1" || $stf == "-2") && $subjectScores->every(fn($s) => empty($s->scr) || $s->scr == 0)) {
    //                 continue;
    //             }

    //             // Include even if empty or all zero, for teachers or others
    //             $mySbjs[] = $sbid;
    //             $scores[] = [
    //                 'sbid' => $sbid,
    //                 'scores' => $subjectScores
    //             ];
    //         }

    //         $psy = false;
    //         $res = "0";
    //         $rinfo = [];

    //         if ($stf == "-2") {
    //             $psy = student_psy::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->exists();

    //             $rinfo = student_res::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->first();

    //             if ($rinfo) {
    //                 $res = $rinfo->stat;
    //             }
    //         }

    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //             'scr' => $scores,
    //             'psy' => $psy,
    //             'res' => $res,
    //             'rinfo' => $rinfo,
    //         ];
    //     }

    //     // Get unique class subjects that are actually used
    //     $clsSbj = [];
    //     $temKeep = [];

    //     foreach ($relevantSubjects as $sbid) {
    //         if (!in_array($sbid, $temKeep)) {
    //             $temKeep[] = $sbid;
    //             $schSbj = subj::where('id', $sbid)->first();
    //             if ($schSbj) {
    //                 $clsSbj[] = $schSbj;
    //             }
    //         }
    //     }

    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }




    public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    {
        // Fetch students based on class and arm
        if ($clsa == '-1') {
            $ostd = old_student::where("schid", $schid)
                ->where("status", "active")
                ->where("ssn", $ssn)
                ->where("clsm", $clsm)
                ->get();
        } else {
            $ostd = old_student::where("schid", $schid)
                ->where("status", "active")
                ->where("ssn", $ssn)
                ->where("clsm", $clsm)
                ->where("clsa", $clsa)
                ->get();
        }

        $stdPld = [];

        foreach ($ostd as $std) {
            $user_id = $std->sid;

            // Get all subjects assigned to the student
            $studentSubjects = student_subj::where('stid', $user_id)->get();

            $mySbjs = [];
            $scores = [];
            $totalScore = 0;
            $scoreCount = 0;

            foreach ($studentSubjects as $sbj) {
                // Fetch subject details
                $subject = subj::find($sbj->sbj);
                if ($subject) {
                    $mySbjs[] = $subject;
                }

                // Fetch scores for this subject
                $subjectScores = std_score::where('stid', $user_id)
                    ->where('sbj', $sbj->sbj)
                    ->where('schid', $schid)
                    ->where('ssn', $ssn)
                    ->where('trm', $trm)
                    ->where('clsid', $clsm)
                    ->get();

                foreach ($subjectScores as $s) {
                    if (!empty($s->scr) && is_numeric($s->scr)) {
                        $totalScore += $s->scr;
                        $scoreCount++;
                    }
                }

                $scores[] = [
                    'sbid' => $sbj->sbj,
                    'scores' => $subjectScores
                ];
            }

            $avgScore = $scoreCount > 0 ? round($totalScore / $scoreCount, 2) : 0;
            $grade = $this->gradeFromAvg2($avgScore);

            // Check for psychological evaluation if needed
            $psy = false;
            if ($stf == "-2") {
                $psy = student_psy::where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsm", $clsm)
                    ->where("stid", $user_id)
                    ->exists();
            }

            // Fetch student result info
            $res = "0";
            $rinfo = student_res::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsm", $clsm)
                ->where("clsa", $clsa)
                ->where("stid", $user_id)
                ->first();

            if ($rinfo) {
                $res = $rinfo->stat;
                $rinfo = [
                    'uid' => $rinfo->uid,
                    'stat' => $rinfo->stat,
                    'com' => $rinfo->com,
                    'stid' => $rinfo->stid,
                    'schid' => $rinfo->schid,
                    'ssn' => $rinfo->ssn,
                    'trm' => $rinfo->trm,
                    'clsm' => $rinfo->clsm,
                    'clsa' => $rinfo->clsa,
                    'pos' => $rinfo->pos,
                    'avg' => $rinfo->avg,
                    'cavg' => $rinfo->cavg,
                    'created_at' => $rinfo->created_at,
                    'updated_at' => $rinfo->updated_at,
                    'grade' => isset($rinfo->avg) ? $this->gradeFromAvg2($rinfo->avg) : null,
                ];
            } else {
                $rinfo = [];
            }

            // Build student payload
            $stdPld[] = [
                'std' => $std,
                'sbj' => $mySbjs,
                'scr' => $scores,
                'avg_score' => $avgScore,
                'grade' => $grade,
                'psy' => $psy,
                'res' => $res,
                'rinfo' => $rinfo,
            ];
        }

        // Prepare class subjects list
        $clsSbj = [];
        $temKeep = [];
        foreach ($studentSubjects as $sbj) {
            if (!in_array($sbj->sbj, $temKeep)) {
                $temKeep[] = $sbj->sbj;
                $schSbj = subj::find($sbj->sbj);
                if ($schSbj) {
                    $clsSbj[] = $schSbj;
                }
            }
        }

        $pld = [
            'std-pld' => $stdPld,
            'cls-sbj' => $clsSbj
        ];

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    private function gradeFromAvg2($avg)
    {
        if ($avg >= 70) return 'A';
        elseif ($avg >= 60) return 'B';
        elseif ($avg >= 50) return 'C';
        elseif ($avg >= 45) return 'D';
        elseif ($avg >= 40) return 'E';
        else return 'F';
    }





    //////////////////////////////////////////////////////////////////////////////////////

    public function getOldStudentsAndSubjectScoreSheet($schid, $ssn, $trm, $clsm, $clsa, $stf)
    {
        $ostd = [];
        if ($clsa == '-1') {
            $ostd = old_student::where("schid", $schid)->where("status", "active")->where("ssn", $ssn)->where("clsm", $clsm)->get();
        } else {
            $ostd = old_student::where("schid", $schid)->where("status", "active")->where("ssn", $ssn)->where("clsm", $clsm)->where("clsa", $clsa)->get();
        }
        $relevantSubjects = [];
        if ($stf == "-1" || $stf == "-2") {
            $relevantSubjects = class_subj:: //join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
                where('class_subj.schid', $schid)
                ->where('class_subj.clsid', $clsm)
                ->pluck('subj_id');
        } else {
            $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
                ->where('class_subj.schid', $schid)
                ->where('class_subj.clsid', $clsm)
                ->where('staff_subj.stid', $stf)
                ->pluck('sbj');
        }
        $stdPld = [];
        foreach ($ostd as $std) {
            $user_id = $std->sid;
            $studentSubjects = student_subj::where('stid', $user_id)->whereIn('sbj', $relevantSubjects)->get();
            $mySbjs = [];
            $scores = [];
            foreach ($studentSubjects as $sbj) {
                $sbid = $sbj->sbj;
                $mySbjs[] = $sbid;
                $subjectScores = std_score::where('stid', $user_id)->where('sbj', $sbid)
                    ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsid", $clsm)->get();
                $scores[] = [
                    'sbid' => $sbid,
                    'scores' => $subjectScores
                ];
            }
            $psy = false;
            $res = "0";
            $rinfo = [];
            if ($stf == "-2") {
                $psy = student_psy::where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsm", $clsm)
                    ->where("stid", $user_id)
                    ->exists();
                $rinfo = student_res::where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsm", $clsm)
                    ->where("stid", $user_id)
                    ->first();
                if ($rinfo) {
                    $res = $rinfo->stat;
                }
            }
            $stdPld[] = [
                'std' => $std,
                'sbj' => $mySbjs,
                'scr' => $scores,
                'psy' => $psy,
                'res' => $res,
                'rinfo' => $rinfo,
            ];
        }
        $clsSbj = [];
        $temKeep = [];
        foreach ($relevantSubjects as $sbid) {
            if (!in_array($sbid, $temKeep)) {
                $temKeep[] = $sbid;
                $schSbj = subj::where('id', $sbid)->first();
                $clsSbj[] = $schSbj;
            }
        }
        $pld = [
            'std-pld' => $stdPld,
            'cls-sbj' => $clsSbj //Not necessary, maybe remove later????
        ];
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }






    // public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];
    //     // Fetch students based on class and section filters
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     // Fetch relevant subjects
    //     $relevantSubjects = [];
    //     if ($stf == "-1" || $stf == "-2") {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj');
    //     } else {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');
    //     }

    //     $stdPld = [];
    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $studentSubjects = student_subj::where('stid', $user_id)->whereIn('sbj', $relevantSubjects)->get();
    //         $mySbjs = [];
    //         $validScores = []; // Store valid (non-zero and non-empty) scores for calculations
    //         $zeroScoreSubjects = []; // To store subjects with zero or empty scores for display purposes

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;
    //             $mySbjs[] = $sbid;

    //             // Fetch the subject scores
    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsid", $clsm)
    //                 ->get();

    //             // Separate zero, null, and valid scores
    //             $validScoresTemp = $subjectScores->filter(function ($score) {
    //                 return !empty($score->scr) && $score->scr > 0; // Only valid scores greater than 0 and not empty
    //             });

    //             $zeroOrNullScores = $subjectScores->filter(function ($score) {
    //                 return empty($score->scr) || $score->scr == 0; // Zero or null scores
    //             });

    //             // If there are valid scores, add them to validScores for computation
    //             if ($validScoresTemp->isNotEmpty()) {
    //                 $validScores[] = [
    //                     'sbid' => $sbid,
    //                     'scores' => $validScoresTemp
    //                 ];
    //             }
    //             // Add zero or empty-score subjects to the list for display purposes
    //             if ($zeroOrNullScores->isNotEmpty()) {
    //                 $zeroScoreSubjects[] = [
    //                     'sbid' => $sbid,
    //                     'scores' => $zeroOrNullScores
    //                 ];
    //             }
    //         }

    //         // Check for subjects with no score in std_score table (these subjects will have zero score)
    //         $subjectsWithNoScores = $relevantSubjects->diff($mySbjs); // Subjects not found in student's subjects
    //         foreach ($subjectsWithNoScores as $sbid) {
    //             // Add subjects with zero score
    //             $zeroScoreSubjects[] = [
    //                 'sbid' => $sbid,
    //                 'scores' => []  // Empty scores array to indicate no score
    //             ];
    //         }

    //         $psy = false;
    //         $res = "0";
    //         $rinfo = [];
    //         if ($stf == "-2") {
    //             $psy = student_psy::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->exists();
    //             $rinfo = student_res::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->first();
    //             if ($rinfo) {
    //                 $res = $rinfo->stat;
    //             }
    //         }

    //         // Add the student data with valid (non-zero and non-empty) subjects and scores
    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //             'scr' => array_merge($validScores, $zeroScoreSubjects), // Merging valid scores and zero/empty-score subjects for display
    //             'psy' => $psy,
    //             'res' => $res,
    //             'rinfo' => $rinfo,
    //         ];
    //     }

    //     // Create a list of all relevant subjects, including those with zero or null scores
    //     $clsSbj = [];
    //     $temKeep = [];
    //     foreach ($relevantSubjects as $sbid) {
    //         if (!in_array($sbid, $temKeep)) {
    //             $temKeep[] = $sbid;
    //             $schSbj = subj::where('id', $sbid)->first();
    //             if ($schSbj) {
    //                 $clsSbj[] = $schSbj;
    //             }
    //         }
    //     }

    //     // Get the number of days (nof) from the result_meta table
    //     $nof = result_meta::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //     ])->value('num_of_days') ?? 0;

    //     // Structure the final response
    //     $pld = [
    //         'std-pld' => $stdPld,  // Include student data with subjects and scores
    //         'num_of_days' => $nof,  // Number of days
    //         'cls-sbj' => $clsSbj,   // List of all relevant subjects (including zero/null and valid scores)
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }





    // public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     // Fetch relevant subjects
    //     $relevantSubjects = ($stf == "-1" || $stf == "-2")
    //         ? class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj')
    //         : class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');

    //     $stdPld = [];
    //     $validSubjects = []; // Store subjects with at least one non-zero score

    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $studentSubjects = student_subj::where('stid', $user_id)
    //             ->whereIn('sbj', $relevantSubjects)
    //             ->get();

    //         $mySbjs = [];
    //         $scores = [];

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;

    //             // Fetch student scores, ensuring we exclude zero or null scores
    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsid", $clsm)
    //                 ->where('scr', '>', 0) // Exclude zero scores
    //                 ->get();

    //             $totalScore = $subjectScores->sum('scr');

    //             if ($totalScore > 0) { // Include subject only if it has a valid score
    //                 $mySbjs[] = $sbid;
    //                 $scores[] = [
    //                     'sbid' => $sbid,
    //                     'scores' => $subjectScores
    //                 ];
    //                 $validSubjects[$sbid] = true; // Mark as valid
    //             }
    //         }

    //         if (!empty($mySbjs)) { // Only add students who have valid subjects
    //             $psy = false;
    //             $res = "0";
    //             $rinfo = [];

    //             if ($stf == "-2") {
    //                 $psy = student_psy::where("schid", $schid)
    //                     ->where("ssn", $ssn)
    //                     ->where("trm", $trm)
    //                     ->where("clsm", $clsm)
    //                     ->where("stid", $user_id)
    //                     ->exists();

    //                 $rinfo = student_res::where("schid", $schid)
    //                     ->where("ssn", $ssn)
    //                     ->where("trm", $trm)
    //                     ->where("clsm", $clsm)
    //                     ->where("stid", $user_id)
    //                     ->first();

    //                 if ($rinfo) {
    //                     $res = $rinfo->stat;
    //                 }
    //             }

    //             $stdPld[] = [
    //                 'std' => $std,
    //                 'sbj' => $mySbjs,
    //                 'scr' => $scores,
    //                 'psy' => $psy,
    //                 'res' => $res,
    //                 'rinfo' => $rinfo,
    //             ];
    //         }
    //     }

    //     // Filter clsSbj to only include subjects that have valid scores
    //     $clsSbj = [];
    //     foreach (array_keys($validSubjects) as $sbid) {
    //         $schSbj = subj::where('id', $sbid)->first();
    //         if ($schSbj) {
    //             $clsSbj[] = $schSbj;
    //         }
    //     }

    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    // public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];

    //     // Get old students based on class arm condition
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     // Get subjects based on staff
    //     if ($stf == "-1" || $stf == "-2") {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj');
    //     } else {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');
    //     }

    //     $stdPld = [];

    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;

    //         $studentSubjects = student_subj::where('stid', $user_id)
    //             ->whereIn('sbj', $relevantSubjects)
    //             ->get();

    //         $mySbjs = [];
    //         $scores = [];

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;
    //             $mySbjs[] = $sbid;

    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsid", $clsm)
    //                 ->get();

    //             $scores[] = [
    //                 'sbid' => $sbid,
    //                 'scores' => $subjectScores
    //             ];
    //         }

    //         $psy = false;
    //         $res = "0";
    //         $rinfo = [];

    //         if ($stf == "-2") {
    //             $psy = student_psy::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->exists();

    //             $rinfo = student_res::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->first();

    //             if ($rinfo) {
    //                 $res = $rinfo->stat;
    //             }
    //         }

    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //             'scr' => $scores,
    //             'psy' => $psy,
    //             'res' => $res,
    //             'rinfo' => $rinfo,
    //         ];
    //     }

    //     // Get unique class subjects
    //     $clsSbj = [];
    //     $temKeep = [];

    //     foreach ($relevantSubjects as $sbid) {
    //         if (!in_array($sbid, $temKeep)) {
    //             $temKeep[] = $sbid;
    //             $schSbj = subj::where('id', $sbid)->first();
    //             if ($schSbj) {
    //                 $clsSbj[] = $schSbj;
    //             }
    //         }
    //     }

    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj, // Optional: remove if not needed
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    // public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];

    //     // Get old students based on class arm condition
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     // Get subjects based on staff
    //     if ($stf == "-1" || $stf == "-2") {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj');
    //     } else {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');
    //     }

    //     $stdPld = [];

    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;

    //         $studentSubjects = student_subj::where('stid', $user_id)
    //             ->whereIn('sbj', $relevantSubjects)
    //             ->get();

    //         $mySbjs = [];
    //         $scores = [];
    //         $validSubjectsForCount = [];

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;

    //             // Always include subject in response
    //             $mySbjs[] = $sbid;

    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsid", $clsm)
    //                 ->get();

    //             // Check if any of the scores are not null or zero
    //             $hasValidScore = $subjectScores->contains(function ($score) {
    //                 return !is_null($score->scr) && $score->scr > 0;
    //             });

    //             if ($hasValidScore) {
    //                 $validSubjectsForCount[] = $sbid;
    //             }

    //             $scores[] = [
    //                 'sbid' => $sbid,
    //                 'scores' => $subjectScores
    //             ];
    //         }

    //         $psy = false;
    //         $res = "0";
    //         $rinfo = [];

    //         if ($stf == "-2") {
    //             $psy = student_psy::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->exists();

    //             $rinfo = student_res::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->first();

    //             if ($rinfo) {
    //                 $res = $rinfo->stat;
    //             }
    //         }

    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs, // all subjects (for response)
    //             'scr' => $scores, // scores per subject
    //             'valid_sbj' => $validSubjectsForCount, // subjects with valid scores for calculations
    //             'psy' => $psy,
    //             'res' => $res,
    //             'rinfo' => $rinfo,
    //         ];
    //     }

    //     // Get unique class subjects
    //     $clsSbj = [];
    //     $temKeep = [];

    //     foreach ($relevantSubjects as $sbid) {
    //         if (!in_array($sbid, $temKeep)) {
    //             $temKeep[] = $sbid;
    //             $schSbj = subj::where('id', $sbid)->first();
    //             if ($schSbj) {
    //                 $clsSbj[] = $schSbj;
    //             }
    //         }
    //     }

    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj,
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }



    // public function getOldStudentsAndSubject($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("status", "active")
    //             ->where("ssn", $ssn)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     $relevantSubjects = [];
    //     if ($stf == "-1" || $stf == "-2") {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->pluck('sbj');
    //     } else {
    //         $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //             ->where('class_subj.schid', $schid)
    //             ->where('class_subj.clsid', $clsm)
    //             ->where('staff_subj.stid', $stf)
    //             ->pluck('sbj');
    //     }

    //     $stdPld = [];
    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $studentSubjects = student_subj::where('stid', $user_id)->whereIn('sbj', $relevantSubjects)->get();
    //         $mySbjs = [];
    //         $validScores = []; // Store valid (non-zero and non-empty) scores for calculations
    //         $zeroScoreSubjects = []; // To store subjects with zero or empty scores for display purposes

    //         foreach ($studentSubjects as $sbj) {
    //             $sbid = $sbj->sbj;
    //             $mySbjs[] = $sbid;

    //             // Fetch the subject scores
    //             $subjectScores = std_score::where('stid', $user_id)
    //                 ->where('sbj', $sbid)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsid", $clsm)
    //                 ->get();

    //             // Filter out subjects with zero or empty scores
    //             $nonZeroScores = $subjectScores->filter(function ($score) {
    //                 return !empty($score->scr) && $score->scr > 0; // Only valid scores greater than 0 and not empty
    //             });

    //             // If there are valid scores, add them to validScores for computation
    //             if ($nonZeroScores->isNotEmpty()) {
    //                 $validScores[] = [
    //                     'sbid' => $sbid,
    //                     'scores' => $nonZeroScores
    //                 ];
    //             } else {
    //                 // Add zero or empty-score subjects to the list for display purposes
    //                 $zeroScoreSubjects[] = [
    //                     'sbid' => $sbid,
    //                     'scores' => $subjectScores
    //                 ];
    //             }
    //         }

    //         $psy = false;
    //         $res = "0";
    //         $rinfo = [];
    //         if ($stf == "-2") {
    //             $psy = student_psy::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->exists();
    //             $rinfo = student_res::where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsm", $clsm)
    //                 ->where("stid", $user_id)
    //                 ->first();
    //             if ($rinfo) {
    //                 $res = $rinfo->stat;
    //             }
    //         }

    //         // Add the student data with valid (non-zero and non-empty) subjects and scores
    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //             'scr' => array_merge($validScores, $zeroScoreSubjects), // Merging valid scores and zero/empty-score subjects for display
    //             'psy' => $psy,
    //             'res' => $res,
    //             'rinfo' => $rinfo,
    //         ];
    //     }

    //     $clsSbj = [];
    //     $temKeep = [];
    //     foreach ($relevantSubjects as $sbid) {
    //         if (!in_array($sbid, $temKeep)) {
    //             $temKeep[] = $sbid;
    //             $schSbj = subj::where('id', $sbid)->first();
    //             $clsSbj[] = $schSbj;
    //         }
    //     }

    //     // Get the number of fails (nof) from the result_meta table
    //     $nof = result_meta::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //     ])->value('num_of_days') ?? 0;

    //     // Structure the final response
    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'num_of_days' => $nof,
    //         'cls-sbj' => $clsSbj // Keeping this may not be necessary, can remove later if not used
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }




    ////////////////////////////////////////





    /**
     * @OA\Get(
     *     path="/api/getOldStudentsAndSubjectHistory/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's score from past session",
     *     description="Use this endpoint to get basic information about an old student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stf",
     *         in="path",
     *         required=true,
     *         description="Staff ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    // public function getOldStudentsAndSubjectHistory($schid, $ssn,$trm, $clsm, $clsa,$stf){
    // $ostd = [];
    // if($clsa=='-1'){
    //     $ostd = old_student::where("schid", $schid)->where("ssn", $ssn)->where('status', 'active')->where("clsm", $clsm)->get();
    // }else{
    //     $ostd = old_student::where("schid", $schid)->where("ssn", $ssn)->where('status', 'active')->where("clsm", $clsm)->where("clsa", $clsa)->get();
    // }
    // $stdPld = [];
    // $relevantSubjects = [];
    // $clsSbj = [];
    // foreach ($ostd as $std) {
    //     $user_id = $std->sid;
    //     $mySbjs = [];
    //     $allScores = std_score::where('stid',$user_id)
    //     ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsid", $clsm)->get();
    //     foreach($allScores as $scr){
    //         $sbid = $scr->sbj;
    //         if (!in_array($scr->sbj, $mySbjs)) {
    //             $mySbjs[] = $scr->sbj;
    //         }
    //         if (!in_array($scr->sbj, $relevantSubjects)) {
    //             $schSbj = subj::where('id',$scr->sbj)->first();
    //             $clsSbj[] = $schSbj;
    //             $relevantSubjects[] = $scr->sbj;
    //         }
    //     }
    //     $subjectScores = [];
    //     foreach($mySbjs as $sbid){
    //         $subjectScores[$sbid] = [];
    //     }
    //     $scores = [];
    //     foreach($allScores as $scr){
    //         $sbid = $scr->sbj;
    //         $subjectScores[$sbid][] = $scr;
    //     }
    //     foreach($mySbjs as $sbid){
    //         $scores[] = [
    //             'sbid' => $sbid,
    //             'scores' => $subjectScores[$sbid]
    //         ];
    //     }
    //     $psy = false;
    //     $res = "0";
    //     $rinfo = [];
    //     if($stf=="-2"){
    //         $psy = student_psy::where("schid", $schid)
    //         ->where("ssn", $ssn)
    //         ->where("trm", $trm)
    //         ->where("clsm", $clsm)
    //         ->where("stid", $user_id)
    //         ->exists();
    //         $rinfo = student_res::where("schid", $schid)
    //         ->where("ssn", $ssn)
    //         ->where("trm", $trm)
    //         ->where("clsm", $clsm)
    //         ->where("stid", $user_id)
    //         ->first();
    //         if($rinfo){
    //             $res = $rinfo->stat;
    //         }
    //     }
    //     $stdPld[] = [
    //         'std'=> $std,
    //         'sbj'=>$mySbjs,
    //         'scr'=> $scores,
    //         'psy'=> $psy,
    //         'res'=> $res,
    //         'rinfo'=> $rinfo,
    //     ];
    // }
    // $pld = [
    //     'std-pld'=>$stdPld,
    //     'cls-sbj' => $clsSbj
    // ];
    // return response()->json([
    //     "status"=> true,
    //     "message"=> "Success",
    //     "pld"=> $pld,
    // ]);
    // }


    public function getOldStudentsAndSubjectHistory($schid, $ssn, $trm, $clsm, $clsa, $stf)
    {
        $ostd = [];
        if ($clsa == '-1') {
            $ostd = old_student::where("schid", $schid)->where("ssn", $ssn)->where('status', 'active')->where("clsm", $clsm)->get();
        } else {
            $ostd = old_student::where("schid", $schid)->where("ssn", $ssn)->where('status', 'active')->where("clsm", $clsm)->where("clsa", $clsa)->get();
        }

        $stdPld = [];
        $relevantSubjects = [];
        $clsSbj = [];

        foreach ($ostd as $std) {
            $user_id = $std->sid;
            $mySbjs = [];

            // Get all scores where score is greater than 0
            $allScores = std_score::where('stid', $user_id)
                ->where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsid", $clsm)
                ->whereNotNull('scr') // Exclude null scores
                ->where('scr', '>', 0) // Exclude zero scores
                ->get();

            // Collect subjects and their corresponding scores
            foreach ($allScores as $scr) {
                $sbid = $scr->sbj;
                if (!in_array($scr->sbj, $mySbjs)) {
                    $mySbjs[] = $scr->sbj;
                }
                if (!in_array($scr->sbj, $relevantSubjects)) {
                    $schSbj = subj::where('id', $scr->sbj)->first();
                    $clsSbj[] = $schSbj;
                    $relevantSubjects[] = $scr->sbj;
                }
            }

            // Organize scores by subject
            $subjectScores = [];
            foreach ($mySbjs as $sbid) {
                $subjectScores[$sbid] = [];
            }

            $scores = [];
            foreach ($allScores as $scr) {
                $sbid = $scr->sbj;
                $subjectScores[$sbid][] = $scr;
            }

            // Collect the scores for each subject
            foreach ($mySbjs as $sbid) {
                $scores[] = [
                    'sbid' => $sbid,
                    'scores' => $subjectScores[$sbid]
                ];
            }

            $psy = false;
            $res = "0";
            $rinfo = [];

            if ($stf == "-2") {
                $psy = student_psy::where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsm", $clsm)
                    ->where("stid", $user_id)
                    ->exists();

                $rinfo = student_res::where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsm", $clsm)
                    ->where("stid", $user_id)
                    ->first();

                if ($rinfo) {
                    $res = $rinfo->stat;
                }
            }

            $stdPld[] = [
                'std' => $std,
                'sbj' => $mySbjs,
                'scr' => $scores,
                'psy' => $psy,
                'res' => $res,
                'rinfo' => $rinfo,
            ];
        }

        $pld = [
            'std-pld' => $stdPld,
            'cls-sbj' => $clsSbj
        ];

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getOldStudentsAndSubjectWithoutScore/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stf}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old student's Basic Info",
     *     description="Use this endpoint to get basic information about an old student.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stf",
     *         in="path",
     *         required=true,
     *         description="Staff ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getOldStudentsAndSubjectWithoutScore($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     $ostd = [];
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)->where("clsa", $clsa)->get();
    //     }
    //     $stdPld = [];
    //     $relevantSubjects = [];
    //     $clsSbj = [];
    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $mySbjs = [];
    //         $allScores = std_score::where('stid', $user_id)
    //             ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsid", $clsm)->get();
    //         foreach ($allScores as $scr) {
    //             $sbid = $scr->sbj;
    //             if (!in_array($scr->sbj, $mySbjs)) {
    //                 $mySbjs[] = $scr->sbj;
    //             }
    //             if (!in_array($scr->sbj, $relevantSubjects)) {
    //                 $schSbj = subj::where('id', $scr->sbj)->first();
    //                 $clsSbj[] = $schSbj;
    //                 $relevantSubjects[] = $scr->sbj;
    //             }
    //         }
    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //         ];
    //     }
    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj
    //     ];
    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }

    // public function getOldStudentsAndSubjectWithoutScore($schid, $ssn, $trm, $clsm, $clsa, $stf)
    // {
    //     // Get old students
    //     if ($clsa == '-1') {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("ssn", $ssn)
    //             ->where("trm", $trm)
    //             ->where("clsm", $clsm)
    //             ->get();
    //     } else {
    //         $ostd = old_student::where("schid", $schid)
    //             ->where("ssn", $ssn)
    //             ->where("trm", $trm)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->get();
    //     }

    //     $stdPld = [];

    //     // Get all subjects for this class (for cls-sbj)
    //     $clsSbj = class_subj::where("schid", $schid)
    //         ->where("clsid", $clsm)
    //         ->where("sesn", $ssn)
    //         ->where("trm", $trm)
    //         ->get();

    //     foreach ($ostd as $std) {
    //         $user_id = $std->sid;
    //         $mySbjs = [];

    //         // Get all scores for student
    //         $allScores = std_score::where('stid', $user_id)
    //             ->where("schid", $schid)
    //             ->where("ssn", $ssn)
    //             ->where("trm", $trm)
    //             ->where("clsid", $clsm)
    //             ->pluck('sbj');

    //         if ($allScores->isNotEmpty()) {
    //             // If student already has scores, use those subjects
    //             $mySbjs = $allScores->toArray();
    //         } else {
    //             // Otherwise, use subjects assigned in student_subj
    //             $studentSubjects = student_subj::where('stid', $user_id)
    //                 ->where("schid", $schid)
    //                 ->where("ssn", $ssn)
    //                 ->where("trm", $trm)
    //                 ->where("clsid", $clsm)
    //                 ->pluck('sbj');

    //             $mySbjs = $studentSubjects->toArray();
    //         }

    //         $stdPld[] = [
    //             'std' => $std,
    //             'sbj' => $mySbjs,
    //         ];
    //     }

    //     $pld = [
    //         'std-pld' => $stdPld,
    //         'cls-sbj' => $clsSbj,
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    public function getOldStudentsAndSubjectWithoutScore($schid, $ssn, $trm, $clsm, $clsa, $stf)
    {
        // Get old students
        if ($clsa == '-1') {
            $ostd = old_student::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsm", $clsm)
                ->get();
        } else {
            $ostd = old_student::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsm", $clsm)
                ->where("clsa", $clsa)
                ->get();
        }

        $stdPld = [];

        // Get all subjects for this class (for cls-sbj) and rename subj_id to id
        $clsSbj = class_subj::where("schid", $schid)
            ->where("clsid", $clsm)
            ->where("sesn", $ssn)
            ->where("trm", $trm)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->subj_id,  // changed from subj_id to id
                    'name' => $item->name,
                    'comp' => $item->comp
                ];
            });

        foreach ($ostd as $std) {
            $user_id = $std->sid;
            $mySbjs = [];

            // Get all scores for student
            $allScores = std_score::where('stid', $user_id)
                ->where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsid", $clsm)
                ->pluck('sbj');

            if ($allScores->isNotEmpty()) {
                // If student already has scores, use those subjects
                $mySbjs = $allScores->toArray();
            } else {
                // Otherwise, use subjects assigned in student_subj
                $studentSubjects = student_subj::where('stid', $user_id)
                    ->where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsid", $clsm)
                    ->pluck('sbj');

                $mySbjs = $studentSubjects->toArray();
            }

            $stdPld[] = [
                'std' => $std,
                'sbj' => $mySbjs,
            ];
        }

        $pld = [
            'std-pld' => $stdPld,
            'cls-sbj' => $clsSbj,
        ];

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getStudentResult/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a student's result",
     *     description="Use this endpoint to get basic information about a student's result.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Id of the class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getStudentResult($schid, $ssn,$trm, $clsm, $clsa,$stid){
    //     $totalStd = student::join('old_student', 'student.sid', '=', 'old_student.sid')
    //     ->where('student.schid', $schid)
    //     ->where('student.stat', "1")
    //     ->where('old_student.ssn', $ssn)
    //     ->where('old_student.clsm', $clsm)
    //     ->where('old_student.clsa', $clsa)
    //     ->count();
    //     $std = old_student::where("schid", $schid)->where("ssn", $ssn)->where("clsm", $clsm)->where("clsa", $clsa)->where("sid", $stid)->first();
    //     $pld = [];
    //     $user_id = $std->sid;
    //     $scores = [];
    //     $mySbjs = [];
    //     $allScores = std_score::where('stid',$user_id)
    //         ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsid", $clsm)->get();
    //     foreach($allScores as $scr){
    //         $sbid = $scr->sbj;
    //         if (!in_array($sbid, $mySbjs)) {
    //             $mySbjs[] = $sbid;
    //         }
    //     }
    //     $subjectScores = [];
    //     foreach($mySbjs as $sbid){
    //         $subjectScores[$sbid] = [];
    //     }
    //     $scores = [];
    //     foreach($allScores as $scr){
    //         $sbid = $scr->sbj;
    //         $subjectScores[$sbid][] = $scr;
    //     }
    //     $positions = [];
    //     foreach($mySbjs as $sbid){
    //         $scores[] = [
    //             'sbid' => $sbid,
    //             'scores' => $subjectScores[$sbid]
    //         ];
    //         $subjectPosition = student_sub_res::where('stid',$user_id)->where('sbj', $sbid)
    //         ->where("schid", $schid)->where("ssn", $ssn)->where("trm", $trm)->where("clsm", $clsm)
    //         ->where("clsa", $clsa)->first();
    //         if($subjectPosition){
    //             $positions[] = [
    //                 'sbid' => $sbid,
    //                 'pos' => $subjectPosition->pos,
    //             ];
    //         }
    //     }
    //     $psy = student_psy::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //         ['clsm', $clsm],
    //         ['stid', $user_id]
    //     ])->exists();

    //     $rinfo = student_res::where("schid", $schid)
    //     ->where("ssn", $ssn)
    //     ->where("trm", $trm)
    //     ->where("clsm", $clsm)
    //     ->where("stid", $user_id)
    //     ->first();

    //     $res = student_res::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //         ['clsm', $clsm],
    //         ['stid', $user_id]
    //     ])->value('stat') ?? "0";
    //     $pld = [
    //         'std'=> $std,
    //         'sbj'=> $mySbjs,
    //         'scr'=> $scores,
    //         'psy'=> $psy,
    //         'res'=> $res,
    //         'rinfo'=> $rinfo,
    //         'cnt'=> $totalStd,
    //         'spos'=> $positions
    //     ];
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Success",
    //         "pld"=> $pld,
    //     ]);
    // }


    // public function getStudentResult($schid, $ssn, $trm, $clsm, $clsa, $stid)
    // {
    //     $totalStd = student::join('old_student', 'student.sid', '=', 'old_student.sid')
    //         ->where('student.schid', $schid)
    //         ->where('student.stat', "1")
    //         ->where('student.status', "active")
    //         ->where('old_student.ssn', $ssn)
    //         ->where('old_student.clsm', $clsm)
    //         ->where('old_student.clsa', $clsa)
    //         ->count();

    //     $std = old_student::where("schid", $schid)->where("ssn", $ssn)
    //         ->where("clsm", $clsm)->where("status", "active")
    //         ->where("clsa", $clsa)->where("sid", $stid)->first();

    //     if (!$std) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "Student has been exited",
    //             "pld" => []
    //         ], 404);
    //     }

    //     $user_id = $std->sid;
    //     $scores = [];
    //     $mySbjs = [];

    //     // Get the relevant subjects for the student
    //     $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
    //         ->where('class_subj.schid', $schid)
    //         ->where('class_subj.clsid', $clsm)
    //         ->pluck('sbj');

    //     $studentSubjects = student_subj::where('stid', $user_id)
    //         ->whereIn('sbj', $relevantSubjects)
    //         ->pluck('sbj');

    //     // Get all scores, excluding subjects with zero or null scores
    //     $allScores = std_score::where('stid', $user_id)
    //         ->where("schid", $schid)
    //         ->where("ssn", $ssn)
    //         ->where("trm", $trm)
    //         ->where("clsid", $clsm)
    //         ->whereIn("sbj", $studentSubjects)
    //         ->whereNotNull('scr')  // Exclude NULL scores
    //         ->where('scr', '>', 0) // Exclude zero scores
    //         ->get();


    //     foreach ($allScores as $scr) {
    //         $sbid = $scr->sbj;
    //         if (!in_array($sbid, $mySbjs)) {
    //             $mySbjs[] = $sbid;
    //         }
    //     }

    //     $subjectScores = [];
    //     foreach ($mySbjs as $sbid) {
    //         $subjectScores[$sbid] = [];
    //     }

    //     foreach ($allScores as $scr) {
    //         $sbid = $scr->sbj;
    //         $subjectScores[$sbid][] = $scr;
    //     }

    //     $positions = [];
    //     foreach ($mySbjs as $sbid) {
    //         $scores[] = [
    //             'sbid' => $sbid,
    //             'scores' => $subjectScores[$sbid]
    //         ];
    //         $subjectPosition = student_sub_res::where('stid', $user_id)
    //             ->where('sbj', $sbid)
    //             ->where("schid", $schid)
    //             ->where("ssn", $ssn)
    //             ->where("trm", $trm)
    //             ->where("clsm", $clsm)
    //             ->where("clsa", $clsa)
    //             ->first();

    //         if ($subjectPosition) {
    //             $positions[] = [
    //                 'sbid' => $sbid,
    //                 'pos' => $subjectPosition->pos,
    //             ];
    //         }
    //     }

    //     $psy = student_psy::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //         ['clsm', $clsm],
    //         ['stid', $user_id]
    //     ])->exists();

    //     $rinfo = student_res::where("schid", $schid)
    //         ->where("ssn", $ssn)
    //         ->where("trm", $trm)
    //         ->where("clsm", $clsm)
    //         ->where("stid", $user_id)
    //         ->first();

    //     $res = student_res::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['trm', $trm],
    //         ['clsm', $clsm],
    //         ['stid', $user_id]
    //     ])->value('stat') ?? "0";

    //     $pld = [
    //         'std' => $std,
    //         'sbj' => $mySbjs,
    //         'scr' => $scores,
    //         'psy' => $psy,
    //         'res' => $res,
    //         'rinfo' => $rinfo,
    //         'cnt' => $totalStd,
    //         'spos' => $positions
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    public function getStudentResult($schid, $ssn, $trm, $clsm, $clsa, $stid)
    {
        $totalStd = student::join('old_student', 'student.sid', '=', 'old_student.sid')
            ->where('student.schid', $schid)
            ->where('student.stat', "1")
            ->where('old_student.ssn', $ssn)
            ->where('old_student.clsm', $clsm)
            ->where('old_student.clsa', $clsa)
            ->count();

        $std = old_student::where("schid", $schid)->where("ssn", $ssn)
            ->where("clsm", $clsm)
            ->where("clsa", $clsa)->where("sid", $stid)->first();

        if (!$std) {
            return response()->json([
                "status" => false,
                "message" => "Student has been exited",
                "pld" => []
            ], 404);
        }

        $user_id = $std->sid;
        $scores = [];
        $mySbjs = [];

        // Get the relevant subjects for the student
        $relevantSubjects = class_subj::join('staff_subj', 'class_subj.subj_id', '=', 'staff_subj.sbj')
            ->where('class_subj.schid', $schid)
            ->where('class_subj.clsid', $clsm)
            ->pluck('sbj');

        // Get subjects the student is registered for
        $studentSubjects = student_subj::where('stid', $user_id)
            ->whereIn('sbj', $relevantSubjects)
            ->pluck('sbj');

        // Get all scores, excluding subjects with zero or null scores
        $allScores = std_score::where('stid', $user_id)
            ->where("schid", $schid)
            ->where("ssn", $ssn)
            ->where("trm", $trm)
            ->where("clsid", $clsm)
            ->whereIn("sbj", $studentSubjects)
            ->whereNotNull('scr')
            ->where('scr', '>', 0) // Ensure only subjects with scores > 0 are considered
            ->get();

        // Populate subjects that have scores
        foreach ($allScores as $scr) {
            $sbid = $scr->sbj;
            if (!in_array($sbid, $mySbjs)) {
                $mySbjs[] = $sbid;
            }
        }

        $subjectScores = [];
        foreach ($mySbjs as $sbid) {
            $subjectScores[$sbid] = [];
        }

        // Assign scores to subjects
        foreach ($allScores as $scr) {
            $sbid = $scr->sbj;
            $subjectScores[$sbid][] = $scr;
        }

        $positions = [];
        foreach ($mySbjs as $sbid) {
            $scores[] = [
                'sbid' => $sbid,
                'scores' => $subjectScores[$sbid]
            ];

            // Get subject position, excluding zero/null score subjects
            $subjectPosition = student_sub_res::where('stid', $user_id)
                ->where('sbj', $sbid)
                ->where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("trm", $trm)
                ->where("clsm", $clsm)
                ->where("clsa", $clsa)
                ->first();

            if ($subjectPosition) {
                $positions[] = [
                    'sbid' => $sbid,
                    'pos' => $subjectPosition->pos,
                ];
            }
        }

        // Check for student psychological result
        $psy = student_psy::where([
            ['schid', $schid],
            ['ssn', $ssn],
            ['trm', $trm],
            ['clsm', $clsm],
            ['stid', $user_id]
        ])->exists();

        // Get student result info
        $rinfo = student_res::where("schid", $schid)
            ->where("ssn", $ssn)
            ->where("trm", $trm)
            ->where("clsm", $clsm)
            ->where("stid", $user_id)
            ->first();

        $res = student_res::where([
            ['schid', $schid],
            ['ssn', $ssn],
            ['trm', $trm],
            ['clsm', $clsm],
            ['stid', $user_id]
        ])->value('stat') ?? "0";


        // Get the number of fails (nof) from the result_meta table
        $nof = result_meta::where([
            ['schid', $schid],
            ['ssn', $ssn],
            ['trm', $trm],
        ])->value('num_of_days') ?? 0;

        $presentCountQuery = \DB::table('attendances')
            ->where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('sid', $std->sid);



        // Check if any attendance exists for this student
        $attendanceExists = $presentCountQuery->exists();

        if ($attendanceExists) {
            Log::info('Success:' . $attendanceExists . 'yes it is');
            $presentCount = $presentCountQuery->where('status', 1)->count();
            $absentCount = max(0, $nof - $presentCount);
        } else {
            Log::info('Error' . 'no attendance');
            $presentCount = null;
            $absentCount = null;
        }


        $pld = [
            'std' => $std,
            'sbj' => $mySbjs,  // List of subjects with valid scores
            'scr' => $scores,  // List of scores
            'psy' => $psy,
            'res' => $res,
            'rinfo' => $rinfo,
            'cnt' => $totalStd,
            'num_of_days' => $nof,
            'present_days' => $presentCount,
            'absent_days' => $absentCount,
            'spos' => $positions, // Subject positions
        ];

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /////////////////////////////////////



    /**
     * @OA\Get(
     *     path="/api/getStaffSubjects/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff's subjects",
     *     description="Use this endpoint to get subjects of a staff.",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffSubjects($stid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = staff_subj::where("stid", $stid)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteStaffSubject/{uid}",
     *     tags={"Api"},
     *     summary="Delete a staff subject",
     *     description="Use this endpoint to delete a staff subject",
     *
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteStaffSubject($uid)
    {
        $pld = staff_subj::where('uid', $uid)->delete();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStaffClass",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set staff classes",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="cls", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="suid", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="role2", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Staff data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStaffClass(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "stid" => "required",
            "cls" => "required",
            "schid" => "required",
            "ssn" => "required",
            "fname" => "required",
            "lname" => "required",
            "mname" => "nullable",
            "suid" => "required",
            "role" => "required",
            "role2" => "required",
        ]);
        staff_class::updateOrCreate(
            ["uid" => $request->uid,],
            [
                "stid" => $request->stid,
                "cls" => $request->cls,
                "schid" => $request->schid,
            ]
        );
        $uid = $request->ssn . $request->cls . $request->stid;
        old_staff::updateOrCreate(
            ["uid" => $uid,],
            [
                'sid' => $request->stid,
                'schid' => $request->schid,
                'fname' => $request->fname,
                'mname' => $request->mname,
                'lname' => $request->lname,
                'suid' => $request->suid,
                'ssn' => $request->ssn,
                'clsm' => $request->cls,
                'role' => $request->role,
                'role2' => $request->role2,
                'more' => "",
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStaffClasses/{stid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff's classes",
     *     description="Use this endpoint to get classes of a staff.",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffClasses($stid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = staff_class::where("stid", $stid)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteStaffClass/{uid}",
     *     tags={"Api"},
     *     summary="Delete a staff class",
     *     description="Use this endpoint to delete a staff class",
     *
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteStaffClass($uid)
    {
        $pld = staff_class::where('uid', $uid)->first();
        $act1 = staff_class_arm::where("stid", $pld->stid)->where("cls", $pld->cls)->delete();
        $act2 = old_staff::where("sid", $pld->stid)->where("clsm", $pld->cls)->delete();
        //TODO Delete subjects too
        $pld->delete();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setStaffClassArm",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set staff class arm",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="cls", type="string"),
     *             @OA\Property(property="arm", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Staff data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setStaffClassArm(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "stid" => "required",
            "cls" => "required",
            "arm" => "required",
            "schid" => "required",
        ]);
        $pld = staff_class_arm::updateOrCreate(
            ["uid" => $request->uid,],
            [
                "stid" => $request->stid,
                "cls" => $request->cls,
                "arm" => $request->arm,
                "schid" => $request->schid,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStaffClassArms/{stid}/{cls}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff's class arms",
     *     description="Use this endpoint to get class arms of a staff.",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="cls",
     *         in="path",
     *         required=true,
     *         description="The Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffClassArms($stid, $cls)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = staff_class_arm::where("stid", $stid)->where("cls", $cls)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClassArmsByStaffClass/{stid}/{cls}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff's class arms",
     *     description="Use this endpoint to get class arms of a staff.",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="User Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="cls",
     *         in="path",
     *         required=true,
     *         description="The Class Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassArmsByStaffClass($stid, $cls)
    {
        $arms = staff_class_arm::where("stid", $stid)
            ->where("cls", $cls)
            ->pluck('arm')
            ->map(fn($arm) => (int) $arm); // Ensure the array is of integers
        $pld = sch_cls::whereIn('id', $arms)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStaffByClassArms/{schid}/{arm}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get staff by class arms",
     *     description="Use this endpoint to get staff by class arm.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="arm",
     *         in="path",
     *         required=true,
     *         description="The Class Arm Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffByClassArms($schid, $arm)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = staff_class_arm::where("schid", $schid)->where("arm", $arm)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteStaffClassArm/{uid}",
     *     tags={"Api"},
     *     summary="Delete a staff class arm",
     *     description="Use this endpoint to delete a staff class arm",
     *
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteStaffClassArm($uid)
    {
        $pld = staff_class_arm::where('uid', $uid)->delete();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStaffByClassArmAndSubject/{schid}/{arm}/{sbid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get a staff by class arm and subject",
     *     description="Use this endpoint to get a staff by class arm and subject.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="arm",
     *         in="path",
     *         required=true,
     *         description="The Class Arm Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbid",
     *         in="path",
     *         required=true,
     *         description="Subject Id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffByClassArmAndSubject($schid, $arm, $sbid)
    {
        $pld = staff_class_arm::join('staff_subj', 'staff_class_arm.stid', '=', 'staff_subj.stid')
            ->where('staff_class_arm.schid', $schid)
            ->where('staff_class_arm.arm', $arm)
            ->where('staff_subj.sbj', $sbid)
            ->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setOldStaffInfo",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Set info about an old staff data. Specify id if you wish to update",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *             @OA\Property(property="sid", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="fname", type="string"),
     *             @OA\Property(property="mname", type="string"),
     *             @OA\Property(property="lname", type="string"),
     *             @OA\Property(property="suid", type="string"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="clsm", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="role2", type="string"),
     *             @OA\Property(property="more", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Staff old data set successfully"),
     *     @OA\Response(response="400", description="Validation error"),
     * )
     */
    public function setOldStaffInfo(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
            "sid" => "required",
            "schid" => "required",
            "fname" => "required",
            "mname" => "required",
            "lname" => "required",
            "suid" => "required",
            "ssn" => "required",
            "clsm" => "required",
            "role" => "required",
            "role2" => "required",
            "more" => "required",
        ]);
        old_staff::updateOrCreate(
            ["uid" => $request->uid,],
            [
                'sid' => $request->sid,
                'schid' => $request->schid,
                'fname' => $request->fname,
                'mname' => $request->mname,
                'lname' => $request->lname,
                'suid' => $request->suid,
                'ssn' => $request->ssn,
                'clsm' => $request->clsm,
                'role' => $request->role,
                'role2' => $request->role2,
                'more' => $request->more,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Info Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getOldStaff/{schid}/{ssn}/{clsm}/{role}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old staff's Basic Info",
     *     description="Use this endpoint to get basic information about an old staff.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="Role Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getOldStaff($schid, $ssn, $clsm, $role)
    {
        $pld = [];
        if ($role == '-1') {
            $pld = old_staff::where("schid", $schid)->where("ssn", $ssn)->where("status", "active")->where("clsm", $clsm)->get();
        } else {
            $pld = old_staff::where("schid", $schid)
                ->where("ssn", $ssn)
                ->where("status", "active")
                ->where("clsm", $clsm)
                ->where(function ($query) use ($role) {
                    $query->where("role", $role)
                        ->orWhere("role2", $role);
                })
                ->get();
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getOldStaffStat/{schid}/{ssn}/{clsm}/{role}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old staff's stats",
     *     description="Use this endpoint to get stats information about an old staff.",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="Id of the school",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Id of the session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Id of the main class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="Role Id of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getOldStaffStat($schid, $ssn, $clsm, $role)
    {
        $male = 0;
        $female = 0;
        if ($role == '-1') {
            $male = old_staff::join('staff_basic_data', 'old_staff.sid', '=', 'staff_basic_data.user_id')
                ->where('old_staff.schid', $schid)
                ->where('old_staff.ssn', $ssn)
                ->where('status', 'active')
                ->where('old_staff.clsm', $clsm)
                ->where('staff_basic_data.sex', 'M')
                ->count();
            $female = old_staff::join('staff_basic_data', 'old_staff.sid', '=', 'staff_basic_data.user_id')
                ->where('old_staff.schid', $schid)
                ->where('old_staff.ssn', $ssn)
                ->where('status', 'active')
                ->where('old_staff.clsm', $clsm)
                ->where('staff_basic_data.sex', 'F')
                ->count();
        } else {
            $male = old_staff::join('staff_basic_data', 'old_staff.sid', '=', 'staff_basic_data.user_id')
                ->where('old_staff.schid', $schid)
                ->where('old_staff.ssn', $ssn)
                ->where('old_staff.clsm', $clsm)
                ->where(function ($query) use ($role) {
                    $query->where('old_staff.role', $role)
                        ->orWhere('old_staff.role2', $role);
                })
                ->where('staff_basic_data.sex', 'M')
                ->count();

            $female = old_staff::join('staff_basic_data', 'old_staff.sid', '=', 'staff_basic_data.user_id')
                ->where('old_staff.schid', $schid)
                ->where('old_staff.ssn', $ssn)
                ->where('old_staff.clsm', $clsm)
                ->where(function ($query) use ($role) {
                    $query->where('old_staff.role', $role)
                        ->orWhere('old_staff.role2', $role);
                })
                ->where('staff_basic_data.sex', 'F')
                ->count();
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "male" => $male,
                "female" => $female,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getOldStaffInfo/{uid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get an old staff's Basic Info",
     *     description="Use this endpoint to get basic information about an old staff.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="uid of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getOldStaffInfo($uid)
    {
        $pld = old_staff::where("uid", $uid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }



    //---messaging


    /**
     * @OA\Get(
     *     path="/api/searchMsgThread",
     *     tags={"Api"},
     *     summary="Full text search on subjects",
     *     description=" Use this endpoint for Full text search on message subjects",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchMsgThread()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        if ($search) {
            $pld = msgthread::whereRaw("MATCH(subject) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(subject) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(2)
                ->get();
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getMyMessagesStat/{uid}",
     *     tags={"Api"},
     *     summary="Get Message Count by UID",
     *     description="Use this endpoint to get messages count for this `uid`",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getMyMessagesStat($uid)
    {
        $totalMessages = msgthread::where('from_uid', $uid)->orWhere('to_uid', $uid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "totalMessages" => $totalMessages,
            ],
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getMyMessages/{uid}",
     *     tags={"Api"},
     *     summary="Get Message Threads by UID",
     *     description="Use this endpoint to get messages Threads for this `uid`",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getMyMessages($uid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = msgthread::where('from_uid', $uid)->orWhere('to_uid', $uid)
            ->orderBy('updated_at', 'desc')->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getMessageThread/{tid}",
     *     tags={"Api"},
     *     summary="Get Messages by thread id",
     *     description="Use this endpoint to get messages for this `tid`",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tid",
     *         in="path",
     *         required=true,
     *         description="Thread ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getMessageThread($tid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = msg::where('tid', $tid)->skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/createMsgThread",
     *     tags={"Api"},
     *     summary="Create a new message thread",
     *     description="Use this endpoint to create a new message thread.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="from", type="string", description="Name of the person sending"),
     *             @OA\Property(property="from_uid", type="string", description="User ID of the person sending"),
     *             @OA\Property(property="to", type="string", description="Name of the person receiving"),
     *             @OA\Property(property="to_uid", type="string", description="User ID of the person receiving"),
     *             @OA\Property(property="subject", type="string", description="Message Subject"),
     *             @OA\Property(property="from_mail", type="string", description=",,"),
     *             @OA\Property(property="to_mail", type="string", description=",,"),
     *             @OA\Property(property="last_msg", type="string", description="Last Message (First in this case) - Shown in preview"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function createMsgThread(Request $request)
    {
        $request->validate([
            "from" => "required",
            "from_uid" => "required",
            "to" => "required",
            "to_uid" => "required",
            "last_msg" => "required",
            "subject" => "required",
            "from_mail" => "required",
            "to_mail" => "required"
        ]);
        $mt = msgthread::create([
            "from" => $request->from,
            "from_uid" => $request->from_uid,
            "to" => $request->to,
            "to_uid" => $request->to_uid,
            "last_msg" => $request->last_msg,
            "subject" => $request->subject,
            "from_mail" => $request->from_mail,
            "to_mail" => $request->to_mail,
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $mt
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/sendMsg",
     *     tags={"Api"},
     *     summary="Send a message",
     *     description="Use this endpoint to send a chat. You may also notify by mail",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="body", type="string", description="Message content"),
     *             @OA\Property(property="who", type="string", description="User ID of the person sending"),
     *             @OA\Property(property="tid", type="string", description="Thread ID of the message"),
     *             @OA\Property(property="mail", type="string", description="If not empty, user will be mailed"),
     *             @OA\Property(property="art", type="string", description="Document ID"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function sendMsg(Request $request)
    {
        $request->validate([
            "body" => "required",
            "who" => "required",
            "tid" => "required",
            "mail" => "required",
            "art" => "required",
        ]);
        $trd = msgthread::where('id', intval($request->tid))->first();
        if ($trd) {
            $ms = msg::create([
                "tid" => $request->tid,
                "body" => $request->body,
                "who" => $request->who,
                "art" => $request->art,
            ]);
            $trd->update([
                "last_msg" => $request->body,
            ]);
            if ($request->mail != '') {
                $isPerson1 = $request->who == $trd->from_uid;
                $from = null;
                $to = null;
                if ($isPerson1) {
                    $from = $trd->from;
                    $to = $trd->to;
                } else {
                    $from = $trd->to;
                    $to = $trd->from;
                }
                // Wrap the email sending logic in a try-catch block
                try {
                    $data = [
                        'name' => $from . ' -> ' . $to,
                        'subject' => $trd->subject,
                        'body' => $request->body,
                        'link' => $request->art != '_' ? env('API_URL') . '/getFile/msg/' . $request->art : env('PORTAL_URL')
                    ];

                    Mail::to($request->mail)->send(new SSSMails($data));
                } catch (\Exception $e) {
                    // Log the email error, but don't stop the process
                    Log::error('Failed to send email: ' . $e->getMessage());
                }

                return response()->json([
                    "status" => true,
                    "message" => "Success (User was also mailed)",
                    "pld" => $ms
                ]);
            }
            // Respond
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $ms
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "Thread not found"
        ]);
    }




    //--- FILE UPLOAD

    /**
     * @OA\Post(
     *     path="/api/uploadFile",
     *     tags={"File"},
     *     security={{"bearerAuth": {}}},
     *     summary="Upload a file",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="filename",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="folder",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="string"
     *                 ),
     *                 required={"file", "filename", "folder", "user_id"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function uploadFile(Request $request){
    //     $request->validate([
    //         'file' => 'required', //|mimes:jpeg,png,jpg,gif,svg|max:2048
    //         'filename' => 'required',
    //         'folder' => 'required',
    //         'user_id'=> 'required',
    //     ]);
    //     if ($request->hasFile('file')) {
    //         $file = $request->file('file');
    //         $filename = $request->filename;
    //         $folder = $request->folder;
    //         if (!Storage::disk('public')->exists($folder)) {
    //             // If it doesn't exist, create the directory
    //             Storage::disk('public')->makeDirectory($folder);
    //         }
    //         Storage::disk('public')->put($folder.'/'. $filename, file_get_contents($file));
    //         // Log It
    //         files::create([
    //             'user_id' => $request->user_id,
    //             'file'=> $filename,
    //             'folder'=> $folder,
    //         ]);
    //         return response()->json([
    //             "status"=> true,
    //             "message"=> "Success"
    //         ]);
    //     } else {
    //         return response()->json([
    //             "status"=> false,
    //             "message"=> "No file provided"
    //         ]);
    //     }
    // }



    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required', //|mimes:jpeg,png,jpg,gif,svg|max:2048
            'filename' => 'required',
            'folder' => 'required',
            'user_id' => 'required',
        ]);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $request->filename;
            $folder = $request->folder;
            if (!Storage::disk('public')->exists($folder)) {
                // If it doesn't exist, create the directory
                Storage::disk('public')->makeDirectory($folder);
            }
            Storage::disk('public')->put($folder . '/' . $filename, file_get_contents($file));
            // Log It
            files::create([
                'user_id' => $request->user_id,
                'file' => $filename,
                'folder' => $folder,
            ]);
            return response()->json([
                "status" => true,
                "message" => "Success"
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No file provided"
            ]);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/getFiles/{user_id}",
     *     tags={"File"},
     *     summary="Get all Files belonging to a user ",
     *     description="API: Use this endpoint to get all files by a user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="user ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *      ),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getFiles($uid)
    {
        $pld = files::where('user_id', $uid)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getFile/{folder}/{filename}",
     *     tags={"Unprotected"},
     *     summary="Get File",
     *     description="API: Use this endpoint to get a file by providing the folder and filename as path parameters.",
     *     @OA\Parameter(
     *         name="folder",
     *         in="path",
     *         required=true,
     *         description="Name of the folder",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         required=true,
     *         description="Name of the file",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/octet-stream",
     *              @OA\Schema(type="file")
     *          )
     *      ),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="404", description="File not found"),
     * )
     */
    public function getFile($folder, $filename)
    {
        if (Storage::disk('public')->exists($folder . '/' . $filename)) {
            return response()->file(Storage::disk('public')->path($folder . '/' . $filename));
        } else {
            return response()->json([
                "status" => false,
                "message" => "File not found",
            ], 404);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/fileExists/{folder}/{filename}",
     *     tags={"File"},
     *     summary="Check if File Exists",
     *     description="API: Use this endpoint to check if a file exists by providing the folder and filename as path parameters.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="folder",
     *         in="path",
     *         required=true,
     *         description="Name of the folder",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         required=true,
     *         description="Name of the file",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function fileExists($folder, $filename)
    {
        if (Storage::disk('public')->exists($folder . '/' . $filename)) {
            return response()->json([
                "status" => true,
                "message" => "Yes, it does",
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "File not found",
            ]);
        }
    }






    //------------------------- PAYMENT

    /**
     * @OA\Get(
     *     path="/api/resolveAccountNumber",
     *     tags={"General"},
     *     summary="Validate an account Number using paystack",
     *     description="Validate an account Number using paystack",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="anum",
     *         in="path",
     *         required=true,
     *         description="Account Number",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="bnk",
     *         in="path",
     *         required=true,
     *         description="Bank Code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function resolveAccountNumber($anum, $bnk)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET'),
        ])->get('https://api.paystack.co/bank/resolve', [
            'account_number' => $anum,
            'bank_code' => $bnk,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Output or use the resolved data
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $data
            ]);
        } else {
            // Handle error
            $error = $response->body();
            return response()->json([
                "status" => false,
                "message" => "Failed",
                "pld" => $error
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/setPayRecord",
     *     tags={"Payments"},
     *     summary="Record a payment",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="sid", type="string", example="123"),
     *             @OA\Property(property="rid", type="string", example="456"),
     *             @OA\Property(property="sname", type="string", example="John Doe"),
     *             @OA\Property(property="amt", type="integer", example="1500"),
     *             @OA\Property(property="time", type="integer", example=1625097600000),
     *             @OA\Property(property="ref", type="string", example="ABC123XYZ"),
     *             @OA\Property(property="typ", type="string", example="1"),
     *             @OA\Property(property="pid", type="string", example="10"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment Recorded",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment Recorded")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setPayRecord(Request $request)
    {
        $request->validate([
            'sid' => 'required',
            'rid' => 'required',
            'sname' => 'required',
            'amt' => 'required',
            'time' => 'required',
            'ref' => 'required',
            'typ' => 'required',
            'pid' => 'required',
        ]);
        pay::create([
            'sid' => $request->sid,
            'rid' => $request->rid,
            'sname' => $request->sname,
            'amt' => $request->amt,
            'time' => $request->time,
            'ref' => $request->ref,
            'typ' => $request->typ,
            'pid' => $request->pid,
        ]);
        return response()->json([
            "status" => true,
            "message" => "Payment Recorded"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPaysByReceiver/{uid}",
     *     tags={"Payments"},
     *     summary="Get Payment records by Receiver",
     *     description="Use this endpoint to get Payment records by Receiver",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="rid",
     *         in="path",
     *         required=true,
     *         description="User ID of the receiver",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPaysByReceiver($rid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = pay::where('rid', $rid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPaysBySender/{sid}",
     *     tags={"Payments"},
     *     summary="Get Payment records by Sender",
     *     description="Use this endpoint to get Payment records by Sender",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="sid",
     *         in="path",
     *         required=true,
     *         description="User ID of the sender",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPaysBySender($sid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = pay::where('sid', $sid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPaysBySenderAndReceiver/{sid}/{rid}",
     *     tags={"Payments"},
     *     summary="Get Payment records by Sender And Receiver",
     *     description="Use this endpoint to get Payment records by Sender And Receiver",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="sid",
     *         in="path",
     *         required=true,
     *         description="User ID of the sender",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="rid",
     *         in="path",
     *         required=true,
     *         description="User ID of the receiver",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPaysBySenderAndReceiver($sid, $rid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = pay::where('rid', $rid)->where('sid', $sid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPaymentStat/{schid}/{clsid}/{ssnid}/{trmid}",
     *     tags={"Api"},
     *     summary="Get how many payments are available",
     *     description="Use this endpoint to get ow many payments are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssnid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPaymentStat($schid, $clsid, $ssnid, $trmid)
    {
        $query = payments::query();

        if ($schid !== "-1") {
            $query->where('schid', $schid);
        }

        if ($clsid !== "-1") {
            $query->where('clsid', $clsid);
        }

        if ($ssnid !== "-1") {
            $query->where('ssnid', $ssnid);
        }

        if ($trmid !== "-1") {
            $query->where('trmid', $trmid);
        }

        $total = $query->count();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPayments/{schid}/{clsid}/{ssnid}/{trmid}",
     *     tags={"Payments"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get Payments by School",
     *     description="Use this endpoint to get Payments by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssnid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getPayments($schid, $clsid, $ssnid, $trmid){
    //     $start = 0;
    //     $count = 20;

    //     if(request()->has('start') && request()->has('count')) {
    //         $start = request()->input('start');
    //         $count = request()->input('count');
    //     }

    //     $query = payments::query();

    //     if ($schid !== "-1") {
    //         $query->where('schid', $schid);
    //     }

    //     if ($clsid !== "-1") {
    //         $query->where('clsid', $clsid);
    //     }

    //     if ($ssnid !== "-1") {
    //         $query->where('ssnid', $ssnid);
    //     }

    //     if ($trmid !== "-1") {
    //         $query->where('trmid', $trmid);
    //     }

    //     $pld = $query->skip($start)->take($count)->get();

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    // public function getPayments($schid, $clsid, $ssnid, $trmid)
    // {
    //     $start = 0;
    //     $count = 20;

    //     if (request()->has('start') && request()->has('count')) {
    //         $start = request()->input('start');
    //         $count = request()->input('count');
    //     }

    //     $query = payments::query();

    //     if ($schid !== "-1") {
    //         $query->where('schid', $schid);
    //     }

    //     if ($clsid !== "-1") {
    //         $query->where('clsid', $clsid);
    //     }

    //     if ($ssnid !== "-1") {
    //         $query->where('ssnid', $ssnid);
    //     }

    //     if ($trmid !== "-1") {
    //         $query->where('trmid', $trmid);
    //     }

    //     // Get total amount
    //     $totalAmount = (clone $query)->sum('amt');

    //     // Get paginated payment data
    //     $pld = $query->skip($start)->take($count)->get();

    //     // Get all payments stid
    //     $paidStudentIds = (clone $query)->distinct()->pluck('stid')->toArray();

    //     if (empty($paidStudentIds)) {
    //         // No payment records found
    //         $paidStudentsCount = 0;
    //         $notPaidStudentsCount = 0;
    //     } else {
    //         // Count paid students
    //         $paidStudentsCount = count($paidStudentIds);

    //         // Get all old_student.sids for this schid and clsid
    //         $oldStudentQuery = old_student::query();

    //         if ($schid !== "-1") {
    //             $oldStudentQuery->where('schid', $schid);
    //         }

    //         if ($clsid !== "-1") {
    //             $oldStudentQuery->where('clsm', $clsid);
    //         }

    //         $allStudentIds = $oldStudentQuery->distinct()->pluck('sid')->toArray();

    //         // Determine not paid students by diff
    //         $notPaidStudentIds = array_diff($allStudentIds, $paidStudentIds);
    //         $notPaidStudentsCount = count($notPaidStudentIds);
    //     }

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "total_revenue_amount" => $totalAmount,
    //         "paid_students_count" => $paidStudentsCount,
    //         "not_paid_students_count" => $notPaidStudentsCount,
    //         "pld" => $pld,
    //     ]);
    // }


    public function getPayments($schid, $clsid, $ssnid, $trmid)
    {
        $start = 0;
        $count = 20;

        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $query = payments::query();

        if ($schid !== "-1") {
            $query->where('schid', $schid);
        }

        if ($clsid !== "-1") {
            $query->where('clsid', $clsid);
        }

        if ($ssnid !== "-1") {
            $query->where('ssnid', $ssnid);
        }

        if ($trmid !== "-1") {
            $query->where('trmid', $trmid);
        }

        // Clone query to get total amount
        $totalAmount = (clone $query)->sum('amt');

        // Get paginated payment data
        $pld = $query->skip($start)->take($count)->get();

        // Check if there are any payments that match the query
        $paymentsExist = (clone $query)->exists();

        $paidStudentsCount = 0;
        $notPaidStudentsCount = 0;

        if ($paymentsExist) {
            $paidStudentsCount = student::where('schid', $schid)
                ->whereExists(function ($subQuery) use ($schid, $ssnid, $trmid) {
                    $subQuery->select(DB::raw(1))
                        ->from('payments')
                        ->whereColumn('payments.stid', 'student.sid')
                        ->where('payments.schid', $schid)
                        ->where('payments.ssnid', $ssnid)
                        ->where('payments.trmid', $trmid);
                })
                ->count();

            $notPaidStudentsCount = student::where('schid', $schid)
                ->whereNotExists(function ($subQuery) use ($schid, $ssnid, $trmid) {
                    $subQuery->select(DB::raw(1))
                        ->from('payments')
                        ->whereColumn('payments.stid', 'student.sid')
                        ->where('payments.schid', $schid)
                        ->where('payments.ssnid', $ssnid)
                        ->where('payments.trmid', $trmid);
                })
                ->count();
        }

        // Return structured JSON response
        return response()->json([
            "status" => true,
            "message" => "Success",
            "total_revenue_amount" => $totalAmount,
            "paid_students_count" => $paidStudentsCount,
            "not_paid_students_count" => $notPaidStudentsCount,
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStudentPayments/{stid}",
     *     tags={"Payments"},
     *     security={{"bearerAuth": {}}},
     *     summary="get a student payments",
     *     description="Use this endpoint to get payments by a student",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentPayments($stid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = payments::where('stid', $stid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getStudentPaymentStat/{stid}",
     *     tags={"Payments"},
     *     summary="get stats for student payments",
     *     description="Use this endpoint to get stats for payments by a student",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudentPaymentStat($stid)
    {
        $total = payments::where('stid', $stid)->count();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/confirmPayment/{schid}/{clsid}/{ssnid}/{trmid}/{stid}",
     *     tags={"Payments"},
     *     security={{"bearerAuth": {}}},
     *     summary="Returns null if no payment",
     *     description="Use this endpoint to confirm payment",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssnid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function confirmPayment($schid,$clsid,$ssnid,$trmid,$stid){
    //     $pld = payments::where('schid', $schid)->where('clsid', $clsid)->where('ssnid', $ssnid)->where('trmid', $trmid)->where('stid', $stid)->first();
    //     // Respond
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Success",
    //         "pld"=> $pld,
    //     ]);
    // }

    public function confirmPayment($schid, $clsid, $ssnid, $trmid, $stid)
    {
        $pld = payments::where('schid', $schid)
            ->where('clsid', $clsid)
            ->where('ssnid', $ssnid)
            ->where('trmid', $trmid)
            ->where('stid', $stid)
            ->orderBy('created_at', 'desc') // ðŸ”¥ get the latest by creation time
            ->first();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    //     public function confirmPayment($schid, $clsid, $ssnid, $trmid, $stid)
    // {
    //     // Get all payments for the student in that term
    //     $allPayments = payments::where('schid', $schid)
    //         ->where('clsid', $clsid)
    //         ->where('ssnid', $ssnid)
    //         ->where('trmid', $trmid)
    //         ->where('stid', $stid)
    //         ->orderBy('id', 'desc')
    //         ->get();

    //     // Calculate total amount paid
    //     $totalPaid = $allPayments->sum('amt');

    //     // Get latest payment record
    //     $latestPayment = $allPayments->first();

    //     // Attach total_paid to the latest payment record
    //     if ($latestPayment) {
    //         $latestPayment->amt = $totalPaid;
    //     }

    //     // Respond
    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $latestPayment,
    //     ]);
    // }



    // public function confirmPayment($schid, $clsid, $ssnid, $trmid, $stid)
    //   {
    //     $pld = payments::where('schid', $schid)
    //         ->where('clsid', $clsid)
    //         ->where('ssnid', $ssnid)
    //         ->where('trmid', $trmid)
    //         ->where('stid', $stid)
    //         ->with('payhead:id,schid,name,comp') // Join payhead table
    //         ->first();

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //         "phid" => $pld?->payhead?->id // Extract payhead ID (phid)
    //     ]);
    //   }


    /**
     * @OA\Get(
     *     path="/api/confirmAcceptancePayment/{schid}/{clsid}/{stid}",
     *     tags={"Payments"},
     *     summary="Returns null if no payment",
     *     description="Use this endpoint to confirm payment of acceptance fee",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function confirmAcceptancePayment($schid, $clsid, $stid)
    {
        $pld = afeerec::where('schid', $schid)->where('clsid', $clsid)->where('stid', $stid)->first();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setAFeeRecord",
     *     tags={"Payments"},
     *     summary="Record an acceptance fee payment",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string", example="123"),
     *             @OA\Property(property="schid", type="string", example="456"),
     *             @OA\Property(property="clsid", type="string", example="John Doe"),
     *             @OA\Property(property="amt", type="integer", example="1500"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment Recorded",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment Recorded")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setAFeeRecord(Request $request)
    {
        $request->validate([
            'stid' => 'required',
            'schid' => 'required',
            'clsid' => 'required',
            'amt' => 'required',
        ]);
        $stid = $request->stid;
        $schid = $request->schid;
        $clsid = $request->clsid;
        $amt = $request->amt;
        $uid = $stid . $schid . $clsid;
        afeerec::updateOrCreate(
            ["uid" => $uid,],
            [
                "stid" => $stid,
                "schid" => $schid,
                "clsid" => $clsid,
                "amt" => $amt,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Payment Recorded"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAcceptancePaymentStat/{schid}/{clsid}",
     *     tags={"Api"},
     *     summary="Get how many acceptance fee payments are available",
     *     description="Use this endpoint to get how many aFee payments are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAcceptancePaymentStat($schid, $clsid)
    {
        $total = afeerec::where('schid', $schid)->where('clsid', $clsid)->count();
        $totalAmt = afeerec::where('schid', $schid)->where('clsid', $clsid)->sum('amt');
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
                "totalAmt" => $totalAmt,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAcceptancePayments/{schid}/{clsid}",
     *     tags={"Payments"},
     *     summary="Get Acceptance Fee Payments by School",
     *     description="Use this endpoint to get Payments by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAcceptancePayments($schid, $clsid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = afeerec::where('schid', $schid)->where('clsid', $clsid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getRegFeePaymentStat/{schid}/{rfee}",
     *     tags={"Api"},
     *     summary="Get how many reg fee payments are available",
     *     description="Use this endpoint to get how many rFee payments are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="rfee",
     *         in="path",
     *         required=true,
     *         description="Reg Fee ID (0/1)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getRegFeePaymentStat($schid, $rfee)
    {
        $total = student::where('schid', $schid)->where('rfee', $rfee)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getRegFeePayments/{schid}/{rfee}",
     *     tags={"Payments"},
     *     summary="Get Reg Fee Payments by School",
     *     description="Use this endpoint to get Payments by School",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="rfee",
     *         in="path",
     *         required=true,
     *         description="Reg Fee ID (0/1)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getRegFeePayments($schid, $rfee)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = student::where('schid', $schid)->where('rfee', $rfee)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setAcctPref",
     *     tags={"Payments"},
     *     summary="Set Acct Preference (0= By class, 1 = By payment heading)",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="sid", type="string", example="123"),
     *             @OA\Property(property="pref", type="string", example="456"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preference Recorded",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment Recorded")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setAcctPref(Request $request)
    {
        $request->validate([
            'sid' => 'required',
            'pref' => 'required',
        ]);
        acct_pref::updateOrCreate(
            ["sid" => $request->sid,],
            [
                "pref" => $request->pref,
            ]
        );
        return response()->json([
            "status" => true,
            "message" => "Preference Recorded"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAcctPref/{schid}",
     *     tags={"Api"},
     *     summary="Get preference for acct number",
     *     description="Use this endpoint to get pref",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAcctPref($schid)
    {
        $pld = acct_pref::where('sid', $schid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }




    /**
     * @OA\Post(
     *     path="/api/setAccount",
     *     summary="Create or Update an Account with Paystack Subaccount",
     *     description="Creates a new account and registers a Paystack subaccount. If 'id' is provided, it updates an existing account.",
     *     operationId="setAccount",
     *     security={{"bearerAuth": {}}},
     *     tags={"Accounts"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "clsid", "ssnid", "trmid", "anum", "bnk", "aname"},
     *             @OA\Property(property="schid", type="integer", example=1, description="School ID"),
     *             @OA\Property(property="clsid", type="integer", example=10, description="Class ID"),
     *             @OA\Property(property="anum", type="string", example="1234567890", description="Account Number"),
     *             @OA\Property(property="bnk", type="string", example="058", description="Bank Code (e.g., GTBank: 058)"),
     *             @OA\Property(property="aname", type="string", example="John Doe", description="Account Holder Name"),
     *             @OA\Property(property="id", type="integer", example=5, description="(Optional) ID for updating an existing account")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success Response",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Account Updated")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Account and Paystack Subaccount Created Successfully"),
     *                     @OA\Property(property="paystack_data", type="object",
     *                         @OA\Property(property="subaccount_code", type="string", example="SUB_ACCT_ABC123"),
     *                         @OA\Property(property="business_name", type="string", example="Business_65f9b0d4"),
     *                         @OA\Property(property="settlement_bank", type="string", example="058"),
     *                         @OA\Property(property="percentage_charge", type="number", example=0)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Failed to Create Paystack Subaccount",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to Create Paystack Subaccount"),
     *             @OA\Property(property="error", type="object", example={"status": false, "message": "Invalid Bank Code", "type": "validation_error"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Account Not Found (When updating an account with an invalid ID)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account Not Found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="anum", type="array", @OA\Items(type="string", example="The account number field is required.")),
     *                 @OA\Property(property="bnk", type="array", @OA\Items(type="string", example="The bank code field is required."))
     *             )
     *         )
     *     )
     * )
     */



    public function setAcct(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'clsid' => 'required',
            'anum' => 'required',
            'bnk'  => 'required',
            'aname' => 'required',
        ]);

        $data = [
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'anum' => $request->anum,
            'bnk' => $request->bnk,
            'aname' => $request->aname,
        ];

        if ($request->has('id')) {
            $acct = accts::find($request->id);
            if ($acct) {
                $acct->update($data);
                return response()->json([
                    "status" => true,
                    "message" => "Account Updated",
                ]);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Account Not Found",
                ], 404);
            }
        } else {
            $acct = accts::create($data);

            // Automatically generate Paystack-required fields
            $business_name = "Business_" . uniqid(); // Generate a unique business name
            $percentage_charge = 0; // Default percentage charge
            $settlement_bank = $request->bnk; // Use the bank user provided

            // Step 1: Create Paystack subaccount
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/subaccount', [
                'business_name' => $business_name,
                'account_number' => $request->anum,
                'bank_code' => $request->bnk,
                'percentage_charge' => $percentage_charge,
                'settlement_bank' => $settlement_bank,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Step 2: Store subaccount details in the database
                sub_account::create([
                    'acct_id' => $acct->id,
                    'schid' => $request->schid,
                    'clsid' => $request->clsid,
                    'subaccount_code' => $data['data']['subaccount_code'],
                    'percentage_charge' => $percentage_charge,
                ]);

                return response()->json([
                    "status" => true,
                    "message" => "Account and Paystack Subaccount Created Successfully",
                    "paystack_data" => $data,
                ]);
            } else {
                // Delete the main account if Paystack subaccount creation fails
                $acct->delete();

                return response()->json([
                    "status" => false,
                    "message" => "Failed to Create Paystack Subaccount",
                    "error" => $response->body(),
                ], 400);
            }
        }
    }






    /**
     * @OA\Get(
     *     path="/api/getSubAccount/{acctid}",
     *     summary="Fetch or create a subaccount for a given account ID",
     *     description="Retrieves the subaccount details for the given account ID. If a subaccount does not exist, a new one is created using Paystack API.",
     *     tags={"Accounts"},
     * security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="acctid",
     *         in="path",
     *         required=true,
     *         description="Account ID to fetch the subaccount for",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subaccount retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subaccount retrieved successfully"),
     *             @OA\Property(
     *                 property="subaccount",
     *                 type="object",
     *                 @OA\Property(property="acct_id", type="integer", example=123),
     *                 @OA\Property(property="schid", type="integer", example=456),
     *                 @OA\Property(property="clsid", type="integer", example=789),
     *                 @OA\Property(property="subaccount_code", type="string", example="SUB_ABC123XYZ"),
     *                 @OA\Property(property="percentage_charge", type="number", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create subaccount",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create subaccount"),
     *             @OA\Property(property="error", type="string", example="Invalid bank details")
     *         )
     *     )
     * )
     */
    public function getSubAccount($acctid)
    {
        // Fetch the existing subaccount linked to the account
        $subaccount = sub_account::where('acct_id', $acctid)->first();

        if (!$subaccount) {
            // Step 1: Check if the account exists
            $account = accts::where('id', $acctid)->first();

            if (!$account) {
                return response()->json([
                    "status" => false,
                    "message" => "Account not found",
                ], 404);
            }

            // Step 2: Generate unique business name
            $business_name = "Business_" . uniqid();

            // Step 3: Prepare subaccount data for Paystack
            $percentage_charge = 0;
            $subaccountData = [
                'business_name' => $business_name,
                'account_number' => $request->anum ?? $account->anum,
                'bank_code' => $request->bnk ?? $account->bnk,
                'percentage_charge' => $percentage_charge,
                'settlement_bank' => $request->settlement_bank ?? $account->bnk,
            ];

            // Step 4: Create Paystack subaccount
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/subaccount', $subaccountData);

            if ($response->successful()) {
                $data = $response->json();

                // Step 5: Store subaccount details in the database
                $subaccount = sub_account::create([
                    'acct_id' => $account->id,
                    'schid' => $account->schid ?? null,
                    'clsid' => $account->clsid ?? null,
                    'subaccount_code' => $data['data']['subaccount_code'],
                    'percentage_charge' => $percentage_charge,
                ]);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Failed to create subaccount",
                    "error" => $response->json()['message'] ?? 'Unknown error',
                ], 400);
            }
        }

        return response()->json([
            "status" => true,
            "message" => "Subaccount retrieved successfully",
            "subaccount" => $subaccount,
        ]);
    }





    /**
     * @OA\Post(
     *     path="/api/initializePayment",
     *     summary="Initialize a payment with Paystack",
     *     tags={"Payments"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "amount", "schid", "clsid", "subaccount_code", "transaction_charge"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="amount", type="integer", example=5000, description="Amount in Naira"),
     *             @OA\Property(property="schid", type="integer", example=1, description="School ID"),
     *             @OA\Property(property="clsid", type="integer", example=2, description="Class ID"),
     *             @OA\Property(property="subaccount_code", type="string", example="ACCT_123ABC", description="Subaccount code"),
     *             @OA\Property(property="transaction_charge", type="integer", example=500, description="Transaction charge in Naira")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment Initialized Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment Initialized Successfully"),
     *             @OA\Property(property="data", type="object", example={"authorization_url": "https://paystack.com/pay/xyz"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment Initialization Failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment Initialization Failed"),
     *             @OA\Property(property="error", type="string", example="Invalid subaccount code")
     *         )
     *     )
     * )
     */


    public function initializePayment(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric|min:100',
            'schid' => 'required',
            'clsid' => 'required',
            'subaccount_code' => 'required', // The subaccount receiving the money
            'transaction_charge' => 'required|numeric|min:0'
        ]);

        $amountInKobo = $request->amount * 100;

        $payload = [
            'email' => $request->email,
            'amount' => $amountInKobo,
            'callback_url' => 'https://api.schoolsstest.top/api/payment/callback',
            'metadata' => [
                'school_id' => $request->schid,
                'class_id' => $request->clsid,
            ],
            'subaccount' => $request->subaccount_code,  // paystack will send money to this subaccount
            'bearer' => 'account',  //
            'currency' => 'NGN',
            'channels' => ['card', 'bank', 'ussd'],
            'transaction_charge' => $request->transaction_charge,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', $payload);

        if ($response->successful()) {
            Log::info('Paystack Response:', $response->json());
            return response()->json([
                "status" => true,
                "message" => "Payment Initialized Successfully",
                "data" => $response->json(),
            ]);
        } else {
            Log::error('Paystack Error:', ['response' => $response->body()]);
            return response()->json([
                "status" => false,
                "message" => "Payment Initialization Failed",
                "error" => $response->body(),
            ], 400);
        }
    }






    /**
     * @OA\Get(
     *     path="/api/getAccountStat/{schid}",
     *     tags={"Api"},
     *     summary="Get how many accounts are available",
     *     description="Use this endpoint to get how many accounts are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAccountStat($schid)
    {
        $total = accts::where('schid', $schid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAccountsBySchool/{schid}",
     *     tags={"Payments"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all Accounts by School",
     *     description="Use this endpoint to get all Accounts by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    // public function getAccountsBySchool($schid){
    //     $start = 0;
    //     $count = 20;
    //     if(request()->has('start') && request()->has('count')) {
    //         $start = request()->input('start');
    //         $count = request()->input('count');
    //     }
    //     $pld = accts::where('schid',$schid)->skip($start)->take($count)->get();
    //     // Respond
    //     return response()->json([
    //         "status"=> true,
    //         "message"=> "Success",
    //         "pld"=> $pld,
    //     ]);
    // }



    public function getAccountsBySchool($schid)
    {
        $start = request()->input('start', 0);
        $count = request()->input('count', 20);

        // Retrieve accounts with subaccount details
        $pld = accts::where('schid', $schid)
            ->with('subAccounts') // Load subAccounts relationship
            ->skip($start)
            ->take($count)
            ->get();

        return response()->json([
            "status"  => true,
            "message" => "Success",
            "pld"     => $pld,
        ]);
    }





    /**
     * @OA\Get(
     *     path="/api/getAccountsBySchoolAndClass/{schid}/{clsid}",
     *     tags={"Payments"},
     *     summary="Get Accounts by School And Class",
     *     description="Use this endpoint to get Accounts records by School And Class",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAccountsBySchoolAndClass($schid, $clsid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = accts::where('schid', $schid)->where('clsid', $clsid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteAccount/{schid}/{clsid}",
     *     tags={"Payments"},
     *     summary="Delete an Account",
     *     description="Use this endpoint to delete an Account",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteAcct($schid, $clsid)
    {
        $pld = accts::where('schid', $schid)->where('clsid', $clsid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setAFee",
     *     tags={"Payments"},
     *     summary="Create/Update acceptance Fee",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="amt", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fee Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setAFee(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'clsid' => 'required',
            'amt' => 'required',
        ]);
        $data = [
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'amt' => $request->amt,
        ];
        $afee = [];
        if ($request->has('id')) {
            $afee = afee::where('id', $request->id)->first();
            if ($afee) {
                $afee->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Fee Not Found",
                ]);
            }
        } else {
            $afee = afee::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Account Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAFeeStat/{schid}",
     *     tags={"Api"},
     *     summary="Get how many accpt. Fee are available",
     *     description="Use this endpoint to get how many accpt. fee are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAFeeStat($schid)
    {
        $total = afee::where('schid', $schid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAFeeBySchool/{schid}",
     *     tags={"Payments"},
     *     summary="Get all accpt. Fee by School",
     *     description="Use this endpoint to get all accpt. Fee by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAFeeBySchool($schid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = afee::where('schid', $schid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAFee/{schid}/{clsid}",
     *     tags={"Payments"},
     *     summary="Get all accpt. Fee for a class",
     *     description="Use this endpoint to get accpt. Fee",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAFee($schid, $clsid)
    {
        $pld = afee::where('schid', $schid)->where('clsid', $clsid)->first();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteAFee/{schid}/{clsid}",
     *     tags={"Payments"},
     *     summary="Delete an Accpt. Fee",
     *     description="Use this endpoint to delete an Accpt. Fee",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteAFee($schid, $clsid)
    {
        $pld = afee::where('schid', $schid)->where('clsid', $clsid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/setPayHead",
     *     tags={"Payments"},
     *     summary="Create/Update payment heading",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="comp", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PH Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setPayHead(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'comp' => 'required',
            'schid' => 'required',
        ]);
        $data = [
            'name' => $request->name,
            'comp' => $request->comp,
            'schid' => $request->schid,
        ];
        $ph = [];
        if ($request->has('id')) {
            $ph = payhead::where('id', $request->id)->first();
            if ($ph) {
                $ph->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "PH Not Found",
                ]);
            }
        } else {
            $ph = payhead::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "PH Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPayHeadStat/{schid}",
     *     tags={"Api"},
     *     summary="Get how many pay heads are available",
     *     description="Use this endpoint to get how many pay heads are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPayHeadStat($schid)
    {
        $total = payhead::where('schid', $schid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getPayHeadsBySchool/{schid}",
     *     tags={"Payments"},
     *     summary="Get all pay heads by School",
     *     description="Use this endpoint to get all pay heads by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getPayHeadsBySchool($schid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = payhead::where('schid', $schid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deletePayHead/{uid}",
     *     tags={"Payments"},
     *     summary="Delete a payment heading",
     *     description="Use this endpoint to delete a pay head",
     *
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deletePayHead($uid)
    {
        $pld = payhead::where('id', $uid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setClassPay",
     *     tags={"Payments"},
     *     summary="Link a pay heading to a class",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="clsid", type="string"),
     *             @OA\Property(property="amt", type="string"),
     *             @OA\Property(property="phid", type="string"),
     *             @OA\Property(property="sesid", type="string"),
     *             @OA\Property(property="trmid", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setClassPay(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'clsid' => 'required',
            'amt' => 'required',
            'phid' => 'required',
            'sesid' => 'required',
            'trmid' => 'required',
        ]);
        $data = [
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'amt' => $request->amt,
            'phid' => $request->phid,
            'sesid' => $request->sesid,
            'trmid' => $request->trmid,
        ];
        $clspay = [];
        if ($request->has('id')) {
            $clspay = clspay::where('id', $request->id)->first();
            if ($clspay) {
                $clspay->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Record Not Found",
                ]);
            }
        } else {
            $clspay = clspay::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Record Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClassPays/{schid}/{clsid}/{sesid}/{trmid}",
     *     tags={"Payments"},
     *     summary="Get payments by School And Class",
     *     description="Use this endpoint to get payments heads by School And Class",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sesid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClassPays($schid, $clsid, $sesid, $trmid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = clspay::where('schid', $schid)->where('clsid', $clsid)
            ->where('sesid', $sesid)->where('trmid', $trmid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteClassPay/{uid}",
     *     tags={"Payments"},
     *     summary="Delete a class payment",
     *     description="Use this endpoint to delete a class pay head",
     *
     *     @OA\Parameter(
     *         name="uid",
     *         in="path",
     *         required=true,
     *         description="ID of the record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteClassPay($uid)
    {
        $pld = clspay::where('id', $uid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }




    /**
     * @OA\Post(
     *     path="/api/paystackConf",
     *     summary="Process a payment reference and update records",
     *     description="This endpoint processes a payment reference, updates related records based on the type of payment, and sends an email confirmation.",
     *     tags={"Payments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ref", "data"},
     *             @OA\Property(property="ref", type="string", example="PAY-123456-5000-0-98765-2024-1-10"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="time", type="string", format="date-time", example="2024-03-07 10:00:00"),
     *                     @OA\Property(property="exp", type="string", example="2024-12-31"),
     *                     @OA\Property(property="eml", type="string", format="email", example="johndoe@example.com"),
     *                     @OA\Property(property="lid", type="string", example="LID-123456")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request or missing parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid reference")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */

    //Paystack Webhook (POST, formdata)
    // public function paystackConf(Request $request){
    //     Log::info('------------ARRIVED-----------');
    //     $payload = json_decode($request->input('payload'), true);
    //     if($payload['event'] == "charge.success"){
    //         $ref = $payload['data']['reference'];
    //         Log::info($ref);
    //         $pld = payment_refs::where("ref","=", $ref)->first();
    //         if(!$pld){ // Its unique
    //             $payinfo = explode('-',$ref);
    //             $amt = $payinfo[2];
    //             $schid = $payinfo[1];
    //             $typ = $payinfo[3];
    //             $stid = $payinfo[4];
    //             $ssnid = $payinfo[5];
    //             $trmid = $payinfo[6];
    //             $clsid = $payinfo[7];
    //             $nm = $payload['data']['metadata']['name'];
    //             $tm = $payload['data']['metadata']['time'];
    //             $exp = $payload['data']['metadata']['exp'];
    //             $eml = $payload['data']['metadata']['eml'];
    //             $lid = $payload['data']['metadata']['lid'];
    //             $pid = $payload['data']['metadata']['pid'];
    //             $what = '';
    //             if($typ =='0'){ //School-Student Payment
    //                 payments::create([
    //                     'schid'=>$schid,
    //                     'stid'=>$stid,
    //                     'ssnid'=>$ssnid,
    //                     'trmid'=>$trmid,
    //                     'clsid'=>$clsid,
    //                     'name'=>$nm,
    //                     'exp'=>$exp,
    //                     'amt'=>$amt,
    //                     'lid'=>$lid,
    //                 ]);
    //                 $what = 'School Fees';
    //             }
    //             if($typ =='1'){ //Application Fee Paid
    //                 //WARN: ClassID will be a placeholder in this case, so dont use it
    //                 student::where('sid',$stid)->update([
    //                     "rfee"=>'1'
    //                 ]);
    //                 $what = 'Application Fee';
    //             }
    //             if($typ =='2'){ //Acceptance Fee Paid
    //                 $uid = $stid.$schid.$clsid;
    //                 afeerec::updateOrCreate(
    //                     ["uid"=> $uid,],
    //                     [
    //                     "stid"=> $stid,
    //                     "schid"=> $schid,
    //                     "clsid"=> $clsid,
    //                     "amt"=> intval($amt),
    //                 ]);
    //                 $what = 'Acceptance Fee';
    //             }
    //             // Wrap the email sending logic in a try-catch block
    //             try {
    //                 $data = [
    //                     'name' => $nm,
    //                     'subject' => 'Payment Received',
    //                     'body' => 'Your '.$what.' payment was received',
    //                     'link'=>env('PORTAL_URL').'/studentLogin'.'/'.$schid,
    //                 ];
    //                 Mail::to($eml)->send(new SSSMails($data));
    //             } catch (\Exception $e) {
    //                 // Log the email error, but don't stop the process
    //                 Log::error('Failed to send email: ' . $e->getMessage());
    //             }

    //             payment_refs::create([
    //                 "ref"=> $ref,
    //                 "amt"=> $amt,
    //                 "time"=> $tm,
    //             ]);
    //             Log::info('SUCCESS');
    //         }else{
    //             Log::info('PLD EXISTS'.json_encode($pld));
    //         }
    //     }else{
    //         Log::info('EVENTS BAD '.$payload['event']);
    //     }
    //     return response()->json(['status' => 'success'], 200);
    // }



    //Paystack Webhook (POST, formdata)
    public function paystackConf(Request $request)
    {
        Log::info('------------ARRIVED-----------');
        $payload = json_decode($request->input('payload'), true);
        if ($payload['event'] == "charge.success") {
            $ref = $payload['data']['reference'];
            $pld = payment_refs::where("ref", "=", $ref)->first();
            if (!$pld) { // Its unique
                $payinfo = explode('-', $ref);
                $amt = $payinfo[2];
                $schid = $payinfo[1];
                $typ = $payinfo[3];
                $stid = $payinfo[4];
                $ssnid = $payinfo[5];
                $trmid = $payinfo[6];
                $clsid = $payinfo[7];
                $nm = $payload['data']['metadata']['name'];
                $tm = $payload['data']['metadata']['time'];
                $exp = $payload['data']['metadata']['exp'];
                $eml = $payload['data']['metadata']['eml'];
                $lid = $payload['data']['metadata']['lid'];

                $what = '';
                if ($typ == '0') { //School-Student Payment
                    payments::create([
                        'schid' => $schid,
                        'stid' => $stid,
                        'ssnid' => $ssnid,
                        'trmid' => $trmid,
                        'clsid' => $clsid,
                        'name' => $nm,
                        'exp' => $exp,
                        'amt' => $amt,
                        'lid' => $lid,
                    ]);
                    $what = 'School Fees';
                }
                if ($typ == '1') { //Application Fee Paid
                    //WARN: ClassID will be a placeholder in this case, so dont use it
                    student::where('sid', $stid)->update([
                        "rfee" => '1'
                    ]);
                    $what = 'Application Fee';
                }
                if ($typ == '2') { //Acceptance Fee Paid
                    $uid = $stid . $schid . $clsid;
                    afeerec::updateOrCreate(
                        ["uid" => $uid,],
                        [
                            "stid" => $stid,
                            "schid" => $schid,
                            "clsid" => $clsid,
                            "amt" => intval($amt),
                        ]
                    );
                    $what = 'Acceptance Fee';
                }
                // Wrap the email sending logic in a try-catch block
                try {
                    $data = [
                        'name' => $nm,
                        'subject' => 'Payment Received',
                        'body' => 'Your ' . $what . ' payment was received',
                        'link' => env('PORTAL_URL') . '/studentLogin' . '/' . $schid,
                    ];
                    Mail::to($eml)->send(new SSSMails($data));
                } catch (\Exception $e) {
                    // Log the email error, but don't stop the process
                    Log::error('Failed to send email: ' . $e->getMessage());
                }

                payment_refs::create([
                    "ref" => $ref,
                    "amt" => $amt,
                    "time" => $tm,
                ]);
                Log::info('SUCCESS');
            } else {
                Log::info('PLD EXISTS' . json_encode($pld));
            }
        } else {
            Log::info('EVENTS BAD ' . $payload['event']);
        }
        return response()->json(['status' => 'success'], 200);
    }

    //--VENDORS

    /**
     * @OA\Post(
     *     path="/api/setVendor",
     *     tags={"Accounting"},
     *     summary="Create/Update vendor details",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="addr", type="string"),
     *             @OA\Property(property="bnk", type="string"),
     *             @OA\Property(property="anum", type="string"),
     *             @OA\Property(property="aname", type="string"),
     *             @OA\Property(property="goods", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vendor Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setVendor(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phn' => 'required',
            'addr' => 'required',
            'bnk' => 'required',
            'anum' => 'required',
            'aname' => 'required',
            'goods' => 'required',
            'schid' => 'required',
        ]);
        $data = [
            'name' => $request->name,
            'phn' => $request->phn,
            'addr' => $request->addr,
            'bnk' => $request->bnk,
            'anum' => $request->anum,
            'aname' => $request->aname,
            'goods' => $request->goods,
            'schid' => $request->schid,
        ];
        $vendor = [];
        if ($request->has('id')) {
            $vendor = vendor::where('id', $request->id)->first();
            if ($vendor) {
                $vendor->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Vendor Not Found",
                ]);
            }
        } else {
            $vendor = vendor::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Vendor Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getVendorStat/{schid}",
     *     tags={"Accounting"},
     *     summary="Get how many vendors are available",
     *     description="Use this endpoint to get how many vendors are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getVendorStat($schid)
    {
        $total = vendor::where('schid', $schid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getVendorsBySchool/{schid}",
     *     tags={"Accounting"},
     *     summary="Get all Vendors by School",
     *     description="Use this endpoint to get all Vendors by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getVendorsBySchool($schid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = vendor::where('schid', $schid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteVendor/{vid}",
     *     tags={"Accounting"},
     *     summary="Delete a Vendor",
     *     description="Use this endpoint to delete a vendor",
     *
     *     @OA\Parameter(
     *         name="vid",
     *         in="path",
     *         required=true,
     *         description="Vendor ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteVendor($vid)
    {
        $pld = vendor::where('id', $vid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getVendor/{vid}",
     *     tags={"Accounting"},
     *     summary="Get a Vendor",
     *     description="Use this endpoint to get a vendor",
     *
     *     @OA\Parameter(
     *         name="vid",
     *         in="path",
     *         required=true,
     *         description="Vendor ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getVendor($vid)
    {
        $pld = vendor::where('id', $vid)->first();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setExpense",
     *     tags={"Accounting"},
     *     summary="Create/Update expense details",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="desc", type="string"),
     *             @OA\Property(property="tang", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setExpense(Request $request)
    {
        $request->validate([
            'desc' => 'required',
            'tang' => 'required',
            'schid' => 'required',
        ]);
        $data = [
            'desc' => $request->desc,
            'tang' => $request->tang,
            'schid' => $request->schid,
        ];
        $expense = [];
        if ($request->has('id')) {
            $expense = expense::where('id', $request->id)->first();
            if ($expense) {
                $expense->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "expense Not Found",
                ]);
            }
        } else {
            $expense = expense::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "expense Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExpenseStat/{schid}",
     *     tags={"Accounting"},
     *     summary="Get how many expenses are available",
     *     description="Use this endpoint to get how many expense are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExpenseStat($schid)
    {
        $total = expense::where('schid', $schid)->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExpensesBySchool/{schid}",
     *     tags={"Accounting"},
     *     summary="Get all expenses by School",
     *     description="Use this endpoint to get all expenses by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExpensesBySchool($schid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = expense::where('schid', $schid)->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteExpense/{eid}",
     *     tags={"Accounting"},
     *     summary="Delete an Expense",
     *     description="Use this endpoint to delete a expense",
     *
     *     @OA\Parameter(
     *         name="eid",
     *         in="path",
     *         required=true,
     *         description="Expense ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteExpense($eid)
    {
        $pld = expense::where('id', $eid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExpense/{eid}",
     *     tags={"Accounting"},
     *     summary="Get an expense",
     *     description="Use this endpoint to get an expense",
     *
     *     @OA\Parameter(
     *         name="eid",
     *         in="path",
     *         required=true,
     *         description="expense ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExpense($eid)
    {
        $pld = expense::where('id', $eid)->first();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setExternalExpenditure",
     *     tags={"Accounting"},
     *     summary="Create/Update expenditure details",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="vendor", type="string"),
     *             @OA\Property(property="item", type="string"),
     *             @OA\Property(property="pv", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phn", type="string"),
     *             @OA\Property(property="unit", type="string"),
     *             @OA\Property(property="qty", type="integer"),
     *             @OA\Property(property="mode", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="time", type="integer"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="expenditure Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setExternalExpenditure(Request $request)
    {
        $request->validate([
            'vendor' => 'required',
            'item' => 'required',
            'pv' => 'required',
            'dets' => 'required',
            'name' => 'required',
            'phn' => 'required',
            'unit' => 'required',
            'qty' => 'required',
            'mode' => 'required',
            'schid' => 'required',
            'time' => 'required',
            'ssn' => 'required',
            'trm' => 'required',
        ]);
        $data = [
            'vendor' => $request->vendor,
            'item' => $request->item,
            'pv' => $request->pv,
            'dets' => $request->dets,
            'name' => $request->name,
            'phn' => $request->phn,
            'unit' => $request->unit,
            'qty' => $request->qty,
            'mode' => $request->mode,
            'ext' => $request->ext,
            'schid' => $request->schid,
            'time' => $request->time,
            'ssn' => $request->ssn,
            'trm' => $request->trm,
        ];
        $expenditure = [];
        if ($request->has('id')) {
            $expenditure = ext_expenditure::where('id', $request->id)->first();
            if ($expenditure) {
                $expenditure->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "expenditure Not Found",
                ]);
            }
        } else {
            $expenditure = ext_expenditure::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "expenditure Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExternalExpenditureStat/{schid}/{ssn}/{trm}",
     *     tags={"Accounting"},
     *     summary="Get how many expenditure are available",
     *     description="Use this endpoint to get how many expenditure are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExternalExpenditureStat($schid, $ssn, $trm)
    {
        $query = ext_expenditure::query();
        $query->where('schid', $schid);
        if ($ssn !== '0') {
            $query->where('ssn', $ssn);
        }
        if ($trm !== '0') {
            $query->where('trm', $trm);
        }
        $total = $query->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExternalExpenditures/{schid}/{ssn}/{trm}",
     *     tags={"Accounting"},
     *     summary="Get all expenditures by School, Session and Term",
     *     description="Use this endpoint to get all expenditures by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExternalExpenditures($schid, $ssn, $trm)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $query = ext_expenditure::query();
        $query->where('schid', $schid);
        if ($ssn !== '0') {
            $query->where('ssn', $ssn);
        }
        if ($trm !== '0') {
            $query->where('trm', $trm);
        }
        $pld = $query->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteExternalExpenditure/{eid}",
     *     tags={"Accounting"},
     *     summary="Delete an expenditure",
     *     description="Use this endpoint to delete an expenditure",
     *
     *     @OA\Parameter(
     *         name="eid",
     *         in="path",
     *         required=true,
     *         description="expenditure ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteExternalExpenditure($eid)
    {
        $pld = ext_expenditure::where('id', $eid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExternalExpenditure/{eid}",
     *     tags={"Accounting"},
     *     summary="Get an expenditure",
     *     description="Use this endpoint to get an expenditure",
     *
     *     @OA\Parameter(
     *         name="eid",
     *         in="path",
     *         required=true,
     *         description="expenditure ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExternalExpenditure($eid)
    {
        $pld = ext_expenditure::where('id', $eid)->first();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getExternalExpendituresByFilter/{schid}/{ssn}/{trm}",
     *     tags={"Accounting"},
     *     summary="Get all expenditures by School, Session and Term",
     *     description="Use this endpoint to get all expenditures by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="vendor",
     *         in="query",
     *         required=false,
     *         description="Filter by vendor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="item",
     *         in="query",
     *         required=false,
     *         description="Filter by expense heading",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="time",
     *         in="query",
     *         required=false,
     *         description="Filter by time",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="pv",
     *         in="query",
     *         required=false,
     *         description="Filter by pv",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="mode",
     *         in="query",
     *         required=false,
     *         description="Filter by mode",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="unit",
     *         in="query",
     *         required=false,
     *         description="Filter by unit",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="qty",
     *         in="query",
     *         required=false,
     *         description="Filter by qty",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getExternalExpendituresByFilter($schid, $ssn, $trm)
    {
        $start = request()->input('start', 0); // Default start
        $count = request()->input('count', 20); // Default count

        $query = ext_expenditure::query();

        // Base filters
        $query->where('schid', $schid);

        if ($ssn !== '0') {
            $query->where('ssn', $ssn);
        }

        if ($trm !== '0') {
            $query->where('trm', $trm);
        }

        // Filter and sort for fields: time, vendor, pv, mode, unit, qty
        $fields = ['time', 'vendor', 'pv', 'mode', 'unit', 'qty', 'item'];
        foreach ($fields as $field) {
            $qValue = request()->input($field, null);
            if ($qValue !== null && $qValue !== '-1') {
                if ($field === 'time') {
                    if (strpos($qValue, '-') !== false) {
                        $compo = explode("-", $qValue);
                        if (count($compo) == 2) {
                            $frm = $compo[0];
                            $to = $compo[1];
                            $query->where($field, '>=', $frm);
                            $query->where($field, '<=', $to);
                        }
                    }
                } else {
                    $query->where($field, $qValue);
                }
            }
        }

        // Pagination
        $pld = $query->skip($start)->take($count)->get();

        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setInternalExpenditure",
     *     tags={"Accounting"},
     *     summary="Create/Update expenditure details",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="purp", type="string"),
     *             @OA\Property(property="amt", type="string"),
     *             @OA\Property(property="mode", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *             @OA\Property(property="time", type="integer"),
     *             @OA\Property(property="ssn", type="string"),
     *             @OA\Property(property="trm", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="expenditure Updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setInternalExpenditure(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'purp' => 'required',
            'amt' => 'required',
            'mode' => 'required',
            'schid' => 'required',
            'time' => 'required',
            'ssn' => 'required',
            'trm' => 'required',
        ]);
        $data = [
            'name' => $request->name,
            'purp' => $request->purp,
            'amt' => $request->amt,
            'mode' => $request->mode,
            'ext' => $request->ext,
            'schid' => $request->schid,
            'time' => $request->time,
            'ssn' => $request->ssn,
            'trm' => $request->trm,
        ];
        $expenditure = [];
        if ($request->has('id')) {
            $expenditure = in_expenditure::where('id', $request->id)->first();
            if ($expenditure) {
                $expenditure->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "expenditure Not Found",
                ]);
            }
        } else {
            $expenditure = in_expenditure::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "expenditure Updated"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getInternalExpenditureStat/{schid}/{ssn}/{trm}",
     *     tags={"Accounting"},
     *     summary="Get how many expenditure are available",
     *     description="Use this endpoint to get how many expenditure are available",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getInternalExpenditureStat($schid, $ssn, $trm)
    {
        $query = in_expenditure::query();
        if ($ssn !== '0') {
            $query->where('ssn', $ssn);
        }
        if ($trm !== '0') {
            $query->where('trm', $trm);
        }
        $total = $query->count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getInternalExpenditures/{schid}/{ssn}/{trm}",
     *     tags={"Accounting"},
     *     summary="Get all expenditures by School, Session and Term",
     *     description="Use this endpoint to get all expenditures by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getInternalExpenditures($schid, $ssn, $trm)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $query = in_expenditure::query();
        if ($ssn !== '0') {
            $query->where('ssn', $ssn);
        }
        if ($trm !== '0') {
            $query->where('trm', $trm);
        }
        $pld = $query->skip($start)->take($count)->get();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/deleteInternalExpenditure/{eid}",
     *     tags={"Accounting"},
     *     summary="Delete an expenditure",
     *     description="Use this endpoint to delete an expenditure",
     *
     *     @OA\Parameter(
     *         name="eid",
     *         in="path",
     *         required=true,
     *         description="expenditure ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function deleteInternalExpenditure($eid)
    {
        $pld = in_expenditure::where('id', $eid)->delete();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getInternalExpenditure/{eid}",
     *     tags={"Accounting"},
     *     summary="Get an expenditure",
     *     description="Use this endpoint to get an expenditure",
     *
     *     @OA\Parameter(
     *         name="eid",
     *         in="path",
     *         required=true,
     *         description="expenditure ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getInternalExpenditure($eid)
    {
        $pld = in_expenditure::where('id', $eid)->first();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getInternalExpendituresByFilter/{schid}/{ssn}/{trm}",
     *     tags={"Accounting"},
     *     summary="Get all expenditures by School, Session and Term",
     *     description="Use this endpoint to get all expenditures by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="amt",
     *         in="query",
     *         required=false,
     *         description="Filter by amount",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="time",
     *         in="query",
     *         required=false,
     *         description="Filter by time",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="mode",
     *         in="query",
     *         required=false,
     *         description="Filter by mode",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getInternalExpendituresByFilter($schid, $ssn, $trm)
    {
        $start = request()->input('start', 0); // Default start
        $count = request()->input('count', 20); // Default count

        $query = in_expenditure::query();

        // Filter by session and term
        if ($ssn !== '0') {
            $query->where('ssn', $ssn);
        }

        if ($trm !== '0') {
            $query->where('trm', $trm);
        }

        // Handle sorting for amt, time, and mode
        $fields = ['time', 'mode'];
        foreach ($fields as $field) {
            $qValue = request()->input($field, null);
            if ($qValue !== null && $qValue !== '-1') {
                if ($field === 'time') {
                    if (strpos($qValue, '-') !== false) {
                        $compo = explode("-", $qValue);
                        if (count($compo) == 2) {
                            $frm = $compo[0];
                            $to = $compo[1];
                            $query->where($field, '>=', $frm);
                            $query->where($field, '<=', $to);
                        }
                    }
                } else {
                    $query->where($field, $qValue);
                }
            }
        }

        // Pagination
        $pld = $query->skip($start)->take($count)->get();

        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getSchoolHighlights/{schid}/{ssnid}/{trmid}",
     *     tags={"Api"},
     *     summary="Get a particular school's highlighs",
     *     description="Use this endpoint to get a ...",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssnid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolHighlights($schid, $ssnid, $trmid)
    {
        $pendingStudents = student::where('schid', $schid)->where('stat', '0')->count();

        $approvedStudentsMale = student::join('student_basic_data', 'student.sid', '=', 'student_basic_data.user_id')
            ->where('student.schid', $schid)
            ->where('student.stat', '1')
            ->where('student_basic_data.sex', 'M')
            ->count();
        $approvedStudentsFemale = student::join('student_basic_data', 'student.sid', '=', 'student_basic_data.user_id')
            ->where('student.schid', $schid)
            ->where('student.stat', '1')
            ->where('student_basic_data.sex', 'F')
            ->count();

        $declinedStudents = student::where('schid', $schid)->where('stat', '2')->count();
        $deletedStudents = student::where('schid', $schid)->where('stat', '3')->count();

        $pendingStaff = staff::where('schid', $schid)->where('stat', '0')->count();

        $approvedStaffMale = staff::join('staff_basic_data', 'staff.sid', '=', 'staff_basic_data.user_id')
            ->where('staff.schid', $schid)
            ->where('staff.stat', '1')
            ->where('staff_basic_data.sex', 'M')
            ->count();
        $approvedStaffFemale = staff::join('staff_basic_data', 'staff.sid', '=', 'staff_basic_data.user_id')
            ->where('staff.schid', $schid)
            ->where('staff.stat', '1')
            ->where('staff_basic_data.sex', 'F')
            ->count();

        $declinedStaff = staff::where('schid', $schid)->where('stat', '2')->count();
        $deletedStaff = staff::where('schid', $schid)->where('stat', '3')->count();

        $totalStudentPaidRegFee = student::where('schid', $schid)->where('rfee', "1")->count();
        $totalStudentNotPaidRegFee = student::where('schid', $schid)->where('rfee', '!=', "1")->count();

        $totalRevenueOthers = payments::where('schid', $schid)
            ->select(DB::raw("COALESCE(SUM(CAST(amt AS DECIMAL(15,2))), 0) as total"))
            ->value('total');

        $paidStudentsCount = student::where('schid', $schid)
            ->whereExists(function ($query) use ($schid, $ssnid, $trmid) {
                $query->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.stid', 'student.sid')
                    ->where('payments.schid', $schid)
                    ->where('payments.ssnid', $ssnid)
                    ->where('payments.trmid', $trmid);
            })
            ->count();

        $notPaidStudentsCount = student::where('schid', $schid)
            ->whereNotExists(function ($query) use ($schid, $ssnid, $trmid) {
                $query->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.stid', 'student.sid')
                    ->where('payments.schid', $schid)
                    ->where('payments.ssnid', $ssnid)
                    ->where('payments.trmid', $trmid);
            })
            ->count();

        // $totalAmountPaidSchoolFees = student::where('student.schid', $schid)
        // ->whereExists(function ($query) use ($schid, $ssnid, $trmid) {
        //     $query->select(DB::raw(1))
        //         ->from('payments')
        //         ->whereColumn('payments.stid', 'student.sid')
        //         ->where('payments.schid', $schid)
        //         ->where('payments.ssnid', $ssnid)
        //         ->where('payments.trmid', $trmid);
        // })
        // ->join('payments', 'payments.stid', '=', 'student.sid')
        // ->join('clspay', function($join) use ($schid, $ssnid, $trmid) {
        //     $join->on('clspay.clsid', '=', 'payments.clsid')
        //         ->where('clspay.schid', $schid)
        //         ->where('clspay.sesid', $ssnid)
        //         ->where('clspay.trmid', $trmid);
        // })
        // ->sum(DB::raw('IFNULL(CAST(clspay.amt AS DECIMAL(10, 2)), 0)'));

        // $totalAmountUnpaidSchoolFees = student::where('student.schid', $schid)
        // ->whereNotExists(function ($query) use ($schid, $ssnid, $trmid) {
        //     $query->select(DB::raw(1))
        //         ->from('payments')
        //         ->whereColumn('payments.stid', 'student.sid')
        //         ->where('payments.schid', $schid)
        //         ->where('payments.ssnid', $ssnid)
        //         ->where('payments.trmid', $trmid);
        // })
        // ->leftJoin('student_academic_data', 'student.sid', '=', 'student_academic_data.user_id')  // Left join with student_academic_data
        // ->leftJoin('clspay', function($join) use ($schid, $ssnid, $trmid) {
        //     $join->on('clspay.clsid', '=', 'student_academic_data.new_class_main')  // Left join with clspay using new_class_main
        //         ->where('clspay.schid', $schid)
        //         ->where('clspay.sesid', $ssnid)
        //         ->where('clspay.trmid', $trmid);
        // })
        // ->sum(DB::raw('IFNULL(CAST(clspay.amt AS DECIMAL(10, 2)), 0)'));

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "pendingStudents" => $pendingStudents,
                "approvedStudentsMale" => $approvedStudentsMale,
                "approvedStudentsFemale" => $approvedStudentsFemale,
                "declinedStudents" => $declinedStudents,
                "deletedStudents" => $deletedStudents,

                "pendingStaff" => $pendingStaff,
                "approvedStaffMale" => $approvedStaffMale,
                "approvedStaffFemale" => $approvedStaffFemale,
                "declinedStaff" => $declinedStaff,
                "deletedStaff" => $deletedStaff,

                "totalStudentPaidRegFee" => $totalStudentPaidRegFee,
                "totalStudentNotPaidRegFee" => $totalStudentNotPaidRegFee,
                "totalRevenueOthers" => $totalRevenueOthers,
                "paidStudentsCount" => $paidStudentsCount,
                "notPaidStudentsCount" => $notPaidStudentsCount,

            ],
        ]);
    }

    //-- ADMIN

    /**
     * @OA\Post(
     *     path="/api/resetDefaultPassword",
     *     tags={"Admin"},
     *     security={{"bearerAuth": {}}},
     *     summary="change user password to default",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Password reset token sent to mail"),
     * )
     */
    public function resetDefaultPassword(Request $request)
    {
        //Data validation
        $request->validate([
            "uid" => "required",
        ]);
        $usr = User::where("id", $request->uid)->first();
        if ($usr) {
            $usr->update([
                "password" => bcrypt("123456"),
            ]);
            return response()->json([
                "status" => true,
                "message" => "Success. Please tell user to login again"
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "User not found",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/setAdmin",
     *     tags={"Admin"},
     *     summary="Set or update admin details",
     *     description="Set or update admin details. `uid` is unique to each person occupying that particular role. Its a way for you to differentiate people that occupy that office",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="uid", type="string", description="A unique ID for the user. "),
     *             @OA\Property(property="meml", type="string", format="email",),
     *             @OA\Property(property="eml", type="string", format="email",),
     *             @OA\Property(property="lname", type="string", example="Doe"),
     *             @OA\Property(property="oname", type="string", example="John"),
     *             @OA\Property(property="zone", type="string", example="A zone ID"),
     *             @OA\Property(property="dob", type="string", format="string", example="1625097600000"),
     *             @OA\Property(property="state", type="string", example="Lagos"),
     *             @OA\Property(property="lga", type="string", example="Ikeja"),
     *             @OA\Property(property="phn", type="string", example="08012345678"),
     *             @OA\Property(property="role", type="string", example="Manager"),
     *             @OA\Property(property="addr", type="string", example="123 Main St"),
     *             @OA\Property(property="prev", type="string", example="Previous Role"),
     *             @OA\Property(property="date", type="string", format="string", example="1625097600000"),
     *             @OA\Property(property="level", type="string", example="1"),
     *             @OA\Property(property="verif", type="string", example="0")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin Added",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Admin Added")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error")
     *         )
     *     )
     * )
     */
    public function setAdmin(Request $request)
    {
        $request->validate([
            "uid" => "required",
            "meml" => "required",
            "eml" => "required",
            "lname" => "required",
            "oname" => "required",
            "zone" => "required",
            "dob" => "required",
            "state" => "required",
            "lga" => "required",
            "phn" => "required",
            "role" => "required",
            "addr" => "required",
            "prev" => "required",
            "date" => "required",
            "level" => "required|integer",
            "verif" => "required",
        ]);
        $typ = 'a';
        $main_email = $request->meml;
        $usr = User::where("email", $main_email)->where("typ", $typ)->first();
        if (!$usr) {
            $usr = User::create([
                "email" => $main_email,
                "typ" => $typ,
                "password" => bcrypt('123456'),
            ]);
        }
        silo_user::updateOrCreate(
            ["uid" => $request->uid,],
            [
                "eml" => $request->eml,
                "lname" => $request->lname,
                "oname" => $request->oname,
                "zone" => $request->zone,
                "dob" => $request->dob,
                "state" => $request->state,
                "lga" => $request->lga,
                "phn" => $request->phn,
                "role" => $request->role,
                "addr" => $request->addr,
                "prev" => $request->prev,
                "date" => $request->date,
                "level" => $request->level,
                "verif" => $request->verif,
            ]
        );
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Admin Added"
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/adminLogin",
     *     tags={"Unprotected"},
     *     summary="Login as admin",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Login Successfully"),
     * )
     */
    public function adminLogin(Request $request)
    {
        //Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
        $typ = 'a';
        $usr = User::where("email", $request->email)->where('typ', $typ)->first();
        if ($usr) {
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password,
            ]);
            if (!empty($token)) {
                return response()->json([
                    "status" => true,
                    "message" => "User login successfully",
                    "token" => $token,
                    "pld" => $usr
                ]);
            }
        } else {
            $portalUrl = env('PORTAL_URL');
            $adminEml = 'admin@' . substr($portalUrl, 14);
            if ($request->email == $adminEml) {
                $usr = User::create([
                    "email" => $adminEml,
                    "typ" => $typ,
                    "verif" => '1',
                    "password" => bcrypt('123456'),
                ]);
                return response()->json([
                    "status" => false,
                    "message" => "Please try again",
                ], 400);
            }
        }
        return response()->json([
            "status" => false,
            "message" => "Invalid login details",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/resolveIDtoEmail",
     *     tags={"Unprotected"},
     *     summary="Resolve Staff/Student/Partner ID to their email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="stid", type="string"),
     *             @OA\Property(property="typ", type="string"),
     *             @OA\Property(property="schid", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Action successful",
     *     ),
     * )
     */
    public function resolveIDtoEmail(Request $request)
    {
        //Data validation
        $request->validate([
            "stid" => "required",
            "typ" => "required",
            "schid" => "required",
        ]);
        $typ = $request->typ;
        if ($typ == 'w') {
            $stf = [];
            $compo = explode("/", $request->stid);
            if (count($compo) == 3) {
                $sch3 = $compo[0];
                $count = $compo[2];
                $stf = staff::where("schid", $request->schid)->where("count", $count)->first();
            } else {
                $stf = staff::where("cuid", $request->stid)->first();
            }
            if ($stf) {
                $usr = User::where("typ", $typ)->where("id", $stf->sid)->first();
                return response()->json([
                    "status" => true,
                    "message" => "successful",
                    "pld" => $usr,
                ]);
            }
        }
        if ($typ == 'z') {
            $std = [];
            $compo = explode("/", $request->stid);
            if (count($compo) == 4) {
                $sch3 = $compo[0];
                $year = $compo[1];
                $term = $compo[2];
                $count = $compo[3];
                $std = student::where("schid", $request->schid)->where("year", $year)->where("term", $term)
                    ->where("count", $count)->first();
            } else {
                $std = student::where("cuid", $request->stid)->first();
            }
            if ($std) {
                $usr = User::where("typ", $typ)->where("id", $std->sid)->first();
                return response()->json([
                    "status" => true,
                    "message" => "successful",
                    "pld" => $usr,
                ]);
            }
        }
        return response()->json([
            "status" => false,
            "message" => "Invalid ID",
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/setSubject",
     *     tags={"Admin"},
     *     summary="Set A General Subject. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a subject.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSubj(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $data = [
            'name' => $request->name,
        ];
        $subj = [];
        if ($request->has('id')) {
            $subj = subj::where('id', $request->id)->first();
            if ($subj) {
                $subj->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Subject Not Found",
                ]);
            }
        } else {
            $subj = subj::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $subj
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchAdminSubjects",
     *     tags={"Admin"},
     *     summary="Full text search on admin subjects",
     *     description=" Use this endpoint for Full text search on subjects",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchAdminSubjects()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        if ($search) {
            $pld = subj::whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(3)
                ->get();
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSubjectsStat",
     *     tags={"Admin"},
     *     summary="Get Subjects Stat",
     *     description="Use this endpoint to get stats of subjects",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSubjsStat()
    {
        $total = subj::count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSubjects",
     *     tags={"Admin"},
     *     summary="Get Subjects",
     *     description="Use this endpoint to get a list of Subjects",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve. Default is 5",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSubjs()
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $subj = subj::skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $subj,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSubject/{sbid}",
     *     tags={"Admin"},
     *     summary="Get a particular subject",
     *     description="Use this endpoint to get a particular subject",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="sbid",
     *         in="path",
     *         required=true,
     *         description="Unique Subject ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSubj($sbid)
    {
        $sbj = subj::where('id', $sbid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $sbj,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setClass",
     *     tags={"Admin"},
     *     summary="Set The General Class. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set information about a class.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setCls(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $data = [
            'name' => $request->name,
        ];
        $cls = [];
        if ($request->has('id')) {
            $cls = cls::where('id', $request->id)->first();
            if ($cls) {
                $cls->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Class Not Found",
                ]);
            }
        } else {
            $cls = cls::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $cls
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchAdminClasses",
     *     tags={"Admin"},
     *     summary="Full text search on admin classes",
     *     description=" Use this endpoint for Full text search on classes",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchAdminClasses()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        if ($search) {
            $pld = cls::whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(3)
                ->get();
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClassesStat",
     *     tags={"Admin"},
     *     summary="Get Classes Stat",
     *     description="Use this endpoint to get stats of classes",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClssStat()
    {
        $total = cls::count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClasses",
     *     tags={"Admin"},
     *     summary="Get Classes",
     *     description="Use this endpoint to get a list of classes",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve. Default is 5",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getClss()
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $cls = cls::skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $cls,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getClass/{cid}",
     *     tags={"Admin"},
     *     summary="Get a particular class",
     *     description="Use this endpoint to get a particular class",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="cid",
     *         in="path",
     *         required=true,
     *         description="Unique Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getCls($cid)
    {
        $cls = cls::where('id', $cid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $cls,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSession",
     *     tags={"Admin"},
     *     summary="Set a General Session",
     *     description="This endpoint is used to set information about a session.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="year", type="string"),
     *             @OA\Property(property="name", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSesn(Request $request)
    {
        $request->validate([
            'year' => 'required',
            'name' => 'required',
        ]);
        $ssn = sesn::updateOrCreate([
            'year' => $request->year
        ], [
            'name' => $request->name,
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $ssn
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSessions",
     *     tags={"Admin"},
     *     summary="Get Session",
     *     description="Use this endpoint to get a list of sessions",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve. Default is 5",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSesns()
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $ssn = sesn::skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $ssn,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setTerm",
     *     tags={"Admin"},
     *     summary="Set a General Term",
     *     description="This endpoint is used to set information about a general term.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="no", type="string"),
     *             @OA\Property(property="name", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setTrm(Request $request)
    {
        $request->validate([
            'no' => 'required',
            'name' => 'required',
        ]);
        $trm = trm::updateOrCreate([
            'no' => $request->no
        ], [
            'name' => $request->name,
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $trm
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getTerms",
     *     tags={"Admin"},
     *     summary="Get Terms",
     *     description="Use this endpoint to get a list of terms",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getTrms()
    {
        $trm = trm::get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $trm,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getTerm/{no}",
     *     tags={"Admin"},
     *     summary="Get a particular Term",
     *     description="Use this endpoint to get a term",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="no",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getTrm($no)
    {
        $trm = trm::where('no', $no)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $trm,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setAdminStaffRole",
     *     tags={"Admin"},
     *     summary="Set Staff Role. If new, no need for id param. Otherwise, specify",
     *     description="This endpoint is used to set staff role.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setAdminStaffRole(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $data = [
            'name' => $request->name,
        ];
        $staff_role = [];
        if ($request->has('id')) {
            $staff_role = staff_role::where('id', $request->id)->first();
            if ($staff_role) {
                $staff_role->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Class Not Found",
                ]);
            }
        } else {
            $staff_role = staff_role::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $staff_role
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchAdminStaffRole",
     *     tags={"Admin"},
     *     summary="Full text search on admin staff roles",
     *     description=" Use this endpoint for Full text search on staff roles",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchAdminStaffRole()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        if ($search) {
            $pld = staff_role::whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(3)
                ->get();
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAdminStaffRoleStat",
     *     tags={"Admin"},
     *     summary="Get Staff Roles Stat",
     *     description="Use this endpoint to get stats of staff roles",
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAdminStaffRoleStat()
    {
        $total = staff_role::count();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAdminStaffRoles",
     *     tags={"Admin"},
     *     summary="Get Staff Roles",
     *     description="Use this endpoint to get a list of staff roles",
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve. Default is 5",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAdminStaffRoles()
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $staff_role = staff_role::skip($start)->take($count)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $staff_role,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getAdminStaffRole/{rid}",
     *     tags={"Admin"},
     *     summary="Get a particular staff role",
     *     description="Use this endpoint to get a particular staff role",
     *     @OA\Parameter(
     *         name="rid",
     *         in="path",
     *         required=true,
     *         description="Unique Staff Role ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getAdminStaffRole($rid)
    {
        $staff_role = staff_role::where('id', $rid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $staff_role,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/setSchoolStaffRole",
     *     tags={"Admin"},
     *     summary="Set Staff Role for a particular school.",
     *     description="This endpoint is used to set staff role for a school.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string",),
     *             @OA\Property(property="role", type="string",),
     *             @OA\Property(property="schid", type="string",),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setSchoolStaffRole(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'role' => 'required',
            'schid' => 'required',
        ]);
        $staff_role = sch_staff_role::create([
            'name' => $request->name,
            'role' => $request->role,
            'schid' => $request->schid,
        ]);
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $staff_role
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolStaffRoles/{schid}",
     *     tags={"Admin"},
     *     summary="Get Staff Roles",
     *     description="Use this endpoint to get a list of staff roles",
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="school ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolStaffRoles($schid)
    {
        $staff_role = sch_staff_role::where('schid', $schid)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $staff_role,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolStaffRole/{rid}",
     *     tags={"Admin"},
     *     summary="Get a particular staff role",
     *     description="Use this endpoint to get a particular staff role",
     *     @OA\Parameter(
     *         name="rid",
     *         in="path",
     *         required=true,
     *         description="Unique Staff Role ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolStaffRole($rid)
    {
        $staff_role = sch_staff_role::where('role', $rid)->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $staff_role,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStaffRoleByClass/{stid}/{schid}/{clsid}/{ssn}",
     *     tags={"Admin"},
     *     summary="Get a particular staff role",
     *     description="Use this endpoint to get a particular staff role",
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Staff ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffRoleByClass($stid, $schid, $clsid, $ssn)
    {
        $pld = old_staff::where("sid", $stid)
            ->where("schid", $schid)
            ->where("clsm", $clsid)
            ->where("ssn", $ssn)
            ->first();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getSchoolsByPartner/{uid}",
     *     tags={"Admin"},
     *     summary="Get Partner's Schools",
     *     description="Use this endpoint to get a Partner's Schools.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="pid",
     *         in="path",
     *         required=true,
     *         description="ID of the partner. User ID, not partner code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolsByPartner($pid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = school_basic_data::where('pcode', $pid)->skip($start)->take($count)->get();
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->user_id;
            $genData = school_general_data::where('user_id', $user_id)->first();
            $mergedData = array_merge($member->toArray(), $genData->toArray());
            $pld[] = $mergedData;
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchSchools",
     *     tags={"Admin"},
     *     summary="Full text search on school names",
     *     description=" Use this endpoint for Full text search on school names",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchSchools()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        if ($search) {
            $members = school_basic_data::whereRaw("MATCH(sname) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(sname) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(2)
                ->get();
            $pld = [];
            foreach ($members as $member) {
                $user_id = $member->user_id;
                $genData = school_general_data::where('user_id', $user_id)->first();
                $mergedData = array_merge($member->toArray(), $genData->toArray());
                $pld[] = $mergedData;
            }
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchPartners",
     *     tags={"Admin"},
     *     summary="Full text search on partner names",
     *     description=" Use this endpoint for Full text search on partner names",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchPartners()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        if ($search) {
            $members = partner_basic_data::whereRaw("MATCH(fname, lname, mname) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(fname, lname, mname) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(2)
                ->get();
            $pld = [];
            foreach ($members as $member) {
                $user_id = $member->user_id;
                $genData = school_general_data::where('user_id', $user_id)->first();
                $mergedData = array_merge($member->toArray(), $genData->toArray());
                $pld[] = $mergedData;
            }
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getSchoolsByPay/{pid}",
     *     tags={"Admin"},
     *     summary="ADMIN: Get Schools by pay id",
     *     description="Use this endpoint to get schools",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="pid",
     *         in="path",
     *         required=true,
     *         description="pay ID - 0 or 1",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success. The response is split into b:BASIC_DATA and g:GENERAL_DATA", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolsByPay($pid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = school_basic_data::where('pay', $pid)->skip($start)->take($count)->get();
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->user_id;
            $genData = school_general_data::where('user_id', $user_id)->first();
            $pld[] = [
                'b' => $member,
                'g' => $genData,
            ];
        }
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolsByStat/{stat}",
     *     tags={"Admin"},
     *     summary="ADMIN: Get Schools by stat id",
     *     description="Use this endpoint to get schools",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="stat",
     *         in="path",
     *         required=true,
     *         description="Stat ID - 0 or 1",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolsByStat($stat)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = school::where('stat', $stat)->skip($start)->take($count)->get();
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->user_id;
            $basicData = school_basic_data::where('user_id', $user_id)->first();
            $genData = school_general_data::where('user_id', $user_id)->first();
            $pld[] = [
                'b' => $basicData,
                'g' => $genData,
            ];
        }
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchools",
     *     tags={"Admin"},
     *     summary="ADMIN: Get a list of Schools ",
     *     description="Use this endpoint to get a list of schools",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchools()
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = school::take($count)->skip($start)->get();
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->sid;
            $webData = school_web_data::where('user_id', $user_id)->first();
            $pld[] = [
                's' => $member,
                'w' => $webData,
            ];
        }
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getSchoolsStat",
     *     tags={"Admin"},
     *     summary="ADMIN: Get total no. of Schools",
     *     description="Use this endpoint to Get total no. of Schools",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getSchoolsStat()
    {
        $total = school::count();
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStudentsBySchool/{schid}/{stat}",
     *     summary="Get students by school ID and status",
     *     description="Fetch a paginated list of active students filtered by school, status, class, term, and year.",
     *     operationId="getStudentsBySchool",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="stat",
     *         in="path",
     *         required=true,
     *         description="Student status (numeric or code)",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Offset for pagination",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to return",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="cls",
     *         in="query",
     *         required=false,
     *         description="Class ID filter; use 'zzz' to fetch all classes",
     *         @OA\Schema(type="string", example="13")
     *     ),
     *     @OA\Parameter(
     *         name="term",
     *         in="query",
     *         required=false,
     *         description="Term filter (from student table)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         description="Year filter (from student table)",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sid", type="integer", example=101),
     *                     @OA\Property(property="lname", type="string", example="Doe"),
     *                     @OA\Property(property="fname", type="string", example="John"),
     *                     @OA\Property(property="term", type="integer", example=3),
     *                     @OA\Property(property="year", type="integer", example=2024),
     *                     @OA\Property(property="new_class_main", type="string", example="13")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     )
     * )
     */
    public function getStudentsBySchool(Request $request, $schid, $stat, $cls = 'zzz')
    {
        $start = $request->query('start', 0);
        $count = $request->query('count', 20);
        $cls   = $request->query('cls', 'zzz');
        $term  = $request->query('term', null);
        $year  = $request->query('year', null);

        if ($cls !== 'zzz') {
            $query = Student::join('student_academic_data', 'student.sid', '=', 'student_academic_data.user_id')
                ->where('student.schid', $schid)
                ->where('student.stat', $stat)
                ->where('student_academic_data.new_class_main', $cls);
        } else {
            $query = Student::where('schid', $schid)
                ->where('stat', $stat);
        }

        // Apply term filter if provided
        if (!empty($term)) {
            $query->where('student.term', $term);
        }

        // Apply year filter if provided
        if (!empty($year)) {
            $query->where('student.year', $year);
        }

        $members = $query->orderBy('student.lname', 'asc')
            ->skip($start)
            ->take($count)
            ->get();

        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->sid;

            $academicData = student_academic_data::where('user_id', $user_id)->first();
            $basicData    = student_basic_data::where('user_id', $user_id)->first();

            $pld[] = [
                's' => $member,
                'b' => $basicData,
                'a' => $academicData,
            ];
        }

        return response()->json([
            "status"  => true,
            "message" => "Success",
            "pld"     => $pld,
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/searchStudents",
     *     tags={"Api"},
     *     summary="Full text search on students",
     *     description=" Use this endpoint for Full text search on students",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stat",
     *         in="query",
     *         required=true,
     *         description="Status of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="cls",
     *         in="query",
     *         required=false,
     *         description="Filter by class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchStudents()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        $schid = request()->input('schid');
        $stat = request()->input('stat');
        $cls = '-1';
        if (request()->has('cls')) {
            $cls = request()->input('cls');
        }
        if ($search) {
            $members = [];
            if ($cls != '-1') {
                $members = student::join('student_academic_data', 'student.sid', '=', 'student_academic_data.user_id')
                    ->where('student.schid', $schid)
                    ->where('student_academic_data.new_class_main', $cls)
                    ->whereRaw("MATCH(fname, lname) AGAINST(?)", [$search])
                    ->orderByRaw("MATCH(fname, lname) AGAINST('$search') DESC")
                    ->take(3)
                    ->get();
            } else {
                $members = student::where('schid', $schid)->where('stat', $stat)
                    ->whereRaw("MATCH(fname, lname) AGAINST(?)", [$search])
                    ->orderByRaw("MATCH(fname, lname) AGAINST('$search') DESC")
                    ->take(3)
                    ->get();
            }
            $pld = [];
            foreach ($members as $member) {
                $user_id = $member->sid;
                $academicData = student_academic_data::where('user_id', $user_id)->first();
                $basicData = student_basic_data::where('user_id', $user_id)->first();
                $pld[] = [
                    's' => $member,
                    'b' => $basicData,
                    'a' => $academicData,
                ];
            }
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchOldStudents",
     *     tags={"Api"},
     *     summary="Full text search on old students",
     *     description=" Use this endpoint for Full text search on students",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="cls",
     *         in="query",
     *         required=true,
     *         description="Filter by class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="arm",
     *         in="query",
     *         required=true,
     *         description="Filter by class arm",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="query",
     *         required=true,
     *         description="Filter by Session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchOldStudents()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        $schid = request()->input('schid');
        $cls = request()->input('cls');
        $arm = request()->input('arm');
        $ssn = request()->input('ssn');
        if ($search) {
            $pld = [];
            if ($arm != "-1") {
                $pld = old_student::where('schid', $schid)
                    ->where('clsm', $cls)
                    ->where('clsa', $arm)
                    ->where('ssn', $ssn)
                    ->whereRaw("MATCH(fname, lname) AGAINST(?)", [$search])
                    ->orderByRaw("MATCH(fname, lname) AGAINST('$search') DESC")
                    ->take(3)
                    ->get();
            } else {
                $pld = old_student::where('schid', $schid)
                    ->where('clsm', $cls)
                    ->where('ssn', $ssn)
                    ->whereRaw("MATCH(fname, lname) AGAINST(?)", [$search])
                    ->orderByRaw("MATCH(fname, lname) AGAINST('$search') DESC")
                    ->take(3)
                    ->get();
            }
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStudentsStatBySchool",
     *     operationId="getStudentsStatBySchool",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get total students by school, status, class, session (year), and term",
     *     description="Returns total number of students filtered by school ID, student status, class (optional), session (year), and term",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="stat",
     *         in="query",
     *         required=true,
     *         description="Student status (e.g., active, inactive)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="cls",
     *         in="query",
     *         required=false,
     *         description="Class (optional, defaults to all classes)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sesn",
     *         in="query",
     *         required=false,
     *         description="Academic session (year)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="query",
     *         required=false,
     *         description="Academic term (e.g., 1st Term, 2nd Term)",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=87)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid parameters"
     *     )
     * )
     */


    public function getStudentsStatBySchool(Request $request)
    {
        $schid = $request->query('schid');
        $stat = $request->query('stat');
        $cls = $request->query('cls', 'zzz'); // default to 'zzz'
        $year = $request->query('sesn');      // mapped from sesn to year
        $term = $request->query('trm');       // mapped from trm to term

        $total = 0;

        if ($cls !== 'zzz') {
            $query = student::join('student_academic_data', 'student.sid', '=', 'student_academic_data.user_id')
                ->where('student.schid', $schid)
                ->where('student.stat', $stat)
                ->where('student.status', 'active') // Added condition
                ->where('student_academic_data.new_class_main', $cls);

            if (!is_null($year)) {
                $query->where('student.year', $year);
            }

            if (!is_null($term)) {
                $query->where('student.term', $term);
            }

            $total = $query->count();
        } else {
            $query = student::where('schid', $schid)
                ->where('stat', $stat)
                ->where('status', 'active'); // Added condition

            if (!is_null($year)) {
                $query->where('year', $year);
            }

            if (!is_null($term)) {
                $query->where('term', $term);
            }

            $total = $query->count();
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/getStudents",
     *     tags={"Admin"},
     *     summary="ADMIN: Get Students",
     *     description="Use this endpoint to get students",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStudents()
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = student::take($count)->skip($start)->get();
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->sid;
            $academicData = student_academic_data::where('user_id', $user_id)->first();
            $basicData = student_basic_data::where('user_id', $user_id)->first();
            $pld[] = [
                's' => $member,
                'b' => $basicData,
                'a' => $academicData,
            ];
        }
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStaff",
     *     tags={"Admin"},
     *
     *     summary="Get a staff",
     *     description="Use this endpoint to get a staff.",
     *     @OA\Parameter(
     *         name="uid",
     *         in="query",
     *         required=true,
     *         description="User Id of the student",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="combined",
     *         in="query",
     *         required=false,
     *         description="should be combined?",
     *         @OA\Schema(type="boolean")
     *     ),
     *      @OA\Parameter(
     *         name="uid",
     *         in="query",
     *         required=true,
     *         description="UID of the student",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaff()
    {
        $combined = false;
        if (request()->has('combined')) {
            $combined = request()->input('combined');
        }
        $uid = '';
        if (request()->has('uid')) {
            $uid = request()->input('uid');
        }
        if ($uid == '') {
            return response()->json([
                "status" => false,
                "message" => "No UID provided",
            ], 400);
        }
        $pld = [];
        if ($combined) {
            $members = [];
            $compo = explode("/", $uid);
            if (count($compo) == 4) {
                $sch3 = $compo[0];
                $count = $compo[2];
                $members = staff::where("sch3", $sch3)->where("count", $count)->get();
            } else {
                $members = staff::where("cuid", $uid)->get();
            }
            foreach ($members as $member) {
                $user_id = $member->sid;
                $profData = staff_prof_data::where('user_id', $user_id)->first();
                $basicData = staff_basic_data::where('user_id', $user_id)->first();
                $pld[] = [
                    's' => $member,
                    'b' => $basicData,
                    'p' => $profData,
                ];
            }
        } else {
            $pld = staff::where("sid", $uid)->first();
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    // public function getStaff() {
    //     $combined = false;
    //     if (request()->has('combined')) {
    //         $combined = request()->input('combined');
    //     }

    //     $uid = '';
    //     if (request()->has('uid')) {
    //         $uid = request()->input('uid');
    //     }

    //     if ($uid == '') {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "No UID provided",
    //         ], 400);
    //     }

    //     $pld = [];

    //     if ($combined) {
    //         $members = [];
    //         $compo = explode("/", $uid);

    //         if (count($compo) == 4) {
    //             $sch3 = $compo[0];
    //             $count = $compo[2];
    //             $members = staff::where("sch3", $sch3)->where("count", $count)->get();
    //         } else {
    //             $members = staff::where("cuid", $uid)->get();
    //         }

    //         foreach ($members as $member) {
    //             $user_id = $member->sid;

    //             $profData = staff_prof_data::where('user_id', $user_id)->first();
    //             $basicData = staff_basic_data::where('user_id', $user_id)->first();

    //             // Get role names from staff_role table
    //             $roleName = optional(staff_role::find($member->role))->name;
    //             $role2Name = optional(staff_role::find($member->role2))->name;

    //             $pld[] = [
    //                 's' => $member,
    //                 'b' => $basicData,
    //                 'p' => $profData,
    //                 'role_name' => $roleName,
    //                 'role2_name' => $role2Name,
    //             ];
    //         }
    //     } else {
    //         $staff = staff::where("sid", $uid)->first();

    //         if ($staff) {
    //             $profData = staff_prof_data::where('user_id', $staff->sid)->first();
    //             $basicData = staff_basic_data::where('user_id', $staff->sid)->first();

    //             // Get role names from staff_role table
    //             $roleName = optional(staff_role::find($staff->role))->name;
    //             $role2Name = optional(staff_role::find($staff->role2))->name;

    //             $pld = [
    //                 's' => $staff,
    //                 'b' => $basicData,
    //                 'p' => $profData,
    //                 'role_name' => $roleName,
    //                 'role2_name' => $role2Name,
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $pld,
    //     ]);
    // }


    /**
     * @OA\Get(
     *     path="/api/getStaffBySchool/{schid}/{stat}/{cls?}",
     *     tags={"Admin"},
     *     summary="ADMIN: Get staff by school id",
     *     description="Use this endpoint to get staff by school",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stat",
     *         in="path",
     *         required=true,
     *         description="Status of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="No of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffBySchool($schid, $stat, $cls = 'zzz')
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $members = [];
        if ($cls !== 'zzz') {
            $members = []; //TODO DO it join...
        } else {
            $members = staff::where('schid', $schid)->where('stat', $stat)->orderBy('sid', 'desc')->skip($start)->take($count)->get();
        }
        $pld = [];
        foreach ($members as $member) {
            $user_id = $member->sid;
            $basicData = staff_basic_data::where('user_id', $user_id)->first();
            $pld[] = [
                's' => $member,
                'b' => $basicData,
            ];
        }
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/searchStaff",
     *     tags={"Api"},
     *     summary="Full text search on staff",
     *     description=" Use this endpoint for Full text search on staff",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function searchStaff()
    {
        $search = null;
        if (request()->has('search')) {
            $search = request()->input('search');
        }
        $schid = request()->input('schid');
        if ($search) {
            $members = staff::where('schid', $schid)
                ->whereRaw("MATCH(fname, lname) AGAINST(? IN BOOLEAN MODE)", [$search])
                ->orderByRaw("MATCH(fname, lname) AGAINST(? IN BOOLEAN MODE) DESC", [$search])
                ->take(3)
                ->get();
            $pld = [];
            foreach ($members as $member) {
                $user_id = $member->sid;
                $basicData = staff_basic_data::where('user_id', $user_id)->first();
                $pld[] = [
                    's' => $member,
                    'b' => $basicData,
                ];
            }
            return response()->json([
                "status" => true,
                "message" => "Success",
                "pld" => $pld
            ]);
        }
        return response()->json([
            "status" => false,
            "message" => "The Search param is required"
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/getStaffStatBySchool/{schid}/{stat}/{cls?}",
     *     tags={"Admin"},
     *     summary="ADMIN: Get how many Staff by school id",
     *     description="Use this endpoint to get how many Staff by school",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stat",
     *         in="path",
     *         required=true,
     *         description="Status of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function getStaffStatBySchool($schid, $stat, $cls = 'zzz')
    {
        $total = 0;
        if ($cls !== 'zzz') {
            $total = []; //TODO join...
        } else {
            $total = staff::where('schid', $schid)->where('stat', $stat)->count();
        }
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => [
                "total" => $total
            ],
        ]);
    }



    /**
     * @OA\Post(
     *     path="/api/setAnnouncements",
     *     tags={"Admin"},
     *     summary="Create Announcement",
     *     description="ADMIN: Use this endpoint to create an announcement.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="title", type="string", description="Title of the announcement"),
     *             @OA\Property(property="msg", type="string", description="Message content of the announcement"),
     *             @OA\Property(property="time", type="string", description="Time of the announcement"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Announcement created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function setAnnouncements(Request $request)
    {
        $request->validate([
            "title" => "required",
            "msg" => "required",
            "time" => "required",
        ]);
        announcements::create([
            "title" => $request->title,
            "msg" => $request->msg,
            "time" => $request->time,
        ]);
        // Respond
        return response()->json([
            "status" => true,
            "message" => "Announcement Added"
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/sendMail",
     *     tags={"Admin"},
     *     summary="Send an email",
     *     description="ADMIN: Use this endpoint to create an announcement.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="subject", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="link", type="string"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Announcement created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function sendMail(Request $request)
    {
        $request->validate([
            "name" => "required",
            "email" => "required",
            "subject" => "required",
            "body" => "required",
            "link" => "required",
        ]);
        $data = [
            'name' => $request->name,
            'subject' => $request->subject,
            'body' => $request->body,
            'link' => $request->link,
        ];

        Mail::to($request->email)->send(new SSSMails($data));

        return response()->json([
            "status" => true,
            "message" => "Mailed Successfully",
        ]);
    }



    //-- General

    /**
     * @OA\Get(
     *     path="/api/checkTokenValidity",
     *     tags={"General"},
     *     summary="Check if user is still logged in",
     *     description="No params needed except bearer token. If you get a 200, the token is still valid",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function checkTokenValidity()
    {
        return response()->json([
            "status" => true,
            "message" => "Token OK",
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/logout",
     *     tags={"General"},
     *     summary="Logout a user",
     *     description="No params needed",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response="200", description="Success", @OA\JsonContent()),
     * )
     */
    public function logout()
    {
        auth()->logout();
        return response()->json([
            "status" => true,
            "message" => "Logout successful",
        ]);
    }

    //--- Only for mess handling---

    public function mgetSchools()
    {
        $pld = school::all();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    public function mgetSchoolClasses($schid)
    {
        $pld = school_class::where('schid', $schid)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    public function mgetStudentInSchoolAndClass($schid, $cls)
    {
        $pld = student::join('student_academic_data', 'student.sid', '=', 'student_academic_data.user_id')
            ->where('student.schid', $schid)
            ->where('student_academic_data.new_class_main', $cls)
            ->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    public function mgetStudentSubjects($stid)
    {
        $pld = student_subj::where("stid", $stid)->get();
        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }

    ////////////////////////////////////////////////////////////

    /**
     * @OA\Post(
     *     path="/api/setAcceptanceAcct",
     *     summary="Create or update an acceptance account with Paystack subaccount integration",
     *     tags={"Accounts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "anum", "bnk", "aname"},
     *             @OA\Property(property="schid", type="string", example="12345", description="School ID"),
     *
     *             @OA\Property(property="anum", type="string", example="0123456789", description="Account Number"),
     *             @OA\Property(property="bnk", type="string", example="044", description="Bank Code"),
     *             @OA\Property(property="aname", type="string", example="John Doe", description="Account Name"),
     *             @OA\Property(property="id", type="integer", example=1, description="Optional ID for updating an existing account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account and Paystack subaccount created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account and Paystack Subaccount Created Successfully"),
     *             @OA\Property(property="paystack_data", type="object", example={"subaccount_code": "SUB_12345", "business_name": "Business_67890"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create Paystack subaccount",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to Create Paystack Subaccount"),
     *             @OA\Property(property="error", type="string", example="Error details from Paystack")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account Not Found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object", example={"schid": {"The schid field is required."}})
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="http",
     *         scheme="bearer"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */


    public function setAcceptanceAcct(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'anum' => 'required',
            'bnk'  => 'required',
            'aname' => 'required',
        ]);

        $data = [
            'schid' => $request->schid,
            'anum' => $request->anum,
            'bnk' => $request->bnk,
            'aname' => $request->aname,
        ];

        if ($request->has('id')) {
            $acct = acceptance_acct::find($request->id);
            if ($acct) {
                $acct->update($data);
                return response()->json([
                    "status" => true,
                    "message" => "Account Updated",
                ]);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Account Not Found",
                ], 404);
            }
        } else {
            $acct = acceptance_acct::create($data);

            // Automatically generate Paystack-required fields
            $business_name = "Business_" . uniqid(); // Generate a unique business name
            $percentage_charge = 0; // Default percentage charge
            $settlement_bank = $request->bnk; // Use the bank user provided

            // Step 1: Create Paystack subaccount
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/subaccount', [
                'business_name' => $business_name,
                'account_number' => $request->anum,
                'bank_code' => $request->bnk,
                'percentage_charge' => $percentage_charge,
                'settlement_bank' => $settlement_bank,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Step 2: Store subaccount details in the database
                acceptance_sub_acct::create([
                    'acct_id' => $acct->id,
                    'schid' => $request->schid,
                    'subaccount_code' => $data['data']['subaccount_code'],
                    'percentage_charge' => $percentage_charge,
                ]);

                return response()->json([
                    "status" => true,
                    "message" => "Account and Paystack Subaccount Created Successfully",
                    "paystack_data" => $data,
                ]);
            } else {
                // Delete the main account if Paystack subaccount creation fails
                $acct->delete();

                return response()->json([
                    "status" => false,
                    "message" => "Failed to Create Paystack Subaccount",
                    "error" => $response->body(),
                ], 400);
            }
        }
    }




    /**
     * @OA\Post(
     *     path="/api/setApplicationAcct",
     *     summary="Create or update an application account with Paystack subaccount integration",
     *     tags={"Accounts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "anum", "bnk", "aname"},
     *             @OA\Property(property="schid", type="string", example="12345", description="School ID"),
     *
     *             @OA\Property(property="anum", type="string", example="0123456789", description="Account Number"),
     *             @OA\Property(property="bnk", type="string", example="044", description="Bank Code"),
     *             @OA\Property(property="aname", type="string", example="John Doe", description="Account Name"),
     *             @OA\Property(property="id", type="integer", example=1, description="Optional ID for updating an existing account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account and Paystack subaccount created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account and Paystack Subaccount Created Successfully"),
     *             @OA\Property(property="paystack_data", type="object", example={"subaccount_code": "SUB_12345", "business_name": "Business_67890"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account Not Found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create Paystack subaccount",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to Create Paystack Subaccount"),
     *             @OA\Property(property="error", type="string", example="Error details from Paystack")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object", example={"schid": {"The schid field is required."}})
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="http",
     *         scheme="bearer"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */

    public function setApplicationAcct(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'anum' => 'required',
            'bnk'  => 'required',
            'aname' => 'required',
        ]);

        $data = [
            'schid' => $request->schid,
            'anum' => $request->anum,
            'bnk' => $request->bnk,
            'aname' => $request->aname,
        ];

        if ($request->has('id')) {
            $acct = application_acct::find($request->id);
            if ($acct) {
                $acct->update($data);
                return response()->json([
                    "status" => true,
                    "message" => "Account Updated",
                ]);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Account Not Found",
                ], 404);
            }
        } else {
            $acct = application_acct::create($data);

            // Automatically generate Paystack-required fields
            $business_name = "Business_" . uniqid(); // Generate a unique business name
            $percentage_charge = 0; // Default percentage charge
            $settlement_bank = $request->bnk; // Use the bank user provided

            // Step 1: Create Paystack subaccount
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/subaccount', [
                'business_name' => $business_name,
                'account_number' => $request->anum,
                'bank_code' => $request->bnk,
                'percentage_charge' => $percentage_charge,
                'settlement_bank' => $settlement_bank,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Step 2: Store subaccount details in the database
                application_sub_acct::create([
                    'acct_id' => $acct->id,
                    'schid' => $request->schid,
                    'subaccount_code' => $data['data']['subaccount_code'],
                    'percentage_charge' => $percentage_charge,
                ]);

                return response()->json([
                    "status" => true,
                    "message" => "Account and Paystack Subaccount Created Successfully",
                    "paystack_data" => $data,
                ]);
            } else {
                // Delete the main account if Paystack subaccount creation fails
                $acct->delete();

                return response()->json([
                    "status" => false,
                    "message" => "Failed to Create Paystack Subaccount",
                    "error" => $response->body(),
                ], 400);
            }
        }
    }



    /**
     * @OA\Get(
     *     path="/api/getAcctApp/{schid}",
     *     tags={"Accounts"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all Accounts by School",
     *     description="Use this endpoint to get all Accounts by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="schid", type="string", example="12345"),
     *                     @OA\Property(property="clsid", type="string", example="67890"),
     *                     @OA\Property(property="anum", type="string", example="1234567890"),
     *                     @OA\Property(property="bnk", type="string", example="Bank XYZ"),
     *                     @OA\Property(property="aname", type="string", example="John Doe"),
     *                     @OA\Property(property="subAccounts", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="subaccount_code", type="string", example="SUB_98765"),
     *                             @OA\Property(property="percentage_charge", type="integer", example=0)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account not found"),
     *             @OA\Property(property="pld", type="array", @OA\Items())
     *         )
     *     )
     * )
     */


    public function getAcctApp($schid)
    {
        $start = request()->input('start', 0);
        $count = request()->input('count', 20);
        // Check if the given schid exists in the database

        $pld = application_acct::where('schid', $schid)
            ->with('subAccounts') // Load subAccounts relationship
            ->skip($start)
            ->take($count)
            ->get();

        if (!$pld) {
            return response()->json([
                "status" => false,
                "message" => "Account not found",
                "pld" => $pld,
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }





    /**
     * @OA\Get(
     *     path="/api/getAcctAccept/{schid}",
     *     tags={"Accounts"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all Accounts by School",
     *     description="Use this endpoint to get all Accounts by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="schid", type="string", example="12345"),
     *                     @OA\Property(property="clsid", type="string", example="67890"),
     *                     @OA\Property(property="anum", type="string", example="1234567890"),
     *                     @OA\Property(property="bnk", type="string", example="Bank XYZ"),
     *                     @OA\Property(property="aname", type="string", example="John Doe"),
     *                     @OA\Property(property="subAccounts", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="subaccount_code", type="string", example="SUB_98765"),
     *                             @OA\Property(property="percentage_charge", type="integer", example=0)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account not found"),
     *             @OA\Property(property="pld", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function getAcctAccept($schid)
    {
        $start = request()->input('start', 0);
        $count = request()->input('count', 20);
        // Check if the given schid exists in the database

        $pld = acceptance_acct::where('schid', $schid)
            ->with('subAccounts') // Load subAccounts relationship
            ->skip($start)
            ->take($count)
            ->get();

        if (!$pld) {
            return response()->json([
                "status" => false,
                "message" => "Account not found",
                "pld" => $pld,
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }




    /**
     * @OA\Post(
     *     path="/api/setChangePassword",
     *     summary="Change User Password",
     *     description="Allows an authenticated user to change their password by providing the old password, a new password, and the school ID.",
     *     operationId="setChangePassword",
     *     tags={"Api"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"old_password", "new_password", "new_password_confirmation", "schid"},
     *             @OA\Property(property="old_password", type="string", example="currentPassword123"),
     *             @OA\Property(property="new_password", type="string", example="newSecurePassword456"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="newSecurePassword456"),
     *             @OA\Property(property="schid", type="integer", example=101)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Password updated successfully."),
     *             @OA\Property(property="token", type="string", example="new_jwt_token"),
     *             @OA\Property(property="schid", type="integer", example=101)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Old password does not match",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Old password does not match")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */

    public function setChangePassword(Request $request)
    {
        // Validate input
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
            'schid' => 'required' // Validate school ID
        ]);

        // Get authenticated user
        $user = auth()->user();

        // Verify old password
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Old password does not match'
            ], 400);
        }

        // Retrieve school ID from the request
        $schoolId = $request->schid;

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Generate a new JWT token for continued authentication
        $newToken = auth()->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.',
            'token' => $newToken,  // Send the new token to the frontend
            'schid' => $schoolId    // Return school ID if needed
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/exitStaff/{schid}/{stid}",
     *     summary="Exit a staff member and move them to the exstaffs table",
     *     description="Marks a staff member as inactive and transfers their data to the exstaffs table along with session and exit details.",
     *     operationId="exitStaff",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID of the staff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Staff ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason_for_exit"},
     *             @OA\Property(property="reason_for_exit", type="string", example="Retirement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff successfully exited and moved to exstaffs",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Staff has been exited successfully and moved to exstaffs.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Staff has already been moved to exstaffs",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Staff has already been moved to exstaffs.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Staff not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The reason_for_exit field is required.")
     *         )
     *     )
     * )
     */



    public function exitStaff(Request $request, $schid, $stid)
    {
        // Validate the request
        $request->validate([
            'reason_for_exit' => 'required|string|max:555',
        ]);

        // Find the staff
        $staff = staff::where('schid', $schid)->where('sid', $stid)->first();

        if (!$staff) {
            return response()->json([
                'status' => false,
                'message' => 'Staff not found.',
            ], 404);
        }

        // Check if staff is already in the ex_staff table
        if (ex_staff::where('stid', $stid)->where('schid', $schid)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Staff has already been moved to ex_staffs.',
            ], 400);
        }

        // Retrieve session_of_entry and term_of_entry from old_staff
        $oldStaff = old_staff::where('sid', $stid)->where('schid', $schid)->first();
        $sessionOfEntry = $oldStaff ? $oldStaff->ssn : ($staff->created_at ? $staff->created_at->year : null);

        // Generate the `suid`
        $count = $staff->count;
        $suid = $staff->sch3 . '/STAFF/' . strval($count);

        // Start Transaction
        DB::beginTransaction();

        try {
            // Update staff status
            $staff->update([
                'status' => 'inactive',
                'exit_status' => 'exited'
            ]);

            // Ensure old_staff table reflects the status change
            if (DB::table('old_staff')->where('schid', $schid)->where('sid', $stid)->exists()) {
                DB::table('old_staff')
                    ->where('schid', $schid)
                    ->where('sid', $stid)
                    ->update(['status' => 'inactive']);
            }

            // Refresh staff to ensure status change
            $staff->refresh();

            // Move to ex_staff table only if status is inactive
            if ($staff->status === 'inactive') {
                ex_staff::create([
                    'stid' => $staff->sid,
                    'schid' => $staff->schid,
                    'suid' => $suid,
                    'lname' => $staff->lname,
                    'fname' => $staff->fname,
                    'mname' => $staff->mname,
                    'sch3' => $staff->sch3,
                    'count' => strval($count),
                    'session_of_entry' => $sessionOfEntry,
                    'date_of_entry' => $staff->created_at->toDateString(),
                    'session_of_exit' => now()->year,
                    'date_of_exit' => now()->toDateString(),
                    'reason_for_exit' => $request->input('reason_for_exit'),
                ]);
            }

            // Commit Transaction
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Staff has been exited successfully and moved to ex_staffs.',
            ], 200);
        } catch (\Exception $e) {
            // Rollback on failure
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to exit staff. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    /**
     * @OA\Get(
     *     path="/api/getAlumni/{schid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all Alumni by School",
     *     description="Retrieve a list of alumni for a given school with optional filtering by exit class and exit class arm.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="exit_class",
     *         in="query",
     *         required=false,
     *         description="Exit class of the alumni (e.g., SS3, JSS3)",
     *         @OA\Schema(type="string", example="SS3")
     *     ),
     *     @OA\Parameter(
     *         name="exit_class_arm",
     *         in="query",
     *         required=false,
     *         description="Exit class arm of the alumni (e.g., A, B, C)",
     *         @OA\Schema(type="string", example="A")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="total", type="integer", example=5),
     *             @OA\Property(property="pld", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="schid", type="integer", example=101),
     *                     @OA\Property(property="exit_class", type="string", example="SS3"),
     *                     @OA\Property(property="exit_class_arm", type="string", example="A"),
     *                     @OA\Property(property="year_graduated", type="integer", example=2020)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(
     *         response=404,
     *         description="No alumni found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No alumni found"),
     *             @OA\Property(property="total", type="integer", example=0),
     *             @OA\Property(property="pld", type="array", @OA\Items())
     *         )
     *     )
     * )
     */


    public function getAlumni($schid)
    {
        $start = 0;
        $count = 20;

        // Check if 'start' and 'count' query parameters are provided
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        // Retrieve additional filters from query parameters
        $exitClass = request()->input('exit_class');
        $exitClassArm = request()->input('exit_class_arm');

        // Query alumni table with optional filtering
        $query = alumni::where('schid', $schid);

        if (!empty($exitClass)) {
            $query->where('exit_class', $exitClass);
        }
        if (!empty($exitClassArm)) {
            $query->where('exit_class_arm', $exitClassArm);
        }

        // Get total count before pagination
        $totalAlumni = $query->count();

        // Apply pagination
        $alumni = $query->skip($start)->take($count)->get();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "total" => $totalAlumni,
            "pld" => $alumni,
        ]);
    }






    /**
     * @OA\Get(
     *     path="/api/getExStaff/{schid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all ex-staff by school",
     *     description="Use this endpoint to retrieve all ex-staff members from a specific school, with optional pagination.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at (for pagination)",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="fname", type="string", example="John"),
     *                     @OA\Property(property="lname", type="string", example="Doe"),
     *                     @OA\Property(property="schid", type="integer", example=101),
     *                     @OA\Property(property="session_of_exit", type="integer", example=2022),
     *                     @OA\Property(property="date_of_exit", type="string", format="date", example="2022-05-15"),
     *                     @OA\Property(property="reason_for_exit", type="string", example="Retired")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(
     *         response=404,
     *         description="No ex-staff found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No ex-staff found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */


    public function getExStaff($schid)
    {
        $start = request()->input('start', 0);
        $count = request()->input('count', 20);

        // Get total count of ex-staff in the school
        $totalExStaff = ex_staff::where('schid', $schid)->count();

        // Retrieve ex-staff with pagination
        $pld = ex_staff::where('schid', $schid)
            ->skip($start)
            ->take($count)
            ->get();

        if ($pld->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No ex-staff found",
                "total" => 0,
                "pld" => [],
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "total" => $totalExStaff, // Total number of ex-staff
            "pld" => $pld,
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getExStaff/{schid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all ex-staff by School",
     *     description="Use this endpoint to get all ex-staff by School",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Index to start at",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sid", type="integer", example=1),
     *                     @OA\Property(property="lname", type="string", example="Doe"),
     *                     @OA\Property(property="fname", type="string", example="John"),
     *                     @OA\Property(property="mname", type="string", example="Michael"),
     *                     @OA\Property(property="schid", type="integer", example=101),
     *                     @OA\Property(property="date_of_entry", type="string", format="date", example="2022-03-01"),
     *                     @OA\Property(property="date_of_exit", type="string", format="date", example="2024-09-15"),
     *                     @OA\Property(property="reason_for_exit", type="string", example="Retirement")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(
     *         response=404,
     *         description="No ex-staff found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No ex-staff found"),
     *             @OA\Property(property="pld", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    // public function getExStaff($schid) {
    //     $start = request()->input('start', 0);
    //     $count = request()->input('count', 20);

    //     // Retrieve exstaff based on school ID with pagination
    //     $exstaff = exstaff::where('schid', $schid)
    //         ->skip($start)
    //         ->take($count)
    //         ->get();

    //     if ($exstaff->isEmpty()) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "No ex-staff found",
    //             "pld" => [],
    //         ], 404);
    //     }

    //     return response()->json([
    //         "status" => true,
    //         "message" => "Success",
    //         "pld" => $exstaff,
    //     ]);
    // }



    /**
     * @OA\Post(
     *     path="/api/restoreStudent/{schid}/{stid}",
     *     summary="Restore an inactive student",
     *     description="Changes the status of a student from 'inactive' to 'active' based on school ID and student ID.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student restored successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student restored successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="schid", type="integer", example=101),
     *                 @OA\Property(property="sid", type="integer", example=5001),
     *                 @OA\Property(property="status", type="string", example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found or already active",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found or already active")
     *         )
     *     )
     * )
     */

    // restore
    public function restoreStudent($schid, $stid)
    {
        // Start transaction for atomic operations
        DB::beginTransaction();

        try {
            // Find inactive student in old_student table
            $pld = DB::table('old_student')
                ->where('schid', $schid)
                ->where('sid', $stid)
                ->where('status', 'inactive')
                ->first();

            if (!$pld) {
                DB::rollBack(); // Rollback transaction if student is not found
                return response()->json([
                    'status' => false,
                    'message' => 'Student not found or already active in old_student.',
                ], 404);
            }

            // Restore student in old_student table
            DB::table('old_student')
                ->where('schid', $schid)
                ->where('sid', $stid)
                ->update([
                    'status' => 'active'
                ]);

            // Restore student in student table (if exists)
            $studentExists = DB::table('student')
                ->where('schid', $schid)
                ->where('sid', $stid)
                ->exists();

            if ($studentExists) {
                DB::table('student')
                    ->where('schid', $schid)
                    ->where('sid', $stid)
                    ->update([
                        'status' => 'active',
                        'exit_status' => NULL // Set exit_status to NULL
                    ]);
            }

            // Remove the student from the alumni table
            alumni::where('schid', $schid)->where('stid', $stid)->delete();

            // Commit transaction
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Student restored successfully in old_student and removed from alumni.',
            ], 200);
        } catch (\Exception $e) {
            // Rollback changes if anything fails
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to restore student. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    /**
     * @OA\Post(
     *     path="/api/restoreStaff/{schid}/{stid}",
     *     summary="Restore a staff member",
     *     description="Restores a staff member by setting their status to active and removing their record from the ex-staff table.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         description="School ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         description="Staff ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff restored successfully and removed from ex-staff.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Staff restored successfully and removed from ex-staff."),
     *             @OA\Property(property="pld", type="object",
     *                 @OA\Property(property="sid", type="integer", example=123),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="exit_status", type="string", example=null)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff not found or already active.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Staff not found or already active.")
     *         )
     *     )
     * )
     */

    public function restoreStaff($schid, $stid)
    {
        // Start transaction for atomic operations
        DB::beginTransaction();

        try {
            // Find inactive staff in old_staff table
            $pld = DB::table('old_staff')
                ->where('schid', $schid)
                ->where('sid', $stid)
                ->where('status', 'inactive')
                ->first();

            if (!$pld) {
                DB::rollBack(); // Rollback transaction if staff is not found
                return response()->json([
                    'status' => false,
                    'message' => 'Staff not found or already active in old_staff.',
                ], 404);
            }

            // Restore staff in old_staff table
            DB::table('old_staff')
                ->where('schid', $schid)
                ->where('sid', $stid)
                ->update([
                    'status' => 'active'
                ]);

            // Restore staff in staff table (if exists)
            $staffExists = DB::table('staff')
                ->where('schid', $schid)
                ->where('sid', $stid)
                ->exists();

            if ($staffExists) {
                DB::table('staff')
                    ->where('schid', $schid)
                    ->where('sid', $stid)
                    ->update([
                        'status' => 'active',
                        'exit_status' => NULL // Set exit_status to NULL
                    ]);
            }

            // Remove the staff from the ex_staff table
            ex_staff::where('schid', $schid)->where('stid', $stid)->delete();

            // Commit transaction
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Staff restored successfully in old_staff and removed from ex_staff.',
            ], 200);
        } catch (\Exception $e) {
            // Rollback changes if anything fails
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to restore staff. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/setPaymentInstruction",
     *     summary="Create or Update Payment Instruction",
     *     description="This endpoint creates a new payment instruction or updates an existing one if an ID is provided.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "clsid", "sesid", "trmid", "payment_ins"},
     *             @OA\Property(property="id", type="integer", example=1, description="Optional: ID for updating an existing record"),
     *             @OA\Property(property="schid", type="integer", example=101, description="School ID"),
     *             @OA\Property(property="clsid", type="integer", example=5, description="Class ID"),
     *             @OA\Property(property="sesid", type="integer", example=2024, description="Session ID"),
     *             @OA\Property(property="trmid", type="integer", example=2, description="Term ID"),
     *             @OA\Property(property="payment_ins", type="string", example="Pay before 10th of the month", description="Payment instructions"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment Instruction Updated")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Record Not Found")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         ),
     *     ),
     * )
     */

    // Set Payment Instruction
    public function setPaymentInstruction(Request $request)
    {
        $request->validate([
            'schid' => 'required',
            'clsid' => 'required',
            'sesid' => 'required',
            'trmid' => 'required',
            'payment_ins' => 'required',
        ]);
        $data = [
            'schid' => $request->schid,
            'clsid' => $request->clsid,
            'sesid' => $request->sesid,
            'trmid' => $request->trmid,
            'payment_ins' => $request->payment_ins,
        ];
        $clspay = [];
        if ($request->has('id')) {
            $payment_instruction = payment_instruction::where('id', $request->id)->first();
            if ($payment_instruction) {
                $payment_instruction->update($data);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Record Not Found",
                ]);
            }
        } else {
            $payment_instruction  = payment_instruction::create($data);
        }
        return response()->json([
            "status" => true,
            "message" => "Payment Instruction Updated"
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getPaymentInstruction/{schid}/{clsid}/{sesid}/{trmid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get Payment Instruction",
     *     description="Retrieve the payment instruction for a specific class, term, session, and school.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sesid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="payment_ins", type="string", example="Payment should be made before 10th of the month")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No payment instruction available",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No payment instruction available")
     *         )
     *     ),
     *
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */


    public function getPaymentInstruction($schid, $clsid, $sesid, $trmid)
    {
        $query = payment_instruction::query();

        if ($schid !== "-1") {
            $query->where('schid', $schid);
        }

        if ($clsid !== "-1") {
            $query->where('clsid', $clsid);
        }

        if ($sesid !== "-1") {
            $query->where('sesid', $sesid);
        }

        if ($trmid !== "-1") {
            $query->where('trmid', $trmid);
        }

        $payment_instructions = $query->get()->map(function ($payment) {
            return [
                "schid" => $payment->schid,
                "clsid" => $payment->clsid,
                "trmid" => $payment->trmid,
                "sesid" => $payment->sesid,
                "payment_ins" => $payment->payment_ins,
                "class_name" => cls::where('id', $payment->clsid)->value('name'),
                "term_name" => trm::where('no', $payment->trmid)->value('name'),
                "session_name" => sesn::where('year', $payment->sesid)->value('name'),
            ];
        });

        if ($payment_instructions->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No payment instructions available",
                "pld" => []
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $payment_instructions, // Returns both IDs and names
        ]);
    }




    /**
     * @OA\Delete(
     *     path="/api/deletePaymentInstruction/{schid}/{clsid}/{sesid}/{trmid}",
     *     summary="Delete a payment instruction",
     *     description="Deletes payment instructions based on the provided school ID, class ID, session ID, and term ID.",
     *     operationId="deletePaymentInstruction",
     *     tags={"Api"},
     *      security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsid",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sesid",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trmid",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment instruction(s) deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment instruction(s) deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No matching payment instruction(s) found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No matching payment instruction(s) found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting payment instruction(s).")
     *         )
     *     )
     * )
     */


    // Delete
    public function deletePaymentInstruction($schid, $clsid, $sesid, $trmid)
    {
        $query = payment_instruction::query();

        if ($schid !== "-1") {
            $query->where('schid', $schid);
        }

        if ($clsid !== "-1") {
            $query->where('clsid', $clsid);
        }

        if ($sesid !== "-1") {
            $query->where('sesid', $sesid);
        }

        if ($trmid !== "-1") {
            $query->where('trmid', $trmid);
        }

        $deletedRows = $query->delete();

        if ($deletedRows > 0) {
            return response()->json([
                "status" => true,
                "message" => "Payment instruction(s) deleted successfully",
            ]);
        }

        return response()->json([
            "status" => false,
            "message" => "No matching payment instruction(s) found",
        ], 404);
    }




    /////////////////////////

    /**
     * @OA\Post(
     *     path="/api/setAttendanceMark",
     *     summary="Set attendance for multiple students",
     *     description="Allows Admin or Form Teacher to set or update attendance status for students for a specific term, class, week, and day.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "ssn", "trm", "clsm", "clsa", "stid", "week", "day", "students"},
     *             @OA\Property(property="schid", type="string", example="SCH123"),
     *             @OA\Property(property="ssn", type="string", example="SSN001"),
     *             @OA\Property(property="trm", type="string", example="2024/2025"),
     *             @OA\Property(property="clsm", type="string", example="JSS1"),
     *             @OA\Property(property="clsa", type="string", example="A"),
     *             @OA\Property(property="stid", type="string", example="STAFF001"),
     *             @OA\Property(property="week", type="integer", example=3, minimum=1, maximum=14),
     *             @OA\Property(property="day", type="string", enum={"monday", "tuesday", "wednesday", "thursday", "friday"}, example="monday"),
     *             @OA\Property(
     *                 property="students",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sid", type="string", example="STU001"),
     *                     @OA\Property(property="status", type="integer", enum={0,1,2}, example=1, description="0 = Draft, 1 = Present, 2 = Absent")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance marked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance marked successfully for students."),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized or staff not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */


    public function setAttendanceMark(Request $request)
    {
        $validated = $request->validate([
            'schid' => 'required|string',
            'ssn' => 'required|string',
            'trm' => 'required|string',
            'clsm' => 'required|string',
            'clsa' => 'required|string',
            'stid' => 'nullable|string', // stid is now optional
            'week' => 'required|integer|min:1|max:14',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday',
            'students' => 'required|array',
            'students.*.sid' => 'required|string',
            'students.*.status' => 'required|in:0,1,2',
        ]);

        // Check staff only if stid is provided
        if (!empty($validated['stid'])) {
            $staff = staff::where('sid', $validated['stid'])->first();

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Staff not found'], 403);
            }

            $roles = staff_role::whereIn('id', [$staff->role, $staff->role2])->pluck('name')->toArray();
            if (!array_intersect(['Admin', 'Form Teacher'], $roles)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }
        }

        $marked = [];

        foreach ($validated['students'] as $student) {
            $existing = attendance::where([
                'schid' => $validated['schid'],
                'ssn' => $validated['ssn'],
                'trm' => $validated['trm'],
                'clsm' => $validated['clsm'],
                'clsa' => $validated['clsa'],
                'sid' => $student['sid'],
                'week' => $validated['week'],
                'day' => $validated['day'],
            ])->first();

            if ($existing) {
                $existing->update([
                    'status' => $student['status'],
                    'stid' => $validated['stid'] ?? null,
                ]);
                $marked[] = $existing;
            } else {
                $marked[] = attendance::create([
                    'schid' => $validated['schid'],
                    'ssn' => $validated['ssn'],
                    'trm' => $validated['trm'],
                    'clsm' => $validated['clsm'],
                    'clsa' => $validated['clsa'],
                    'sid' => $student['sid'],
                    'stid' => $validated['stid'] ?? null, // Safe nullable fallback
                    'week' => $validated['week'],
                    'day' => $validated['day'],
                    'status' => $student['status'],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance marked successfully for students.',
            'pld' => $marked
        ]);
    }



    ///
    /**
     * @OA\Post(
     *     path="/api/submitAttendance",
     *     summary="Submit attendance for students",
     *     description="Allows Admin or Form Teacher to submit attendance for students for a specific week and day.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "ssn", "trm", "clsm", "clsa", "stid", "week", "day", "students"},
     *             @OA\Property(property="schid", type="string", example="SCH123", description="School ID"),
     *             @OA\Property(property="ssn", type="string", example="2024", description="Session"),
     *             @OA\Property(property="trm", type="string", example="2024/2025", description="Term"),
     *             @OA\Property(property="clsm", type="string", example="SSS3", description="Class Main"),
     *             @OA\Property(property="clsa", type="string", example="A", description="Class Arm"),
     *             @OA\Property(property="stid", type="string", example="STAFF001", description="Staff ID"),
     *             @OA\Property(property="week", type="integer", example=1, description="Week number (1 to 14)"),
     *             @OA\Property(property="day", type="string", example="Monday", description="Day of the week (e.g., Monday, Tuesday, etc.)"),
     *             @OA\Property(
     *                 property="students",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sid", type="string", example="STU001", description="Student ID"),
     *                     @OA\Property(property="status", type="integer", enum={0, 1, 2}, example=1, description="0 = Draft, 1 = Present, 2 = Absent")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance submitted successfully."),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sid", type="string", example="STU001"),
     *                     @OA\Property(property="week", type="integer", example=1),
     *                     @OA\Property(property="day", type="string", example="Monday"),
     *                     @OA\Property(property="status", type="string", example="Present")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized: Only Admin or Form Teachers can submit attendance",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized: Only Admin or Form Teachers can submit attendance.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized: Staff not found.")
     *         )
     *     )
     * )
     */



    // Submit attendance (for admin, form teachers)
    // Submit attendance (for admin, form teachers)
    public function submitAttendance(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'schid' => 'required|string',
            'ssn' => 'required|string',
            'trm' => 'required|string',
            'clsm' => 'required|string',
            'clsa' => 'required|string',
            'stid' => 'nullable|string', // Now optional
            'week' => 'required|integer|between:1,14',
            'day' => 'required|string',
            'students' => 'required|array',
            'students.*.sid' => 'required|string',
            'students.*.status' => 'required|in:0,1,2',
        ]);

        // If stid is provided, check the staff role
        if (!empty($validated['stid'])) {
            $staff = staff::where('sid', $validated['stid'])->first();

            if (!$staff) {
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized: Staff not found."
                ], 403);
            }

            // Retrieve the role names from the staff_role table
            $roleNames = staff_role::whereIn('id', [$staff->role, $staff->role2])
                ->pluck('name')
                ->toArray();

            // Allow Form Teachers, Admin to submit attendance
            $allowedRoles = ['Form Teacher', 'Admin'];

            if (empty(array_intersect($allowedRoles, $roleNames))) {
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized: Only Admin or Form Teachers can submit attendance."
                ], 403);
            }
        }

        // Proceed with saving attendance (even if stid is null)
        return $this->saveAttendanceSubmission($validated);
    }

    // Save the attendance submission records to the database
    private function saveAttendanceSubmission($validated)
    {
        $attendanceData = [];

        foreach ($validated['students'] as $studentData) {
            // Check if attendance already exists for this student in the specified week and day
            $existingAttendance = attendance::where('schid', $validated['schid'])
                ->where('ssn', $validated['ssn'])
                ->where('trm', $validated['trm'])
                ->where('clsm', $validated['clsm'])
                ->where('clsa', $validated['clsa'])
                ->where('sid', $studentData['sid'])
                ->where('week', $validated['week'])
                ->where('day', $validated['day'])
                ->first();

            // If attendance exists, update it
            if ($existingAttendance) {
                $existingAttendance->status = $studentData['status'];
                $existingAttendance->stid = $validated['stid'] ?? null;

                $existingAttendance->save();

                $attendanceData[] = $existingAttendance;
            } else {
                // If attendance doesn't exist, create a new record
                $attendance = attendance::create([
                    'schid' => $validated['schid'],
                    'ssn' => $validated['ssn'],
                    'trm' => $validated['trm'], // Store the term
                    'clsm' => $validated['clsm'],
                    'clsa' => $validated['clsa'],
                    'sid' => $studentData['sid'],
                    'status' => $studentData['status'],
                    'stid' => $validated['stid'] ?? null,
                    'week' => $validated['week'], // Add the week
                    'day' => $validated['day'],  // Use the day provided in the request
                ]);

                $attendanceData[] = $attendance;
            }
        }

        // Create the response format to include week and day breakdown for each student
        $responseData = [];
        foreach ($attendanceData as $attendance) {
            $responseData[] = [
                'sid' => $attendance->sid,
                'week' => $attendance->week,
                'day' => $attendance->day,  // Return the day as passed in the request
                'status' => $attendance->status == 0 ? 'Draft' : ($attendance->status == 1 ? 'Present' : 'Absent'),
            ];
        }

        return response()->json([
            "status" => "success",
            "message" => "Attendance submitted successfully.",
            "pld" => $responseData // Return the detailed breakdown for each student
        ], 200);
    }



    ////
    /**
     * @OA\Get(
     *     path="/api/getAttendance/{week}/{schid}/{trm}/{ssn}/{clsm}/{clsa}",
     *     summary="Retrieve attendance records for a given week",
     *     description="Fetches all attendance records for a specified school, term, session, class main, and class arm for the given week (excluding day).",
     *     operationId="getAttendance",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="week",
     *         in="path",
     *         required=true,
     *         description="Week number (1-14)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="SCH12345")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term (e.g., First, Second, Third)",
     *         @OA\Schema(type="string", example="First")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session (e.g., 2024/2025)",
     *         @OA\Schema(type="string", example="2024/2025")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class Main (e.g., JSS1)",
     *         @OA\Schema(type="string", example="JSS1")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Class Arm (e.g., A, B, C)",
     *         @OA\Schema(type="string", example="A")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attendance records retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance records retrieved successfully."),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sid", type="string", example="STU001"),
     *                     @OA\Property(property="student_name", type="string", example="John Doe"),
     *                     @OA\Property(property="status", type="string", example="Present"),
     *                     @OA\Property(property="week", type="integer", example=2),
     *                     @OA\Property(property="day", type="string", example="Monday")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No attendance records found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No attendance records found for this week.")
     *         )
     *     )
     * )
     */


    // Get attendance for a student
    public function getAttendance($week, $schid, $trm, $ssn, $clsm, $clsa)
    {
        // Retrieve the attendance records for the specified week (day removed)
        $attendances = attendance::where('week', $week)
            ->where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('clsm', $clsm)
            ->where('clsa', $clsa)
            ->get();

        // Check if no attendance records are found
        if ($attendances->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No attendance records found for this week.',
            ], 404);
        }

        // Format the attendance details for each student
        $attendanceDetails = $attendances->map(function ($attendance) {
            // Retrieve student details (e.g., name, id)
            $student = student::where('sid', $attendance->sid)->first();

            // Concatenate first, middle, and last names
            $fullName = $student ? trim($student->fname . ' ' . $student->mname . ' ' . $student->lname) : 'Unknown';

            return [
                'sid' => $attendance->sid,
                'student_name' => $fullName,
                'status' => $attendance->status == 0 ? 'Draft' : ($attendance->status == 1 ? 'Present' : 'Absent'),
                'week' => $attendance->week,
                'day' => $attendance->day, // Still returning day in result if available
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance records retrieved successfully.',
            'pld' => $attendanceDetails,
        ], 200);
    }



    ////

    /**
     * @OA\Get(
     *     path="/api/calculateAttendanceForClass/{schid}/{ssn}/{clsm}/{clsa}",
     *     summary="Calculate attendance for a class",
     *     description="Retrieve the attendance statistics (days present and absent) for students in a specific class.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Student ID Number",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Class Section",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="attendance_counts", type="object", additionalProperties={
     *                 @OA\Property(type="object",
     *                               @OA\Property(property="days_present", type="integer", example=10),
     *                               @OA\Property(property="days_absent", type="integer", example=2)
     *                 )
     *             })
     *         )
     *     )
     * )
     */


    public function calculateAttendanceForClass($schid, $ssn, $clsm, $clsa)
    {
        // Get all attendance records for the class
        $attendance = Attendance::where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('clsm', $clsm)
            ->where('clsa', $clsa)
            ->get();

        // Initialize an array to store attendance counts for each student
        $attendanceCounts = [];

        // Loop through the attendance records and count the days present/absent for each student
        foreach ($attendance as $record) {
            // If the student already exists in the array, increment the counts
            if (!isset($attendanceCounts[$record->sid])) {
                $attendanceCounts[$record->sid] = [
                    'days_present' => 0,
                    'days_absent' => 0
                ];
            }

            // Count present or absent days for this student
            if ($record->status == Attendance::STATUS_PRESENT) {
                $attendanceCounts[$record->sid]['days_present']++;
            } elseif ($record->status == Attendance::STATUS_ABSENT) {
                $attendanceCounts[$record->sid]['days_absent']++;
            }
        }

        // Return the attendance counts for all students
        return response()->json([
            'status' => true,
            'attendance_counts' => $attendanceCounts,
        ]);
    }


    //////////////////////////////////

    /**
     * @OA\Get(
     *     path="/api/getAttendanceByWeek/{week}/{schid}",
     *     summary="Retrieve attendance records grouped by day for a specific week",
     *     description="Fetches attendance records for a specified week and school ID, grouping the data by day and returning student attendance details for each day.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="week",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1, description="Week number (1 to 14)")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="SCH123", description="School ID")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance records grouped by day retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance records grouped by day for the selected week."),
     *             @OA\Property(property="week", type="integer", example=1),
     *             @OA\Property(
     *                 property="attendance_by_day",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="day", type="string", example="Monday"),
     *                     @OA\Property(property="total_present", type="integer", example=20),
     *                     @OA\Property(property="total_absent", type="integer", example=5),
     *                     @OA\Property(
     *                         property="students",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="sid", type="string", example="STU001"),
     *                             @OA\Property(property="student_name", type="string", example="Doe John Smith"),
     *                             @OA\Property(property="status", type="string", example="Present")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No attendance records found for this week",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No attendance records found for this week.")
     *         )
     *     )
     * )
     */


    public function getAttendanceByWeek($week, $schid)
    {
        // Fetch attendance for the specified school and week
        $attendances = attendance::where('week', $week)
            ->where('schid', $schid)
            ->get();

        if ($attendances->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No attendance records found for this week.',
            ], 404);
        }

        // Group attendance records by day (e.g., monday, tuesday...)
        $groupedByDay = $attendances->groupBy('day')->map(function ($records, $day) {
            $students = $records->map(function ($attendance) {
                // Try to find student in current or old student table
                $student = student::where('sid', $attendance->sid)->first()
                    ?? old_student::where('sid', $attendance->sid)->first();

                $fullName = $student ? trim($student->lname . ' ' . $student->fname . ' ' . $student->mname) : 'Unknown';

                return [
                    'sid' => $attendance->sid,
                    'student_name' => $fullName,
                    'status' => match ($attendance->status) {
                        0 => 'Draft',
                        1 => 'Present',
                        2 => 'Absent',
                        default => 'Unknown'
                    },
                ];
            });

            // You can also include totals if you want
            $presentCount = $records->where('status', 1)->count();
            $absentCount = $records->where('status', 2)->count();

            return [
                'day' => ucfirst($day),
                'total_present' => $presentCount,
                'total_absent' => $absentCount,
                'students' => $students,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance records grouped by day for the selected week.',
            'week' => $week,
            'attendance_by_day' => $groupedByDay
        ], 200);
    }


    ////////////////

    /**
     * @OA\Get(
     *     path="/api/getFilteredAttendanceSummary/{schid}/{ssn}/{trm}/{clsm}/{clsa}",
     *     summary="Get filtered attendance summary",
     *     description="Retrieve weekly and overall attendance summary based on specified school, session, term, and class filters.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="SCH123"),
     *         description="School ID"
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="2024"),
     *         description="Session"
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="1"),
     *         description="Term"
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="JSS1"),
     *         description="Class Main (e.g., JSS1)"
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="A"),
     *         description="Class Arm (e.g., A, B, C)"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attendance summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance summary retrieved successfully."),
     *             @OA\Property(
     *                 property="summary",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="week", type="integer", example=1),
     *                     @OA\Property(property="days_recorded", type="integer", example=5),
     *                     @OA\Property(property="present_count", type="integer", example=120),
     *                     @OA\Property(property="absent_count", type="integer", example=15)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="overall",
     *                 type="object",
     *                 @OA\Property(property="total_weeks", type="integer", example=14),
     *                 @OA\Property(property="total_present", type="integer", example=950),
     *                 @OA\Property(property="total_absent", type="integer", example=50),
     *                 @OA\Property(property="total_students", type="integer", example=100)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No attendance records found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No attendance records found for the specified filters.")
     *         )
     *     )
     * )
     */


    public function getFilteredAttendanceSummary($schid, $ssn, $trm, $clsm, $clsa)
    {
        // Fetch attendance records for specified filters
        $records = attendance::where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('clsm', $clsm)
            ->where('clsa', $clsa)
            ->get();

        // Get total number of students from the attendance table (unique student IDs)
        $totalStudents = $records->unique('sid')->count();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No attendance records found for the specified filters.'
            ], 404);
        }

        // Group by week and generate per-week summaries
        $weeklySummary = collect(range(1, 14))->map(function ($week) use ($records) {
            $weekRecords = $records->where('week', $week);
            $uniqueDays = $weekRecords->groupBy('day')->count();
            $present = $weekRecords->where('status', 1)->count();
            $absent = $weekRecords->where('status', 2)->count();

            return [
                'week' => $week,
                'days_recorded' => $uniqueDays,
                'present_count' => $present,
                'absent_count' => $absent,
            ];
        });

        // Calculate overall totals
        $totalPresent = $records->where('status', 1)->count();
        $totalAbsent = $records->where('status', 2)->count();
        $totalWeeksWithData = $records->pluck('week')->unique()->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance summary retrieved successfully.',
            'summary' => $weeklySummary,
            'overall' => [
                'total_weeks' => $totalWeeksWithData,
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'total_students' => $totalStudents,
            ]
        ]);
    }



    ///////////////////////////////////////////////////

    /**
     * @OA\Post(
     *     path="/api/setLessonPlan",
     *     summary="Create or update a lesson plan",
     *     description="Stores or updates a lesson plan based on school, session, term, class, subject, and date.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                 "schid", "clsm", "date", "ssn", "trm", "sbj",
     *                 "no_of_class", "average_age", "topic",
     *                 "time_from", "time_to", "duration"
     *             },
     *             @OA\Property(property="schid", type="string", example="SCH001"),
     *             @OA\Property(property="clsm", type="string", example="1"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-05-09"),
     *             @OA\Property(property="ssn", type="string", example="2025"),
     *             @OA\Property(property="trm", type="string", example="2"),
     *             @OA\Property(property="sbj", type="string", example="Mathematics"),
     *             @OA\Property(property="no_of_class", type="integer", example=2),
     *             @OA\Property(property="average_age", type="number", format="float", example=10.5),
     *             @OA\Property(property="topic", type="string", example="Addition and Subtraction"),
     *             @OA\Property(
     *                 property="sub_topic",
     *                 type="array",
     *                 @OA\Items(type="string", example="Two-digit addition")
     *             ),
     *             @OA\Property(property="time_from", type="string", format="time", example="09:00"),
     *             @OA\Property(property="time_to", type="string", format="time", example="10:00"),
     *             @OA\Property(property="duration", type="string", example="1 hour"),
     *             @OA\Property(
     *                 property="learning_materials",
     *                 type="array",
     *                 @OA\Items(type="string", example="Flashcards")
     *             ),
     *  *             @OA\Property(
     *                 property="lesson_objectives",
     *                 type="array",
     *                 @OA\Items(type="string", example="At the end of the lesson, students should be able to count numbers")
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson plan saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson plan saved successfully"),
     *             @OA\Property(property="pld", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */


    public function setLessonPlan(Request $request)
    {
        $request->validate([
            "schid" => "required|string",
            "clsm" => "required|string",
            'date' => 'required|date',
            "ssn" => "required|string",
            "trm" => "required",
            "sbj" => "required|string",
            'no_of_class' => 'required|integer',
            'average_age' => 'required|numeric',
            'topic' => 'required|string',
            'sub_topic' => 'nullable|array',
            'time_from' => 'required|date_format:H:i',
            'time_to' => 'required|date_format:H:i|after:time_from',
            'duration' => 'required|string',
            'learning_materials' => 'nullable|array',
            'lesson_objectives' => 'required|array',
        ]);

        $data = $request->only([
            'date',
            'no_of_class',
            'average_age',
            'topic',
            'time_from',
            'time_to',
            'duration',
            "ssn",
            "trm",
            "sbj",
            "schid",
            "clsm",
        ]);

        $data['sub_topic'] = $request->sub_topic;
        $data['learning_materials'] = $request->learning_materials;
        $data['lesson_objectives'] = $request->lesson_objectives;

        $lessonPlan = lesson_plan::updateOrCreate(
            [
                'date' => $request->date,
                "schid" => $request->schid,
                "clsm" => $request->clsm,
                "ssn" => $request->ssn,
                "trm" => $request->trm,
                "sbj" => $request->sbj,
                "no_of_class" => $request->no_of_class,
                "time_from" => $request->time_from,
                "time_to" => $request->time_to,
                "topic" => $request->topic,
                "average_age" => $request->average_age
            ],
            $data
        );

        return response()->json([
            'status' => true,
            'message' => 'Lesson plan saved successfully',
            'pld' => $lessonPlan,
        ], 200);
    }



    //////////////////////////////////////////////////
    /**
     * @OA\Put(
     *     path="/api/updateLessonPlan",
     *     summary="Update a lesson plan",
     *     description="Updates an existing lesson plan record based on schid, clsm, ssn, and trm. Only provided fields will be updated.",
     *     operationId="updateLessonPlan",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "clsm", "ssn", "trm"},
     *             @OA\Property(property="schid", type="string", example="SCH001"),
     *             @OA\Property(property="clsm", type="string", example="1"),
     *             @OA\Property(property="ssn", type="string", example="2025"),
     *             @OA\Property(property="trm", type="string", example="2"),
     *             @OA\Property(property="sbj", type="string", example="Mathematics"),
     *             @OA\Property(property="no_of_class", type="integer", example=5),
     *             @OA\Property(property="average_age", type="integer", example=10),
     *             @OA\Property(property="topic", type="string", example="Multiplication"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-05-15"),
     *             @OA\Property(property="sub_topic", type="array", @OA\Items(type="string"), example={"Introduction", "Examples"}),
     *             @OA\Property(property="time_from", type="string", format="time", example="08:00:00"),
     *             @OA\Property(property="time_to", type="string", format="time", example="09:00:00"),
     *             @OA\Property(property="duration", type="string", example="1 hour"),
     *             @OA\Property(property="learning_materials", type="array", @OA\Items(type="string"), example={"Chalkboard", "Chart"}),
     *             @OA\Property(property="lesson_objectives", type="array", @OA\Items(type="string"), example={"Understand concept", "Apply in daily life"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lesson Plan updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson Plan updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="No valid fields provided for update",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No valid fields provided for update")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Lesson Plan not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson Plan not found")
     *         )
     *     )
     * )
     */


    public function updateLessonPlan(Request $request)
    {
        $request->validate([
            "schid" => "required",
            "clsm" => "required",
            "ssn" => "required",
            "trm" => "required",

            // Optional fields for update
            "sbj" => "nullable",
            "no_of_class" => "nullable|integer",
            "average_age" => "nullable|integer",
            "topic" => "nullable|string",
            "date" => "nullable|date",
            "sub_topic" => "nullable|array",
            "time_from" => "nullable|date_format:H:i:s",
            "time_to" => "nullable|date_format:H:i:s",
            "duration" => "nullable|string",
            "learning_materials" => "nullable|array",
            "lesson_objectives" => "nullable|array",
        ]);

        $lessonPlan = lesson_plan::where("schid", $request->schid)
            ->where("clsm", $request->clsm)
            ->where("ssn", $request->ssn)
            ->where("trm", $request->trm)
            ->first();

        if (!$lessonPlan) {
            return response()->json([
                "status" => false,
                "message" => "Lesson Plan not found",
            ], 404);
        }

        // Fields allowed for update
        $fieldsToUpdate = collect($request->only([
            "sbj",
            "no_of_class",
            "average_age",
            "topic",
            "sub_topic",
            "date",
            "time_from",
            "time_to",
            "duration",
            "learning_materials",
            "lesson_objectives"
        ]))->filter(fn($value) => !is_null($value))->toArray();

        if (empty($fieldsToUpdate)) {
            return response()->json([
                "status" => false,
                "message" => "No valid fields provided for update",
            ], 400);
        }

        $lessonPlan->update($fieldsToUpdate);

        return response()->json([
            "status" => true,
            "message" => "Lesson Plan updated successfully",
            "data" => $lessonPlan
        ], 200);
    }




    ///////////////////////////////////////////////////

    /**
     * @OA\Get(
     *     path="/api/getLessonPlan/{schid}/{ssn}/{trm}/{clsm}",
     *     summary="Get lesson plans for a specific school, session, term, and class",
     *     description="Fetches lesson plans using school ID, session, term, and class with optional pagination parameters.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string"),
     *         example="SCH001"
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session",
     *         @OA\Schema(type="string"),
     *         example="2025"
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string"),
     *         example="2"
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string"),
     *         example="2"
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Pagination start index",
     *         @OA\Schema(type="integer"),
     *         example=0
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of lesson plans to retrieve",
     *         @OA\Schema(type="integer"),
     *         example=20
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of lesson plans retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */


    public function getLessonPlan($schid, $ssn, $trm, $clsm)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $lessonPlan = lesson_plan::where('schid', $schid)
            ->where("clsm", $clsm)
            ->where("ssn", $ssn)
            ->where("trm", $trm)
            ->take($count)->skip($start)->get();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $lessonPlan,
        ]);
    }




    //////////////////////////////////////////
    /**
     * @OA\Get(
     *     path="/api/getLessonPlanBySubject/{schid}/{ssn}/{trm}/{clsm}/{sbj}",
     *     summary="Get lesson plans by subject",
     *     description="Fetch lesson plans based on school ID, session, term, class, and subject with optional pagination.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string"),
     *         example="SCH001"
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session",
     *         @OA\Schema(type="string"),
     *         example="2025"
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID",
     *         @OA\Schema(type="string"),
     *         example="2"
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string"),
     *         example="2"
     *     ),
     *     @OA\Parameter(
     *         name="sbj",
     *         in="path",
     *         required=true,
     *         description="Subject name",
     *         @OA\Schema(type="string"),
     *         example="Mathematics"
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Pagination start index",
     *         @OA\Schema(type="integer"),
     *         example=0
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer"),
     *         example=20
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson plans retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */

    public function getLessonPlanBySubject($schid, $ssn, $trm, $clsm, $sbj)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $lessonPlan = lesson_plan::where('schid', $schid)
            ->where("clsm", $clsm)
            ->where("ssn", $ssn)
            ->where("trm", $trm)
            ->where("sbj", $sbj)
            ->take($count)->skip($start)->get();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $lessonPlan,
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getSingleLessonPlan/{schid}/{ssn}/{trm}/{clsm}/{sbj}/{id}",
     *     summary="Get a single lesson plan by school ID, session, term ID, class, subject, and plan ID",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Academic session (e.g. 2024/2025)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID (e.g. 1, 2, 3)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="11",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbj",
     *         in="path",
     *         required=true,
     *         description="Subject (e.g. Mathematics, English)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lesson plan ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson plan retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson plan not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson plan not found")
     *         )
     *     )
     * )
     */

    public function getSingleLessonPlan($schid, $ssn, $trm, $clsm, $sbj, $id)
    {
        $lessonPlan = lesson_plan::where('schid', $schid)
            ->where('clsm', $clsm)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('sbj', $sbj)
            ->where('id', $id) // This line fetches the individual lesson plan
            ->first();

        if (!$lessonPlan) {
            return response()->json([
                "status" => false,
                "message" => "Lesson plan not found",
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $lessonPlan,
        ], 200);
    }



    ////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *     path="/api/setCurriculum",
     *     summary="Create or update a curriculum",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "clsm", "week", "topic", "ssn", "trm", "sbj"},
     *             @OA\Property(property="schid", type="string", example="SCH123"),
     *             @OA\Property(property="clsm", type="string", example="JSS1"),
     *             @OA\Property(property="week", type="string", example="Week 1"),
     *             @OA\Property(property="topic", type="string", example="Introduction to Biology"),
     *             @OA\Property(
     *                 property="description",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"Explain cells", "Use chart", "Ask questions"}
     *             ),
     *             @OA\Property(
     *                 property="teaching_aids",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"Whiteboard", "Projector"}
     *             ),
     *             @OA\Property(property="ssn", type="string", example="2024"),
     *             @OA\Property(property="trm", type="string", example="1st Term"),
     *             @OA\Property(property="sbj", type="string", example="Biology"),
     *             @OA\Property(property="group", type="string", nullable=true, example="Science"),
     *             @OA\Property(property="url_link", type="string", format="url", nullable=true, example="https://example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Curriculum saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Curriculum saved successfully"),
     *             @OA\Property(property="pld", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */


    public function setCurriculum(Request $request)
    {
        $request->validate([
            "schid" => "required|string",
            "clsm" => "required|string",
            "week" => "required|string",
            "topic" => "required|string",
            "description" => "nullable|array",
            "teaching_aids" => "nullable|array",
            "ssn" => "required|string",
            "trm" => "required|string",
            "sbj" => "required|string",
            "group" => "nullable|string",
            "url_link" => "nullable|url",
        ]);

        $data = $request->only([
            "schid",
            "clsm",
            "week",
            "topic",
            "ssn",
            "trm",
            "sbj",
            "group",
            "url_link"
        ]);

        $data['description'] = $request->description;
        $data['teaching_aids'] = $request->teaching_aids;



        $curriculum = curriculum::updateOrCreate(
            [
                "schid" => $request->schid,
                "clsm" => $request->clsm,
                "ssn" => $request->ssn,
                "trm" => $request->trm,
                "week" => $request->week,
                "sbj" => $request->sbj,
            ],
            $data
        );

        return response()->json([
            "status" => true,
            "message" => "Curriculum saved successfully",
            "pld" => $curriculum
        ], 200);
    }




    /**
     * @OA\Get(
     *     path="/api/getCurriculum/{schid}/{ssn}/{trm}/{clsm}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get Curriculum records",
     *     description="Use this endpoint to fetch curriculum records for a specific school, class, session, term, and subject.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Starting index for pagination",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to fetch",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */

    public function getCurriculum($schid, $ssn, $trm, $clsm)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $curriculum = curriculum::where('schid', $schid)
            ->where("clsm", $clsm)
            ->where("ssn", $ssn)
            ->where("trm", $trm)
            ->take($count)->skip($start)->get();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $curriculum,
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getCurriculumBySubject/{schid}/{ssn}/{trm}/{clsm}/{sbj}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get Curriculum records",
     *     description="Use this endpoint to fetch curriculum records for a specific school, class, session, term, and subject.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbj",
     *         in="path",
     *         required=true,
     *         description="Subject",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Starting index for pagination",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to fetch",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */

    public function getCurriculumBySubject($schid, $ssn, $trm, $clsm, $sbj)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $curriculum = curriculum::where('schid', $schid)
            ->where("clsm", $clsm)
            ->where("ssn", $ssn)
            ->where("trm", $trm)
            ->where("sbj", $sbj)
            ->take($count)->skip($start)->get();

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $curriculum,
        ]);
    }





    /**
     * @OA\Get(
     *     path="/api/getcurriculumByStudent/{schid}/{ssn}/{trm}/{clsm}/{sbj}/{sid}",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get Curriculum for a specific student",
     *     description="Fetch curriculum details for a given student based on school ID, session, term, class, subject, and student ID.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sbj",
     *         in="path",
     *         required=true,
     *         description="Subject",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Starting index for pagination",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to fetch",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="pld", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Curriculum not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Curriculum not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */

    public function getcurriculumByStudent($schid, $ssn, $trm, $clsm, $sbj, $sid)
    {
        $start = 0;
        $count = 20;
        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }
        $pld = curriculum::from('curricula as c')
            ->join('student', 'c.schid', '=', 'student.schid')
            ->where('c.schid', $schid)
            ->where('c.clsm', $clsm)
            ->where('c.ssn', $ssn)
            ->where('c.trm', $trm)
            ->where('c.sbj', $sbj)
            ->where('student.sid', $sid)
            ->skip($start)->take($count)->get();


        if (!$pld) {
            return response()->json([
                "status" => false,
                "message" => "Curriculum not found",
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "Success",
            "pld" => $pld,
        ]);
    }




    /**
     * @OA\Put(
     *     path="/api/updateCurriculum",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Update an existing curriculum record",
     *     description="Updates one or more fields of a curriculum using schid, clsm, ssn, trm, and sbj to identify the record.",
     *     operationId="updateCurriculum",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "clsm", "ssn", "trm", "sbj"},
     *             @OA\Property(property="schid", type="integer", example=1),
     *             @OA\Property(property="clsm", type="string", example="JSS1"),
     *             @OA\Property(property="ssn", type="integer", example=2024),
     *             @OA\Property(property="trm", type="string", example="First Term"),
     *             @OA\Property(property="sbj", type="string", example="Biology"),
     *             @OA\Property(property="topic", type="string", example="Introduction to Cells"),
     *             @OA\Property(property="description", type="string", example="Basic cell structure and function."),
     *             @OA\Property(property="teaching_aids", type="string", example="Charts, Microscope"),
     *             @OA\Property(property="group", type="string", example="Group A"),
     *             @OA\Property(property="url_link", type="string", format="url", example="https://example.com/resource"),
     *             @OA\Property(property="week", type="string", example="Week 2"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Curriculum updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Curriculum updated successfully"),
     *             @OA\Property(property="pld", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Curriculum not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Curriculum not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No valid fields provided for update",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No valid fields provided for update")
     *         )
     *     )
     * )
     */

    public function updateCurriculum(Request $request)
    {
        $request->validate([
            "schid" => "required",
            "clsm" => "required",
            "ssn" => "required",
            "trm" => "required",

            // The fields below are optional for partial updates
            "sbj" => "nullable",
            "topic" => "nullable",
            "description" => "nullable",
            "teaching_aids" => "nullable",
            "group" => "nullable",
            "url_link" => "nullable",
            "week" => "nullable",
        ]);
        Log::info($request->all());

        $curriculum = curriculum::where("schid", $request->schid)
            ->where("clsm", $request->clsm)
            ->where("ssn", $request->ssn)
            ->where("trm", $request->trm)
            ->first();

        if (!$curriculum) {
            return response()->json([
                "status" => false,
                "message" => "Curriculum not found",
            ], 404);
        }

        // Only update fields present in the request
        $fieldsToUpdate = collect($request->only([
            "topic",
            "description",
            "teaching_aids",
            "sbj",
            "group",
            "url_link"
        ]))->filter(fn($value) => !is_null($value))->toArray();

        if (empty($fieldsToUpdate)) {
            return response()->json([
                "status" => false,
                "message" => "No valid fields provided for update",
            ], 400);
        }

        $curriculum->update($fieldsToUpdate);

        return response()->json([
            "status" => true,
            "message" => "Curriculum updated successfully",
            "pld" => $curriculum
        ], 200);
    }

    /////////////////////////////////////////////////////////////

    /**
     * @OA\Get(
     *     path="/api/getAllSubjectPositions/{schid}/{clsm}/{ssn}/{trm}",
     *     operationId="getAllSubjectPositions",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all subject positions for students in a specific school/class/session/term",
     *     description="Returns a list of students with subject positions filtered by school, class, session, and term.",
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         description="School ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         description="Class ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *  *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         description="Class arm",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         description="Session",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         description="Term",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),

     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="stid", type="string", example="ST1234"),
     *                     @OA\Property(property="sbj", type="string", example="Mathematics"),
     *                     @OA\Property(property="pos", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     */


    public function getAllSubjectPositions($schid, $ssn, $trm, $clsm, $clsa)
    {
        // Fetch filtered student subject results with position
        $results = student_sub_res::where('schid', $schid)
            ->where('clsm', $clsm)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('clsa', $clsa)
            ->orderBy('stid')
            ->get();

        return response()->json([
            'status' => true,
            'pld' => $results
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getStudentSubjectPositions/{schid}/{ssn}/{trm}/{clsm}/{clsa}/{stid}",
     *     operationId="getStudentSubjectPositions",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get subject positions for a student",
     *     description="Retrieve all subject positions for a given student filtered by school, session, term, class, and arm.",
     *
     *     @OA\Parameter(name="schid", in="path", required=true, @OA\Schema(type="integer"), description="School ID"),
     *     @OA\Parameter(name="ssn", in="path", required=true, @OA\Schema(type="string"), description="Session (e.g. 2024/2025)"),
     *     @OA\Parameter(name="trm", in="path", required=true, @OA\Schema(type="string"), description="Term"),
     *     @OA\Parameter(name="clsm", in="path", required=true, @OA\Schema(type="string"), description="Class (e.g. JSS1)"),
     *     @OA\Parameter(name="clsa", in="path", required=true, @OA\Schema(type="string"), description="Class arm (e.g. A, B)"),
     *     @OA\Parameter(name="stid", in="path", required=true, @OA\Schema(type="string"), description="Student ID"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Subject positions found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="sbj", type="string", example="Mathematics"),
     *                     @OA\Property(property="pos", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No subject positions found for this student",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No subject positions found for this student.")
     *         )
     *     )
     * )
     */


    public function getStudentSubjectPositions($schid, $ssn, $trm, $clsm, $clsa, $stid)
    {
        $results = student_sub_res::where('schid', $schid)
            ->where('clsm', $clsm)
            ->where('clsa', $clsa)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('stid', $stid)
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No subject positions found for this student.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'pld' => $results
        ]);
    }


    /////////////////////////////////////////////////////////////

    /**
     * @OA\Post(
     *     path="/api/setLessonNote",
     *     summary="Create a new lesson note with topics and subtopics",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sch_id", "session", "term", "class", "week", "subject", "topics"},
     *             @OA\Property(property="sch_id", type="string", example="SCH002"),
     *             @OA\Property(property="session", type="string", example="2025"),
     *             @OA\Property(property="term", type="string", example="2"),
     *             @OA\Property(property="clsm", type="string", example="1"),
     *             @OA\Property(property="week", type="string", example="Week 1"),
     *             @OA\Property(property="subject", type="string", example="Mathematics"),
     *             @OA\Property(
     *                 property="topics",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"title"},
     *                     @OA\Property(property="title", type="string", example="Fractions"),
     *                     @OA\Property(property="references", type="array", @OA\Items(type="string"), example={"Book A", "Book B"}),
     *                     @OA\Property(property="content", type="array", @OA\Items(type="string"), example={"Introduction to fractions", "Examples"}),
     *                     @OA\Property(property="general_evaluation", type="array", @OA\Items(type="string"), example={"Question 1", "Question 2"}),
     *                     @OA\Property(property="weekend_assignment", type="array", @OA\Items(type="string"), example={"Assignment 1", "Assignment 2"}),
     *                     @OA\Property(property="theory", type="array", @OA\Items(type="string"), example={"Explain fractions", "Draw examples"}),
     *                     @OA\Property(
     *                         property="sub_topics",
     *                         type="array",
     *                         @OA\Items(
     *                             required={"title"},
     *                             @OA\Property(property="title", type="string", example="Proper and Improper Fractions"),
     *                             @OA\Property(property="sub_topic_content_one", type="string", example="Explanation with examples"),
     *                             @OA\Property(property="sub_topic_content_two", type="string", example="More examples"),
     *                             @OA\Property(property="sub_topic_content_three", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_four", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_five", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_six", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_seven", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_eight", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_nine", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_content_ten", type="string", example="Practice questions"),
     *                             @OA\Property(property="sub_topic_evaluation", type="array", @OA\Items(type="string"), example={"Q1", "Q2"})
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lesson note created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson note created successfully"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="session", type="string", example="2025"),
     *                 @OA\Property(property="term", type="string", example="2"),
     *                 @OA\Property(property="clsm", type="string", example="1"),
     *                 @OA\Property(property="week", type="string", example="Week 1"),
     *                 @OA\Property(property="subject", type="string", example="Mathematics"),
     *                 @OA\Property(
     *                     property="topics",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="title", type="string", example="Fractions"),
     *                         @OA\Property(
     *                             property="sub_topics",
     *                             type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="title", type="string", example="Improper Fractions")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */


    public function setLessonNote(Request $request)
    {
        $request->validate([
            'sch_id' => 'required',
            'session' => 'required',
            'term' => 'required',
            'clsm' => 'required',
            'week' => 'required',
            'subject' => 'required',
            'topics' => 'required|array',
        ]);

        $lessonNote = lesson_note::create($request->only(['sch_id', 'session', 'term', 'clsm', 'week', 'subject']));

        foreach ($request->topics as $topicData) {
            $topic = $lessonNote->topics()->create([
                'title' => $topicData['title'],
                'references' => $topicData['references'] ?? [],
                'content' => $topicData['content'] ?? [],
                'general_evaluation' => $topicData['general_evaluation'] ?? [],
                'weekend_assignment' => $topicData['weekend_assignment'] ?? [],
                'theory' => $topicData['theory'] ?? [],
            ]);

            foreach ($topicData['sub_topics'] ?? [] as $sub) {
                $topic->subTopics()->create([
                    'title' => $sub['title'],
                    'sub_topic_content_one' => $sub['sub_topic_content_one'] ?? null,
                    'sub_topic_content_two' => $sub['sub_topic_content_two'] ?? null,
                    'sub_topic_content_three' => $sub['sub_topic_content_three'] ?? null,
                    'sub_topic_content_four' => $sub['sub_topic_content_four'] ?? null,
                    'sub_topic_content_five' => $sub['sub_topic_content_five'] ?? null,
                    'sub_topic_content_six' => $sub['sub_topic_content_six'] ?? null,
                    'sub_topic_content_seven' => $sub['sub_topic_content_seven'] ?? null,
                    'sub_topic_content_eight' => $sub['sub_topic_content_eight'] ?? null,
                    'sub_topic_content_nine' => $sub['sub_topic_content_nine'] ?? null,
                    'sub_topic_content_ten' => $sub['sub_topic_content_ten'] ?? null,
                    'sub_topic_evaluation' => $sub['sub_topic_evaluation'] ?? [],
                ]);
            }
        }

        // Reload with relationships
        $lessonNote->load('topics.subTopics');

        return response()->json([
            'status' => true,
            'pld' => [
                'message' => 'Lesson note saved successfully',
                'lesson_note' => $lessonNote,
            ],
        ], 201);
    }




    /**
     * @OA\Get(
     *     path="/api/getLessonNote/{sch_id}/{session}/{term}/{clsm}/{week}",
     *     summary="Retrieve lesson notes based on school, session, term, and class",
     *     description="Returns a list of lesson notes along with their topics and sub-topics.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="sch_id",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="session",
     *         in="path",
     *         required=true,
     *         description="Academic session (e.g. 2024/2025)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="term",
     *         in="path",
     *         required=true,
     *         description="2",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="1",
     *         @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *         name="week",
     *         in="path",
     *         required=true,
     *         description="Week (e.g., Week 1)",
     *         @OA\Schema(type="string", example="Week")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Pagination start index",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lesson notes retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson notes retrieved successfully"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="session", type="string", example="2024/2025"),
     *                     @OA\Property(property="term", type="string", example="1st"),
     *                     @OA\Property(property="clsm", type="string", example="1"),
     *                     @OA\Property(property="week", type="string", example="Week 1"),
     *                     @OA\Property(property="subject", type="string", example="Mathematics"),
     *                     @OA\Property(
     *                         property="topics",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="title", type="string", example="Algebra"),
     *                             @OA\Property(property="references", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="content", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="general_evaluation", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="weekend_assignment", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="theory", type="array", @OA\Items(type="string")),
     *                             @OA\Property(
     *                                 property="sub_topics",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     @OA\Property(property="title", type="string", example="Simplifying expressions"),
     *                                     @OA\Property(property="sub_topic_content_one", type="string"),
     *                                     @OA\Property(property="sub_topic_content_two", type="string"),
     *                                     @OA\Property(property="sub_topic_content_three", type="string"),
     *                                     @OA\Property(property="sub_topic_evaluation", type="array", @OA\Items(type="string"))
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or validation error"
     *     )
     * )
     */

    public function getLessonNote($sch_id, $session, $term, $clsm, $week)
    {
        $start = 0;
        $count = 20;

        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $lessonNotes = lesson_note::with('topics.subTopics')
            ->where('sch_id', $sch_id)
            ->where('session', $session)
            ->where('term', $term)
            ->where('clsm', $clsm)
            ->where('week', $week)
            ->skip($start)
            ->take($count)
            ->get();

        return response()->json([
            "status" => true,
            "message" => "Lesson notes retrieved successfully",
            "pld" => $lessonNotes,
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getLessonNoteBySubject/{sch_id}/{session}/{term}/{clsm}/{subject}/{week}",
     *     summary="Get lesson notes by subject",
     *     description="Retrieve lesson notes filtered by school ID, session, term, class, and subject. Supports pagination via 'start' and 'count' query parameters.",
     *     tags={"Api"},
     *      security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="sch_id",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="SCH123")
     *     ),
     *     @OA\Parameter(
     *         name="session",
     *         in="path",
     *         required=true,
     *         description="Academic session (e.g., 2024/2025)",
     *         @OA\Schema(type="string", example="2024/2025")
     *     ),
     *     @OA\Parameter(
     *         name="term",
     *         in="path",
     *         required=true,
     *         description="Term (e.g., 1, 2, 3)",
     *         @OA\Schema(type="string", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class (e.g., 1, 2)",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="subject",
     *         in="path",
     *         required=true,
     *         description="Subject (e.g., Mathematics)",
     *         @OA\Schema(type="string", example="Mathematics")
     *     ),
     *      @OA\Parameter(
     *         name="week",
     *         in="path",
     *         required=true,
     *         description="Week (e.g., Week 1)",
     *         @OA\Schema(type="string", example="Week")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Pagination start index",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lesson notes retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson notes retrieved successfully"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="sch_id", type="string", example="SCH123"),
     *                     @OA\Property(property="session", type="string", example="2024/2025"),
     *                     @OA\Property(property="term", type="string", example="1st"),
     *                     @OA\Property(property="clsm", type="string", example="1"),
     *                     @OA\Property(property="subject", type="string", example="Mathematics"),
     *                     @OA\Property(property="week", type="string", example="Week 1"),
     *                     @OA\Property(
     *                         property="topics",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="title", type="string", example="Algebra Introduction"),
     *                             @OA\Property(property="references", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="content", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="general_evaluation", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="weekend_assignment", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="theory", type="array", @OA\Items(type="string")),
     *                             @OA\Property(
     *                                 property="sub_topics",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     @OA\Property(property="title", type="string", example="Like terms"),
     *                                     @OA\Property(property="sub_topic_content_one", type="string", example="Content part 1"),
     *                                     @OA\Property(property="sub_topic_content_two", type="string", example="Content part 2"),
     *                                     @OA\Property(property="sub_topic_content_three", type="string", example="Content part 3"),
     *                                     @OA\Property(property="sub_topic_evaluation", type="array", @OA\Items(type="string"))
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input or missing parameters"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson notes not found"
     *     )
     * )
     */

    public function getLessonNoteBySubject($sch_id, $session, $term, $clsm, $subject, $week)
    {
        $start = 0;
        $count = 20;

        if (request()->has('start') && request()->has('count')) {
            $start = request()->input('start');
            $count = request()->input('count');
        }

        $lessonNotes = lesson_note::with('topics.subTopics')
            ->where('sch_id', $sch_id)
            ->where('session', $session)
            ->where('term', $term)
            ->where('clsm', $clsm)
            ->where('subject', $subject)
            ->where('week', $week)
            ->skip($start)
            ->take($count)
            ->get();

        return response()->json([
            "status" => true,
            "message" => "Lesson notes retrieved successfully",
            "pld" => $lessonNotes,
        ]);
    }






    /**
     * @OA\Put(
     *     path="/api/updateLessonNote",
     *     summary="Update an existing lesson note",
     *     description="Updates a lesson note's week and topics if they exist. Deletes existing topics and sub-topics before creating new ones.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sch_id", "session", "term", "clsm", "subject"},
     *             @OA\Property(property="sch_id", type="string", example="123"),
     *             @OA\Property(property="session", type="string", example="2024/2025"),
     *             @OA\Property(property="term", type="string", example="2"),
     *             @OA\Property(property="clsm", type="string", example="1"),
     *             @OA\Property(property="subject", type="string", example="Mathematics"),
     *             @OA\Property(property="week", type="string", example="Week 3"),
     *             @OA\Property(
     *                 property="topics",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="title", type="string", example="Algebra"),
     *                     @OA\Property(property="references", type="array", @OA\Items(type="string"), example={"New General Math Book 1"}),
     *                     @OA\Property(property="content", type="array", @OA\Items(type="string"), example={"Introduction", "Solving equations"}),
     *                     @OA\Property(property="general_evaluation", type="array", @OA\Items(type="string"), example={"Evaluate x + 3 = 5"}),
     *                     @OA\Property(property="weekend_assignment", type="array", @OA\Items(type="string"), example={"Page 23 Q1-5"}),
     *                     @OA\Property(property="theory", type="array", @OA\Items(type="string"), example={"Prove x = 2"}),
     *                     @OA\Property(
     *                         property="sub_topics",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="title", type="string", example="Simple Equations"),
     *                             @OA\Property(property="sub_topic_content_one", type="string", example="Definition"),
     *                             @OA\Property(property="sub_topic_content_two", type="string", example="Examples"),
     *                             @OA\Property(property="sub_topic_content_three", type="string", example="More Examples"),
     *                             @OA\Property(property="sub_topic_content_four", type="string", example="Exercises"),
     *                             @OA\Property(property="sub_topic_content_five", type="string", example="Assessment"),
     *                             @OA\Property(property="sub_topic_evaluation", type="array", @OA\Items(type="string"), example={"Q1: Solve 2x=6"})
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson note updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson note updated successfully"),
     *             @OA\Property(property="pld", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson note not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson note not found")
     *         )
     *     )
     * )
     */



    public function updateLessonNote(Request $request)
    {
        $request->validate([
            'sch_id' => 'required',
            'session' => 'required',
            'term' => 'required',
            'clsm' => 'required',
            'subject' => 'required',
            'week' => 'nullable',
            'topics' => 'nullable|array',
        ]);

        // Find the existing lesson note
        $lessonNote = lesson_note::where([
            'sch_id' => $request->sch_id,
            'session' => $request->session,
            'term' => $request->term,
            'clsm' => $request->clsm,
            'subject' => $request->subject,
        ])->first();

        if (!$lessonNote) {
            return response()->json(['message' => 'Lesson note not found'], 404);
        }

        // Update week (optional fields can be handled here as well)
        $lessonNote->update(['week' => $request->week]);

        // Delete existing topics and subtopics
        foreach ($lessonNote->topics as $topic) {
            $topic->subTopics()->delete();
        }
        $lessonNote->topics()->delete();

        // Create new topics and sub-topics
        foreach ($request->topics as $topicData) {
            $topic = $lessonNote->topics()->create([
                'title' => $topicData['title'],
                'references' => $topicData['references'] ?? [],
                'content' => $topicData['content'] ?? [],
                'general_evaluation' => $topicData['general_evaluation'] ?? [],
                'weekend_assignment' => $topicData['weekend_assignment'] ?? [],
                'theory' => $topicData['theory'] ?? [],
            ]);

            foreach ($topicData['sub_topics'] ?? [] as $sub) {
                $topic->subTopics()->create([
                    'title' => $sub['title'],
                    'sub_topic_content_one' => $sub['sub_topic_content_one'] ?? null,
                    'sub_topic_content_two' => $sub['sub_topic_content_two'] ?? null,
                    'sub_topic_content_three' => $sub['sub_topic_content_three'] ?? null,
                    'sub_topic_content_four' => $sub['sub_topic_content_four'] ?? null,
                    'sub_topic_content_five' => $sub['sub_topic_content_five'] ?? null,
                    'sub_topic_evaluation' => $sub['sub_topic_evaluation'] ?? [],
                ]);
            }
        }

        // Load relationships
        $lessonNote->load('topics.subTopics');

        return response()->json([
            'message' => 'Lesson note updated successfully',
            'pld' => $lessonNote
        ]);
    }





    /**
     * @OA\Get(
     *     path="/api/getSingleLessonNote/{sch_id}/{session}/{term}/{clsm}/{week}/{lessonNoteId}",
     *     summary="Get a single lesson note with topics and subtopics",
     *     description="Retrieve a specific lesson note based on school ID, session, term, class, week, and lesson note ID.",
     *     operationId="getSingleLessonNote",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="sch_id",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="session",
     *         in="path",
     *         required=true,
     *         description="Academic session",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="term",
     *         in="path",
     *         required=true,
     *         description="Academic term (e.g. 1)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class (e.g. 2)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="week",
     *         in="path",
     *         required=true,
     *         description="Week 1",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="lessonNoteId",
     *         in="path",
     *         required=true,
     *         description="The ID of the lesson note",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lesson note retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson note retrieved successfully"),
     *             @OA\Property(property="pld", type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="sch_id", type="string"),
     *                 @OA\Property(property="session", type="string"),
     *                 @OA\Property(property="term", type="string"),
     *                 @OA\Property(property="clsm", type="string"),
     *                 @OA\Property(property="week", type="string"),
     *                 @OA\Property(property="topics", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="references", type="array", @OA\Items(type="string")),
     *                         @OA\Property(property="content", type="array", @OA\Items(type="string")),
     *                         @OA\Property(property="sub_topics", type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer"),
     *                                 @OA\Property(property="title", type="string"),
     *                                 @OA\Property(property="content", type="string")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Lesson note not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson note not found"),
     *             @OA\Property(property="pld", type="string", example=null)
     *         )
     *     )
     * )
     */

    public function getSingleLessonNote($sch_id, $session, $term, $clsm, $week, $lessonNoteId)
    {
        $lessonNote = lesson_note::with('topics.subTopics')
            ->where('sch_id', $sch_id)
            ->where('session', $session)
            ->where('term', $term)
            ->where('clsm', $clsm)
            ->where('week', $week)
            ->where('id', $lessonNoteId)
            ->first();

        if (!$lessonNote) {
            return response()->json([
                "status" => false,
                "message" => "Lesson note not found",
                "pld" => null,
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "Lesson note retrieved successfully",
            "pld" => $lessonNote,
        ]);
    }


    ////////////////////////////////

    /**
     * @OA\Get(
     *     path="/api/getOverallBestStudents/{schid}/{ssn}/{trm}/{clsm}",
     *     summary="Get top 10 overall best students based on average score",
     *     description="Fetches the top 10 students in a school for a given session, term, and class based on their average scores with their rank positions.",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         description="School ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         description="Session",
     *         required=true,
     *         @OA\Schema(type="string", example="2024/2025")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         description="Term ID",
     *         required=true,
     *         @OA\Schema(type="string", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         description="Class ID",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of top 10 best students with ranks",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="student_id", type="integer", example=123),
     *                     @OA\Property(property="name", type="string", example="Doe John A"),
     *                     @OA\Property(property="average", type="number", format="float", example=89.5),
     *                     @OA\Property(property="clsm", type="string", example="JSS1"),
     *                     @OA\Property(property="classarm", type="string", example="A"),
     *                     @OA\Property(property="position", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request due to missing or invalid parameters"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */


    // public function getOverallBestStudents($schid, $ssn, $trm, $clsm)
    // {
    //     // Fetch top 10 students based on average
    //     $results = student_res::where('schid', $schid)
    //         ->where('ssn', $ssn)
    //         ->where('trm', $trm)
    //         ->where('clsm', $clsm)
    //         ->orderByDesc('avg')
    //         ->take(10)
    //         ->get();

    //     // Fetch student details
    //     $students = student::whereIn('sid', $results->pluck('stid'))->get()->keyBy('sid');

    //     // Fetch class info
    //     $classArms = sch_cls::all()->keyBy('id');
    //     $classes = cls::all()->keyBy('id');

    //     // Generate positions
    //     $position = 1;
    //     $lastAvg = null;
    //     $rank = 1;

    //     $output = $results->map(function ($res) use ($students, $classes, $classArms, &$position, &$lastAvg, &$rank) {
    //         $student = $students[$res->stid] ?? null;

    //         // Fetch class and class arm
    //         $classArm = $classArms[$res->clsa] ?? null;
    //         $class = $classArm ? ($classes[$classArm->cls_id] ?? null) : null;

    //         // Generate suid
    //         $suid = $student
    //             ? ($student->sch3 . '/' . $student->year . '/' . $student->term . '/' . strval($student->count))
    //             : 'Unknown';

    //         // Update rank if average changes
    //         if ($lastAvg !== null && $res->avg < $lastAvg) {
    //             $rank = $position;
    //         }

    //         $lastAvg = $res->avg;

    //         return [
    //             'student_id' => $suid,
    //             'name' => $student ? trim("{$student->lname} {$student->fname} {$student->mname}") : 'Unknown',
    //             'average' => $res->avg,
    //             'class_id' => $res->clsm,
    //             'class_name' => $class ? $class->name : 'Unknown',
    //             'class_arm' => $classArm ? $classArm->name : 'Unknown',
    //             'position' => $rank,
    //         ];

    //         $position++;
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'pld' => $output
    //     ]);
    // }


    public function getOverallBestStudents($schid, $ssn, $trm, $clsm)
    {
        $results = student_res::where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('clsm', $clsm)
            ->orderByDesc('avg')
            ->take(10)
            ->get();

        $students = student::whereIn('sid', $results->pluck('stid'))->get()->keyBy('sid');
        $classArms = sch_cls::all()->keyBy('id');
        $classes = cls::all()->keyBy('id');

        // Initialize position variables
        $position = 1;
        $lastAvg = null;
        $rank = 1;

        $output = $results->map(function ($res) use ($students, $classes, $classArms, &$position, &$lastAvg, &$rank) {
            $student = $students[$res->stid] ?? null;
            $classArm = $classArms[$res->clsa] ?? null;
            $class = $classArm ? ($classes[$classArm->cls_id] ?? null) : null;

            $suid = $student
                ? ($student->sch3 . '/' . $student->year . '/' . $student->term . '/' . strval($student->count))
                : 'Unknown';

            // Update rank if average changes
            if ($lastAvg !== null && $res->avg < $lastAvg) {
                $rank = $position;
            }
            $lastAvg = $res->avg;

            // Convert position to ordinal
            $ordinal = $this->toOrdinal($rank);  // Custom function below

            $position++; // Increment after processing

            return [
                'student_id' => $suid,
                'name' => $student ? trim("{$student->lname} {$student->fname} {$student->mname}") : 'Unknown',
                'average' => $res->avg,
                'class_id' => $res->clsm,
                'class_name' => $class ? $class->name : 'Unknown',
                'class_arm' => $classArm ? $classArm->name : 'Unknown',
                'position' => "Overall $ordinal",
            ];
        });

        return response()->json([
            'status' => 'success',
            'pld' => $output
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getBestStudentsInSubject/{schid}/{ssn}/{trm}/{clsm}/{sbj}",
     *     summary="Fetch top best-performing students in a particular subject with pagination",
     *     description="Returns a list of students with the highest total scores in a given subject, class, term, and session. Supports pagination via 'start' and 'count' query parameters.",
     *     operationId="getBestStudentsInSubject",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session (e.g., 2024)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         required=true,
     *         description="Term ID (e.g., 1, 2, 3)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sbj",
     *         in="path",
     *         required=true,
     *         description="Subject ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Offset for pagination (e.g., 0, 10, 20)",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of records to return (e.g., 10, 20)",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of top students",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="start", type="integer", example=0),
     *             @OA\Property(property="count", type="integer", example=20),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="student_id", type="string", example="123"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="total_score", type="number", format="float", example=87.5),
     *                     @OA\Property(property="position", type="integer", example=1),
     *                     @OA\Property(property="class_id", type="integer", example=11),
     *                     @OA\Property(property="class_name", type="string", example="SS2"),
     *                     @OA\Property(property="class_arm", type="string", example="A"),
     *                     @OA\Property(property="subject", type="string", example="Mathematics")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No scores found for this subject",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No scores found for this subject")
     *         )
     *     )
     * )
     */




    // public function getBestStudentsInSubject($schid, $ssn, $trm, $clsm, $sbj)
    // {
    //     $start = request()->input('start', 0);
    //     $count = request()->input('count', 20);

    //     $subject = subj::find($sbj);
    //     $subjectName = $subject ? $subject->name : 'Unknown Subject';

    //     $class = cls::find($clsm);
    //     $className = $class ? $class->name : 'Unknown Class';

    //     $classArms = sch_cls::where('schid', $schid)->get()->keyBy('id');

    //     $scores = std_score::select('stid', DB::raw('SUM(scr) as total_score'))
    //         ->where(compact('schid', 'ssn', 'trm'))
    //         ->where('clsid', $clsm)
    //         ->where('sbj', $sbj)
    //         ->groupBy('stid')
    //         ->orderByDesc('total_score')
    //         ->skip($start)
    //         ->take($count)
    //         ->get();

    //     if ($scores->isEmpty()) {
    //         return response()->json(['message' => 'No scores found for this subject'], 404);
    //     }

    //     $studentIds = $scores->pluck('stid');

    //     // Fetch student info from both old_student and student tables
    //     $oldStudents = old_student::whereIn('sid', $studentIds)->get()->keyBy('sid');
    //     $mainStudents = student::whereIn('sid', $studentIds)->get()->keyBy('sid');

    //     $ranked = $scores->values()->map(function ($item, $index) use ($oldStudents, $mainStudents, $classArms, $className, $clsm, $start, $subjectName) {
    //         $old = $oldStudents[$item->stid] ?? null;
    //         $main = $mainStudents[$item->stid] ?? null;

    //         $armId = $old?->clsa;
    //         $armName = $armId && isset($classArms[$armId]) ? $classArms[$armId]->name : 'Unknown Class Arm';

    //         // Build suid from student table
    //         $suid = $main
    //             ? ($main->sch3 . '/' . $main->year . '/' . $main->term . '/' . strval($main->count))
    //             : ($old->suid ?? 'Unknown');

    //         return [
    //             'student_id' => $suid,
    //             'full_name' => $old ? trim("{$old->lname} {$old->fname} {$old->mname}") : 'Unknown',
    //             'total_score' => round($item->total_score, 2),
    //             'position' => $start + $index + 1,
    //             'class_id' => $clsm,
    //             'class_name' => $className,
    //             'class_arm' => $armName,
    //             'subject' => $subjectName,
    //         ];
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'start' => $start,
    //         'count' => $count,
    //         'results' => $ranked
    //     ]);
    // }

    public function getBestStudentsInSubject($schid, $ssn, $trm, $clsm, $sbj)
    {
        $start = request()->input('start', 0);
        $count = request()->input('count', 20);

        $subject = subj::find($sbj);
        $subjectName = $subject ? $subject->name : 'Unknown Subject';

        $class = cls::find($clsm);
        $className = $class ? $class->name : 'Unknown Class';

        $classArms = sch_cls::where('schid', $schid)->get()->keyBy('id');

        $scores = std_score::select('stid', DB::raw('SUM(scr) as total_score'))
            ->where(compact('schid', 'ssn', 'trm'))
            ->where('clsid', $clsm)
            ->where('sbj', $sbj)
            ->groupBy('stid')
            ->orderByDesc('total_score')
            ->skip($start)
            ->take($count)
            ->get();

        if ($scores->isEmpty()) {
            return response()->json(['message' => 'No scores found for this subject'], 404);
        }

        $studentIds = $scores->pluck('stid');

        $oldStudents = old_student::whereIn('sid', $studentIds)->get()->keyBy('sid');
        $mainStudents = student::whereIn('sid', $studentIds)->get()->keyBy('sid');

        $ranked = $scores->values()->map(function ($item, $index) use ($oldStudents, $mainStudents, $classArms, $className, $clsm, $start, $subjectName) {
            $old = $oldStudents[$item->stid] ?? null;
            $main = $mainStudents[$item->stid] ?? null;

            $armId = $old?->clsa;
            $armName = $armId && isset($classArms[$armId]) ? $classArms[$armId]->name : 'Unknown Class Arm';

            $suid = $main
                ? ($main->sch3 . '/' . $main->year . '/' . $main->term . '/' . strval($main->count))
                : ($old->suid ?? 'Unknown');

            return [
                'student_id' => $suid,
                'full_name' => $old ? trim("{$old->lname} {$old->fname} {$old->mname}") : 'Unknown',
                'total_score' => round($item->total_score, 2),
                'position' => 'Overall ' . $this->toOrdinal($start + $index + 1),
                'class_id' => $clsm,
                'class_name' => $className,
                'class_arm' => $armName,
                'subject' => $subjectName,
            ];
        });

        return response()->json([
            'status' => 'success',
            'start' => $start,
            'count' => $count,
            'results' => $ranked
        ]);
    }


    private function toOrdinal($number)
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }


    /**
     * @OA\Get(
     *     path="/api/getAllSubjectsPerformance/{schid}/{ssn}/{trm}/{clsm}",
     *     summary="Get performance statistics for all subjects based on school, session, term, and class",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         description="School ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         description="Session (2024)",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="path",
     *         description="Term ID (e.g., 1, 2, or 3)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         description="Class or class ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject performance statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="subject",
     *                         type="string",
     *                         example="MATHEMATICS"
     *                     ),
     *                     @OA\Property(
     *                         property="total_students",
     *                         type="integer",
     *                         example=120
     *                     ),
     *                     @OA\Property(
     *                         property="grades",
     *                         type="object",
     *                         example={"A":30,"B":40,"C":20,"D":10,"E":10,"F":10},
     *                         @OA\Property(property="A", type="integer"),
     *                         @OA\Property(property="B", type="integer"),
     *                         @OA\Property(property="C", type="integer"),
     *                         @OA\Property(property="D", type="integer"),
     *                         @OA\Property(property="E", type="integer"),
     *                         @OA\Property(property="F", type="integer")
     *                     ),
     *                     @OA\Property(
     *                         property="credits_and_above",
     *                         type="integer",
     *                         example=90
     *                     ),
     *                     @OA\Property(
     *                         property="percentage_pass",
     *                         type="number",
     *                         format="float",
     *                         example=75.00
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    // public function getAllSubjectsPerformance($schid, $ssn, $trm, $clsm)
    // {
    //     // Fetch all total scores grouped by student and subject
    //     $scores = std_score::select('sbj', 'stid', DB::raw('SUM(scr) as total_score'))
    //         ->where('schid', $schid)
    //         ->where('ssn', $ssn)
    //         ->where('trm', $trm)
    //         ->where('clsid', $clsm)
    //         ->groupBy('sbj', 'stid')
    //         ->get();

    //     // Get unique subject codes from the scores
    //     $subjectCodes = $scores->pluck('sbj')->unique();

    //     // Fetch subject names for these codes from subj table
    //     $subjectNames = subj::whereIn('id', $subjectCodes)
    //         ->pluck('name', 'id'); // key = id (sbj), value = name

    //     // Grade brackets
    //     $grades = [
    //         'A' => [70, 100],
    //         'B' => [60, 69],
    //         'C' => [50, 59],
    //         'D' => [45, 49],
    //         'E' => [40, 44],
    //         'F' => [0, 39]
    //     ];

    //     // Temp structure to hold counts per subject
    //     $subjectStats = [];

    //     foreach ($scores as $score) {
    //         $subjectCode = $score->sbj;
    //         $total = $score->total_score;

    //         if (!isset($subjectStats[$subjectCode])) {
    //             $subjectStats[$subjectCode] = [
    //                 'grades' => ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'F'=>0],
    //                 'total_students' => 0,
    //                 'credits_and_above' => 0,
    //                 'percentage_pass' => 0
    //             ];
    //         }

    //         foreach ($grades as $grade => [$min, $max]) {
    //             if ($total >= $min && $total <= $max) {
    //                 $subjectStats[$subjectCode]['grades'][$grade]++;
    //                 break;
    //             }
    //         }
    //     }

    //     // Calculate totals and percentages per subject
    //     foreach ($subjectStats as $subjectCode => &$stats) {
    //         $stats['total_students'] = array_sum($stats['grades']);
    //         $stats['credits_and_above'] = $stats['grades']['A'] + $stats['grades']['B'] + $stats['grades']['C'];
    //         $stats['percentage_pass'] = $stats['total_students'] > 0
    //             ? round(($stats['credits_and_above'] / $stats['total_students']) * 100, 2)
    //             : 0;
    //     }
    //     unset($stats); // clear reference

    //     // Build the response "pld" array with subject name and class included
    //     $pld = [];
    //     foreach ($subjectStats as $subjectCode => $stats) {
    //         $pld[] = [
    //             'subject_name' => $subjectNames[$subjectCode] ?? 'Unknown',
    //             'clsm' => $clsm,  // Add class here
    //             'total_students' => $stats['total_students'],
    //             'grades' => $stats['grades'],
    //             'credits_and_above' => $stats['credits_and_above'],
    //             'percentage_pass' => $stats['percentage_pass']
    //         ];
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'pld' => $pld
    //     ]);
    // }

    public function getAllSubjectsPerformance($schid, $ssn, $trm, $clsm)
    {
        // Fetch the class name
        $class = cls::find($clsm);
        $className = $class ? $class->name : 'Unknown Class';

        // Get all subject scores grouped by student and subject
        $scores = std_score::select('sbj', 'stid', DB::raw('SUM(scr) as total_score'))
            ->where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->where('clsid', $clsm)
            ->groupBy('sbj', 'stid')
            ->get();

        // Get unique subject codes
        $subjectCodes = $scores->pluck('sbj')->unique();

        // Fetch subject names
        $subjectNames = subj::whereIn('id', $subjectCodes)
            ->pluck('name', 'id');

        // ✅ Fetch school-defined grade brackets from `sch_grade`
        $grades = sch_grade::where('schid', $schid)
            ->where('clsid', $clsm)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->orderByDesc('g0')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->grd => [(float) $item->g0, (float) $item->g1]];
            })
            ->toArray();

        // Temp structure to hold stats per subject
        $subjectStats = [];

        foreach ($scores as $score) {
            $subjectCode = $score->sbj;
            $total = $score->total_score;

            if (!isset($subjectStats[$subjectCode])) {
                // Initialize grade buckets dynamically from school-defined grades
                $subjectStats[$subjectCode] = [
                    'grades' => array_fill_keys(array_keys($grades), 0),
                    'total_students' => 0,
                    'credits_and_above' => 0,
                    'percentage_pass' => 0
                ];
            }

            foreach ($grades as $grade => [$min, $max]) {
                if ($total >= $min && $total <= $max) {
                    $subjectStats[$subjectCode]['grades'][$grade]++;
                    break;
                }
            }
        }

        // Process stats
        foreach ($subjectStats as $subjectCode => &$stats) {
            $stats['total_students'] = array_sum($stats['grades']);

            // Define what counts as a "pass" (customize this logic if needed)
            $credits = ['A', 'B', 'C']; // Change based on your grading labels
            foreach ($credits as $credit) {
                if (isset($stats['grades'][$credit])) {
                    $stats['credits_and_above'] += $stats['grades'][$credit];
                }
            }

            $stats['percentage_pass'] = $stats['total_students'] > 0
                ? round(($stats['credits_and_above'] / $stats['total_students']) * 100, 2)
                : 0;
        }
        unset($stats);

        // Build the response
        $pld = [];
        foreach ($subjectStats as $subjectCode => $stats) {
            $pld[] = [
                'subject_name' => $subjectNames[$subjectCode] ?? 'Unknown',
                'class_id' => $clsm,
                'class_name' => $className,
                'total_students' => $stats['total_students'],
                'grades' => $stats['grades'],
                'credits_and_above' => $stats['credits_and_above'],
                'percentage_pass' => $stats['percentage_pass']
            ];
        }

        return response()->json([
            'status' => 'success',
            'pld' => $pld
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getCumulativeResult",
     *     summary="Get a student's cumulative academic result for all terms in a session",
     *     description="Retrieves cumulative results including term scores, averages, grades, positions, and student details.",
     *     operationId="getCumulativeResult",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="query",
     *         required=true,
     *         description="Session (Academic Year)",
     *         @OA\Schema(type="string", example="2024")
     *     ),
     *     @OA\Parameter(
     *         name="sid",
     *         in="query",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string", example="565")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful cumulative result retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="sid", type="string", example="565"),
     *             @OA\Property(property="clsm", type="string", example="JS3"),
     *             @OA\Property(property="clsa", type="string", example="A"),
     *             @OA\Property(property="basic_info", type="object",
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="gender", type="string", example="Male")
     *             ),
     *             @OA\Property(property="academic_info", type="object",
     *                 @OA\Property(property="entry_year", type="string", example="2021"),
     *                 @OA\Property(property="current_class", type="string", example="JS3")
     *             ),
     *             @OA\Property(
     *                 property="results",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="subject_id", type="integer", example=1),
     *                     @OA\Property(property="subject", type="string", example="Mathematics"),
     *                     @OA\Property(property="1st_term_total", type="number", format="float", example=78.5),
     *                     @OA\Property(property="2nd_term_total", type="number", format="float", example=82.0),
     *                     @OA\Property(property="3rd_term_total", type="number", format="float", example=85.5),
     *                     @OA\Property(property="yearly_average", type="number", format="float", example=82.0),
     *                     @OA\Property(property="grade", type="string", example="A"),
     *                     @OA\Property(property="position", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Student not found or no scores available",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Student not found in specified class/arm")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="schid",
     *                     type="array",
     *                     @OA\Items(type="string", example="The schid field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    // public function getCumulativeResult(Request $request)
    // {
    //     $schid = $request->input('schid');
    //     $ssn = $request->input('ssn');
    //     $stid = $request->input('sid');

    //     // Grade brackets
    //     $grades = [
    //         'A' => [70, 100],
    //         'B' => [60, 69.99],
    //         'C' => [50, 59.99],
    //         'D' => [45, 49.99],
    //         'E' => [40, 44.99],
    //         'F' => [0, 39.99]
    //     ];

    //     $assignGrade = function($average) use ($grades) {
    //         foreach ($grades as $grade => [$min, $max]) {
    //             if ($average >= $min && $average <= $max) {
    //                 return $grade;
    //             }
    //         }
    //         return 'N/A';
    //     };

    //     // Fetch student info including clsm and clsa
    //     $studentInfo = old_student::where('sid', $stid)
    //         ->where('schid', $schid)
    //         ->where('ssn', $ssn)
    //         ->first();

    //     if (!$studentInfo) {
    //         return response()->json(['message' => 'Student not found'], 404);
    //     }

    //     $clsm = $studentInfo->clsm;
    //     $clsa = $studentInfo->clsa;

    //     // Get class and class arm names
    //     $className = cls::where('id', $clsm)->value('name');
    //     $classArmName = sch_cls::where('id', $clsa)->value('name');

    //     // Get current student record to generate suid
    //     $student = student::where('schid', $schid)
    //         ->where('sid', $stid)
    //         ->first();

    //     $suid = null;
    //     if ($student) {
    //         $ssn_real = $student->year;
    //         $term = $student->term;
    //         $count = $student->count;
    //         $suid = $student->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //     }

    //     // Subjects and results
    //     $subjects = std_score::where('schid', $schid)
    //         ->where('ssn', $ssn)
    //         ->where('clsid', $clsm)
    //         ->groupBy('sbj')
    //         ->pluck('sbj')
    //         ->toArray();

    //     $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

    //     $result = [];

    //     foreach ($subjects as $subjectId) {
    //         $subjectAverages = std_score::select(
    //                 'stid',
    //                 DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //                 DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //                 DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //             )
    //             ->where('sbj', $subjectId)
    //             ->where('schid', $schid)
    //             ->where('ssn', $ssn)
    //             ->where('clsid', $clsm)
    //             ->groupBy('stid')
    //             ->get();

    //         $averagesWithTerms = $subjectAverages->map(function ($item) {
    //             $termCount = 0;
    //             $total = 0;
    //             if ($item->t1 > 0) { $total += $item->t1; $termCount++; }
    //             if ($item->t2 > 0) { $total += $item->t2; $termCount++; }
    //             if ($item->t3 > 0) { $total += $item->t3; $termCount++; }

    //             $average = $termCount > 0 ? $total / $termCount : 0;

    //             return [
    //                 'stid' => $item->stid,
    //                 't1' => $item->t1,
    //                 't2' => $item->t2,
    //                 't3' => $item->t3,
    //                 'average' => round($average, 2)
    //             ];
    //         })->sortByDesc('average')->values()->all();

    //         $positions = [];
    //         foreach ($averagesWithTerms as $index => $entry) {
    //             $positions[$entry['stid']] = $index + 1;
    //         }

    //         $current = collect($averagesWithTerms)->firstWhere('stid', $stid);
    //         if ($current) {
    //             $grade = $assignGrade($current['average']);
    //             $result[] = [
    //                 'subject_id' => $subjectId,
    //                 'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
    //                 '1st_term_total' => $current['t1'],
    //                 '2nd_term_total' => $current['t2'],
    //                 '3rd_term_total' => $current['t3'],
    //                 'yearly_average' => $current['average'],
    //                 'grade' => $grade,
    //                 'position' => $positions[$stid] ?? null,
    //             ];
    //         }
    //     }

    //         // Fetch gender from student_basic_data
    //         $studentGender = student_basic_data::where('user_id', $stid)->value('sex');
    //     return response()->json([
    //         'sid' => $stid,
    //         'student_name' => $studentInfo->lname . ' ' . $studentInfo->fname,
    //         'student_id' => $suid,
    //         'gender' => $studentGender ?? 'Not specified',
    //         'class' => $className ?? 'Class ID: ' . $clsm,
    //         'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
    //         'session' => $ssn,
    //         'school_id' => $schid,
    //         'pld' => $result,
    //     ]);
    // }


    // public function getCumulativeResult(Request $request)
    // {
    //     $schid = $request->input('schid');
    //     $ssn = $request->input('ssn');
    //     $stid = $request->input('sid');

    //     // Step 1: Get student info
    //     $studentInfo = old_student::where('sid', $stid)
    //         ->where('schid', $schid)
    //         ->where('ssn', $ssn)
    //         ->first();

    //     if (!$studentInfo) {
    //         return response()->json(['message' => 'Student not found'], 404);
    //     }

    //     $clsm = $studentInfo->clsm;
    //     $clsa = $studentInfo->clsa;

    //     // Step 2: Get dynamic grading
    //     $grades = sch_grade::where('schid', $schid)
    //         ->where('clsid', $clsm)
    //         ->where('ssn', $ssn)
    //         ->orderByDesc('g0')
    //         ->get()
    //         ->mapWithKeys(function ($item) {
    //             return [$item->grd => [(float)$item->g0, (float)$item->g1]];
    //         })
    //         ->toArray();

    //     $assignGrade = function ($average) use ($grades) {
    //         foreach ($grades as $grade => [$min, $max]) {
    //             if ($average >= $min && $average <= $max) {
    //                 return $grade;
    //             }
    //         }
    //         return 'N/A';
    //     };

    //     // Step 3: Additional info
    //     $className = cls::where('id', $clsm)->value('name');
    //     $classArmName = sch_cls::where('id', $clsa)->value('name');

    //     $student = student::where('schid', $schid)
    //         ->where('sid', $stid)
    //         ->first();

    //     $suid = null;
    //     if ($student) {
    //         $ssn_real = $student->year;
    //         $term = $student->term;
    //         $count = $student->count;
    //         $suid = $student->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //     }

    //     // Step 4: Subjects
    //     $subjects = std_score::where('schid', $schid)
    //         ->where('ssn', $ssn)
    //         ->where('clsid', $clsm)
    //         ->groupBy('sbj')
    //         ->pluck('sbj')
    //         ->toArray();

    //     $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

    //     $result = [];
    //     $studentSubjectAverages = [];
    //     $classSubjectAverages = [];

    //     foreach ($subjects as $subjectId) {
    //         $subjectAverages = std_score::select(
    //             'stid',
    //             DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //             DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //             DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //         )
    //             ->where('sbj', $subjectId)
    //             ->where('schid', $schid)
    //             ->where('ssn', $ssn)
    //             ->where('clsid', $clsm)
    //             ->groupBy('stid')
    //             ->get();

    //         $averagesWithTerms = $subjectAverages->map(function ($item) {
    //             $termCount = 0;
    //             $total = 0;
    //             if ($item->t1 > 0) { $total += $item->t1; $termCount++; }
    //             if ($item->t2 > 0) { $total += $item->t2; $termCount++; }
    //             if ($item->t3 > 0) { $total += $item->t3; $termCount++; }
    //             $average = $termCount > 0 ? $total / $termCount : 0;
    //             return [
    //                 'stid' => $item->stid,
    //                 't1' => $item->t1,
    //                 't2' => $item->t2,
    //                 't3' => $item->t3,
    //                 'average' => round($average, 2)
    //             ];
    //         })->sortByDesc('average')->values()->all();

    //         // Compute class average for subject
    //         $allAverages = array_column($averagesWithTerms, 'average');
    //         if (count($allAverages)) {
    //             $classSubjectAverages[] = array_sum($allAverages) / count($allAverages);
    //         }

    //         // Current student's scores
    //         $current = collect($averagesWithTerms)->firstWhere('stid', $stid);

    //         if ($current) {
    //             $grade = $assignGrade($current['average']);
    //             $studentSubjectAverages[] = $current['average'];

    //             $positions = [];
    //             foreach ($averagesWithTerms as $index => $entry) {
    //                 $positions[$entry['stid']] = $index + 1;
    //             }

    //             $result[] = [
    //                 'subject_id' => $subjectId,
    //                 'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
    //                 '1st_term_total' => $current['t1'],
    //                 '2nd_term_total' => $current['t2'],
    //                 '3rd_term_total' => $current['t3'],
    //                 'yearly_average' => $current['average'],
    //                 'grade' => $grade,
    //                 'position' => $positions[$stid] ?? null,
    //             ];
    //         }
    //     }

    //     // Final averages
    //     $finalAverage = count($studentSubjectAverages) > 0
    //         ? number_format(array_sum($studentSubjectAverages) / count($studentSubjectAverages), 2, '.', '')
    //         : number_format(0, 2, '.', '');

    //     $finalGrade = $assignGrade($finalAverage);

    //     $classAverage = count($classSubjectAverages) > 0
    //         ? number_format(array_sum($classSubjectAverages) / count($classSubjectAverages), 2, '.', '')
    //         : number_format(0, 2, '.', '');

    //     $classGrade = $assignGrade($classAverage);

    //     $studentGender = student_basic_data::where('user_id', $stid)->value('sex');

    //     return response()->json([
    //         'sid' => $stid,
    //         'student_name' => $studentInfo->lname . ' ' . $studentInfo->fname,
    //         'student_id' => $suid,
    //         'gender' => $studentGender ?? 'Not specified',
    //         'class' => $className ?? 'Class ID: ' . $clsm,
    //         'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
    //         'session' => $ssn,
    //         'school_id' => $schid,
    //         'final_average' => $finalAverage,
    //         'final_average_grade' => $finalGrade,
    //         'class_average' => $classAverage,
    //         'class_average_grade' => $classGrade,
    //         'pld' => $result,
    //     ]);
    // }


    ////////////////////////////////////////////////////

    public function getCumulativeResult(Request $request)
    {
        $schid = $request->input('schid');
        $ssn = $request->input('ssn');
        $stid = $request->input('sid');

        // Step 1: Get student info
        $studentInfo = old_student::where('sid', $stid)
            ->where('schid', $schid)
            ->where('ssn', $ssn)
            ->first();

        if (!$studentInfo) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $clsm = $studentInfo->clsm;
        $clsa = $studentInfo->clsa;

        // Step 2: Get dynamic grading
        $grades = sch_grade::where('schid', $schid)
            ->where('clsid', $clsm)
            ->where('ssn', $ssn)
            ->orderByDesc('g0')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->grd => [(float)$item->g0, (float)$item->g1]];
            })
            ->toArray();

        // Updated: Use floor to match range properly
        $assignGrade = function ($average) use ($grades) {
            $check = floor($average);
            foreach ($grades as $grade => [$min, $max]) {
                if ($check >= $min && $check <= $max) {
                    return $grade;
                }
            }
            return 'N/A';
        };

        // Step 3: Additional info
        $className = cls::where('id', $clsm)->value('name');
        $classArmName = sch_cls::where('id', $clsa)->value('name');

        $student = student::where('schid', $schid)
            ->where('sid', $stid)
            ->first();

        $suid = null;
        if ($student) {
            $ssn_real = $student->year;
            $term = $student->term;
            $count = $student->count;
            $suid = $student->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
        }

        // Step 4: Subjects
        $subjects = std_score::where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('clsid', $clsm)
            ->groupBy('sbj')
            ->pluck('sbj')
            ->toArray();

        $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

        $result = [];
        $studentSubjectAverages = [];
        $classSubjectAverages = [];

        foreach ($subjects as $subjectId) {
            $subjectAverages = std_score::select(
                'stid',
                DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
                DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
                DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
            )
                ->where('sbj', $subjectId)
                ->where('schid', $schid)
                ->where('ssn', $ssn)
                ->where('clsid', $clsm)
                ->groupBy('stid')
                ->get();

            $averagesWithTerms = $subjectAverages->map(function ($item) {
                $termCount = 0;
                $total = 0;
                if ($item->t1 > 0) {
                    $total += $item->t1;
                    $termCount++;
                }
                if ($item->t2 > 0) {
                    $total += $item->t2;
                    $termCount++;
                }
                if ($item->t3 > 0) {
                    $total += $item->t3;
                    $termCount++;
                }
                $average = $termCount > 0 ? $total / $termCount : 0;
                return [
                    'stid' => $item->stid,
                    't1' => $item->t1,
                    't2' => $item->t2,
                    't3' => $item->t3,
                    'average' => round($average, 2)
                ];
            })->sortByDesc('average')->values()->all();

            // Compute class average for subject
            $allAverages = array_column($averagesWithTerms, 'average');
            if (count($allAverages)) {
                $classSubjectAverages[] = array_sum($allAverages) / count($allAverages);
            }

            // Current student's scores
            $current = collect($averagesWithTerms)->firstWhere('stid', $stid);

            if ($current) {
                $grade = $assignGrade($current['average']);
                $studentSubjectAverages[] = $current['average'];

                $positions = [];
                foreach ($averagesWithTerms as $index => $entry) {
                    $positions[$entry['stid']] = $index + 1;
                }

                $result[] = [
                    'subject_id' => $subjectId,
                    'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
                    '1st_term_total' => $current['t1'],
                    '2nd_term_total' => $current['t2'],
                    '3rd_term_total' => $current['t3'],
                    'yearly_average' => $current['average'],
                    'grade' => $grade,
                    'position' => $positions[$stid] ?? null,
                ];
            }
        }

        // Final averages
        $finalAverage = count($studentSubjectAverages) > 0
            ? number_format(array_sum($studentSubjectAverages) / count($studentSubjectAverages), 2, '.', '')
            : number_format(0, 2, '.', '');

        $finalGrade = $assignGrade($finalAverage);

        $classAverage = count($classSubjectAverages) > 0
            ? number_format(array_sum($classSubjectAverages) / count($classSubjectAverages), 2, '.', '')
            : number_format(0, 2, '.', '');

        $classGrade = $assignGrade($classAverage);

        $studentGender = student_basic_data::where('user_id', $stid)->value('sex');

        return response()->json([
            'sid' => $stid,
            'student_name' => $studentInfo->lname . ' ' . $studentInfo->fname,
            'student_id' => $suid,
            'gender' => $studentGender ?? 'Not specified',
            'class' => $className ?? 'Class ID: ' . $clsm,
            'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
            'session' => $ssn,
            'school_id' => $schid,
            'final_average' => $finalAverage,
            'final_average_grade' => $finalGrade,
            'class_average' => $classAverage,
            'class_average_grade' => $classGrade,
            'pld' => $result,
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getAllStudentCumulativeResult",
     *     summary="Get cumulative result of students for a class",
     *     description="Returns cumulative result (Term 1, Term 2, Term 3 totals, average, and grade) for all students in a specific class and class arm within a school session.",
     *     operationId="getAllStudentCumulativeResult",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="query",
     *         required=true,
     *         description="School session (e.g., 2023/2024)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="query",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="query",
     *         required=true,
     *         description="Class Arm ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Pagination start index (default 0)",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         required=false,
     *         description="Number of students to return (default 20)",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of students with cumulative result",
     *         @OA\JsonContent(
     *             @OA\Property(property="start", type="integer"),
     *             @OA\Property(property="count", type="integer"),
     *             @OA\Property(
     *                 property="students",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="student_id", type="string"),
     *                     @OA\Property(property="student_name", type="string"),
     *                     @OA\Property(property="class", type="string"),
     *                     @OA\Property(property="class_arm", type="string"),
     *                     @OA\Property(property="session", type="string"),
     *                     @OA\Property(property="school_id", type="integer"),
     *                     @OA\Property(
     *                         property="subjects",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="subject_id", type="integer"),
     *                             @OA\Property(property="subject_name", type="string"),
     *                             @OA\Property(property="1st_term_total", type="number", format="float"),
     *                             @OA\Property(property="2nd_term_total", type="number", format="float"),
     *                             @OA\Property(property="3rd_term_total", type="number", format="float"),
     *                             @OA\Property(property="yearly_average", type="number", format="float"),
     *                             @OA\Property(property="grade", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No students found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No students found")
     *         )
     *     )
     * )
     */


    // public function getAllStudentCumulativeResult(Request $request)
    // {
    //     $schid = $request->input('schid');
    //     $ssn = $request->input('ssn');
    //     $clsm = $request->input('clsm');
    //     $clsa = $request->input('clsa');
    //     $start = $request->input('start', 0);
    //     $count = $request->input('count', 20);

    //     // Grade brackets
    //     $grades = [
    //         'A' => [70, 100],
    //         'B' => [60, 69.99],
    //         'C' => [50, 59.99],
    //         'D' => [45, 49.99],
    //         'E' => [40, 44.99],
    //         'F' => [0, 39.99]
    //     ];

    //     $assignGrade = function($average) use ($grades) {
    //         foreach ($grades as $grade => [$min, $max]) {
    //             if ($average >= $min && $average <= $max) {
    //                 return $grade;
    //             }
    //         }
    //         return 'N/A';
    //     };

    //     // Get class and class arm names
    //     $className = cls::where('id', $clsm)->value('name');
    //     $classArmName = sch_cls::where('id', $clsa)->value('name');

    //     // Get students in this class (paginated)
    //     $students = old_student::where([
    //             ['schid', $schid],
    //             ['ssn', $ssn],
    //             ['clsm', $clsm],
    //             ['clsa', $clsa]
    //         ])
    //         ->offset($start)
    //         ->limit($count)
    //         ->get();

    //     if ($students->isEmpty()) {
    //         return response()->json(['message' => 'No students found'], 404);
    //     }

    //     // Subjects in this class
    //     $subjects = std_score::where([
    //             ['schid', $schid],
    //             ['ssn', $ssn],
    //             ['clsid', $clsm],
    //         ])
    //         ->groupBy('sbj')
    //         ->pluck('sbj')
    //         ->toArray();

    //     $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

    //     // Calculate subject positions for all students
    //     $subjectAverages = [];

    //     foreach ($subjects as $subjectId) {
    //         $studentsScores = std_score::select(
    //                 'stid',
    //                 DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //                 DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //                 DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //             )
    //             ->where([
    //                 ['sbj', $subjectId],
    //                 ['schid', $schid],
    //                 ['ssn', $ssn],
    //                 ['clsid', $clsm],
    //             ])
    //             ->groupBy('stid')
    //             ->get()
    //             ->map(function ($item) {
    //                 $total = 0;
    //                 $count = 0;
    //                 if ($item->t1 > 0) { $total += $item->t1; $count++; }
    //                 if ($item->t2 > 0) { $total += $item->t2; $count++; }
    //                 if ($item->t3 > 0) { $total += $item->t3; $count++; }
    //                 $average = $count > 0 ? round($total / $count, 2) : 0;
    //                 return [
    //                     'stid' => $item->stid,
    //                     'average' => $average
    //                 ];
    //             })
    //             ->sortByDesc('average')
    //             ->values()
    //             ->toArray();

    //         // Assign position with ties handled
    //         $ranked = [];
    //         $position = 1;
    //         $lastAvg = null;
    //         $sameRankCount = 0;

    //         foreach ($studentsScores as $index => $row) {
    //             if ($row['average'] === $lastAvg) {
    //                 $sameRankCount++;
    //             } else {
    //                 $position += $sameRankCount;
    //                 $sameRankCount = 1;
    //             }
    //             $ranked[$row['stid']] = $position;
    //             $lastAvg = $row['average'];
    //         }

    //         $subjectAverages[$subjectId] = $ranked;
    //     }

    //     $response = [];

    //     foreach ($students as $student) {
    //         $stid = $student->sid;
    //         $result = [];

    //         foreach ($subjects as $subjectId) {
    //             $score = std_score::select(
    //                     DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //                     DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //                     DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //                 )
    //                 ->where([
    //                     ['sbj', $subjectId],
    //                     ['schid', $schid],
    //                     ['ssn', $ssn],
    //                     ['clsid', $clsm],
    //                     ['stid', $stid]
    //                 ])
    //                 ->first();

    //             // Skip subject if ALL terms are null or zero
    //             if (
    //                 (is_null($score->t1) || $score->t1 == 0) &&
    //                 (is_null($score->t2) || $score->t2 == 0) &&
    //                 (is_null($score->t3) || $score->t3 == 0)
    //             ) {
    //                 continue;
    //             }

    //             $total = 0;
    //             $termCount = 0;

    //             if (!is_null($score->t1) && $score->t1 > 0) { $total += $score->t1; $termCount++; }
    //             if (!is_null($score->t2) && $score->t2 > 0) { $total += $score->t2; $termCount++; }
    //             if (!is_null($score->t3) && $score->t3 > 0) { $total += $score->t3; $termCount++; }

    //             $average = $termCount > 0 ? round($total / $termCount, 2) : 0;
    //             $grade = $assignGrade($average);

    //             $result[] = [
    //                 'subject_id' => $subjectId,
    //                 'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
    //                 '1st_term_total' => $score->t1,
    //                 '2nd_term_total' => $score->t2,
    //                 '3rd_term_total' => $score->t3,
    //                 'yearly_average' => $average,
    //                 'grade' => $grade,
    //                 'position' => $subjectAverages[$subjectId][$stid] ?? null
    //             ];

    //                 // Get student record from the `student` table to generate suid
    //                 $stuRecord = student::where('schid', $schid)
    //                     ->where('sid', $stid)
    //                     ->first();

    //                 $suid = null;
    //                 if ($stuRecord) {
    //                     $ssn_real = $stuRecord->year;
    //                     $term = $stuRecord->term;
    //                     $count = $stuRecord->count;
    //                     $suid = $stuRecord->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //                 }
    //                     }

    //         $response[] = [
    //             'student_id' => $suid,
    //             'student_name' => $student->lname . ' ' . $student->fname,
    //             'class' => $className ?? 'Class ID: ' . $clsm,
    //             'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
    //             'session' => $ssn,
    //             'school_id' => $schid,
    //             'subjects' => $result
    //         ];
    //     }

    //     return response()->json([
    //         'start' => $start,
    //         'count' => $count,
    //         'pld' => $response
    //     ]);
    // }




    //  public function getAllStudentCumulativeResult(Request $request)
    // {
    //     $schid = $request->input('schid');
    //     $ssn = $request->input('ssn');
    //     $clsm = $request->input('clsm');
    //     $clsa = $request->input('clsa');
    //     $start = $request->input('start', 0);
    //     $count = $request->input('count', 20);

    //     $grades = [
    //         'A' => [70, 100],
    //         'B' => [60, 69.99],
    //         'C' => [50, 59.99],
    //         'D' => [45, 49.99],
    //         'E' => [40, 44.99],
    //         'F' => [0, 39.99]
    //     ];

    //     $assignGrade = function($average) use ($grades) {
    //         foreach ($grades as $grade => [$min, $max]) {
    //             if ($average >= $min && $average <= $max) {
    //                 return $grade;
    //             }
    //         }
    //         return 'N/A';
    //     };

    //     $className = cls::where('id', $clsm)->value('name');
    //     $classArmName = sch_cls::where('id', $clsa)->value('name');

    //     $students = old_student::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['clsm', $clsm],
    //         ['clsa', $clsa]
    //     ])->offset($start)->limit($count)->get();

    //     if ($students->isEmpty()) {
    //         return response()->json(['message' => 'No students found'], 404);
    //     }

    //     $subjects = std_score::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['clsid', $clsm],
    //     ])->groupBy('sbj')->pluck('sbj')->toArray();

    //     $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

    //     $subjectAverages = [];

    //     foreach ($subjects as $subjectId) {
    //         $studentsScores = std_score::select(
    //             'stid',
    //             DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //             DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //             DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //         )
    //         ->where([
    //             ['sbj', $subjectId],
    //             ['schid', $schid],
    //             ['ssn', $ssn],
    //             ['clsid', $clsm],
    //         ])
    //         ->groupBy('stid')
    //         ->get()
    //         ->map(function ($item) {
    //             $total = 0;
    //             $count = 0;
    //             if ($item->t1 > 0) { $total += $item->t1; $count++; }
    //             if ($item->t2 > 0) { $total += $item->t2; $count++; }
    //             if ($item->t3 > 0) { $total += $item->t3; $count++; }
    //             $average = $count > 0 ? round($total / $count, 2) : 0;
    //             return ['stid' => $item->stid, 'average' => $average];
    //         })->sortByDesc('average')->values()->toArray();

    //         $ranked = [];
    //         $position = 1;
    //         $lastAvg = null;
    //         $sameRankCount = 0;

    //         foreach ($studentsScores as $index => $row) {
    //             if ($row['average'] === $lastAvg) {
    //                 $sameRankCount++;
    //             } else {
    //                 $position += $sameRankCount;
    //                 $sameRankCount = 1;
    //             }
    //             $ranked[$row['stid']] = $position;
    //             $lastAvg = $row['average'];
    //         }

    //         $subjectAverages[$subjectId] = $ranked;
    //     }

    //     $response = [];

    //     foreach ($students as $student) {
    //         $stid = $student->sid;
    //         $result = [];

    //         foreach ($subjects as $subjectId) {
    //             $score = std_score::select(
    //                 DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //                 DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //                 DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //             )
    //             ->where([
    //                 ['sbj', $subjectId],
    //                 ['schid', $schid],
    //                 ['ssn', $ssn],
    //                 ['clsid', $clsm],
    //                 ['stid', $stid]
    //             ])
    //             ->first();

    //             if (
    //                 (is_null($score->t1) || $score->t1 == 0) &&
    //                 (is_null($score->t2) || $score->t2 == 0) &&
    //                 (is_null($score->t3) || $score->t3 == 0)
    //             ) {
    //                 continue;
    //             }

    //             $total = 0;
    //             $termCount = 0;
    //             if (!is_null($score->t1) && $score->t1 > 0) { $total += $score->t1; $termCount++; }
    //             if (!is_null($score->t2) && $score->t2 > 0) { $total += $score->t2; $termCount++; }
    //             if (!is_null($score->t3) && $score->t3 > 0) { $total += $score->t3; $termCount++; }

    //             $average = $termCount > 0 ? number_format($total / $termCount, 2, '.', '') : number_format(0, 2, '.', '');
    //             $grade = $assignGrade($average);

    //             $result[] = [
    //                 'subject_id' => $subjectId,
    //                 'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
    //                 '1st_term_total' => $score->t1,
    //                 '2nd_term_total' => $score->t2,
    //                 '3rd_term_total' => $score->t3,
    //                 'yearly_average' => $average,
    //                 'grade' => $grade,
    //                 'position' => $subjectAverages[$subjectId][$stid] ?? null
    //             ];
    //         }

    //         $stuRecord = student::where('schid', $schid)->where('sid', $stid)->first();
    //         $suid = null;
    //         if ($stuRecord) {
    //             $ssn_real = $stuRecord->year;
    //             $term = $stuRecord->term;
    //             $count = $stuRecord->count;
    //             $suid = $stuRecord->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //         }

    //         // Get gender from student_basic_data
    //         $gender = student_basic_data::where('user_id', $stid)->value('sex');

    //                 // Compute final average and grade
    //         $totalAverage = 0;
    //         $subjectCount = count($result);

    //         foreach ($result as $subject) {
    //             $totalAverage += $subject['yearly_average'];
    //         }

    //         $finalAverage = $subjectCount > 0 ? number_format($totalAverage / $subjectCount, 2, '.', '') : number_format(0, 2, '.', '');
    //         $finalGrade = $assignGrade($finalAverage);

    //         // Sort by final average descending (top-performing students first)
    //         usort($response, function ($a, $b) {
    //             return $b['final_average'] <=> $a['final_average'];
    //         });


    //     $response[] = [
    //         'sid' => $stid, // ✅ Add student ID here
    //         'student_id' => $suid,
    //         'student_name' => $student->lname . ' ' . $student->fname,
    //         'gender' => $gender ?? 'Unknown',
    //         'class' => $className ?? 'Class ID: ' . $clsm,
    //         'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
    //         'session' => $ssn,
    //         'school_id' => $schid,
    //         'subjects' => $result,
    //         'final_average' => $finalAverage,
    //         'final_average_grade' => $finalGrade
    //     ];


    //     }

    //     return response()->json([
    //         'start' => $start,
    //         'count' => $count,
    //         'pld' => $response
    //     ]);
    // }



    // public function getAllStudentCumulativeResult(Request $request)
    // {
    //     $schid = $request->input('schid');
    //     $ssn = $request->input('ssn');
    //     $clsm = $request->input('clsm');
    //     $clsa = $request->input('clsa');
    //     $start = $request->input('start', 0);
    //     $count = $request->input('count', 20);

    //     // Dynamically load grades for the school/session/class
    //     $gradeScale = \App\Models\sch_grade::where([
    //         ['schid', $schid],
    //         ['clsid', $clsm],
    //         ['ssn', $ssn]
    //     ])->orderByDesc('g1')->get()->mapWithKeys(function ($item) {
    //         return [$item->grd => [(float)$item->g0, (float)$item->g1]];
    //     })->toArray();

    //     $assignGrade = function($average) use ($gradeScale) {
    //         foreach ($gradeScale as $grade => [$min, $max]) {
    //             if ($average >= $min && $average <= $max) {
    //                 return $grade;
    //             }
    //         }
    //         return 'N/A';
    //     };

    //     $className = cls::where('id', $clsm)->value('name');
    //     $classArmName = sch_cls::where('id', $clsa)->value('name');

    //     $students = old_student::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['clsm', $clsm],
    //         ['clsa', $clsa]
    //     ])->offset($start)->limit($count)->get();

    //     if ($students->isEmpty()) {
    //         return response()->json(['message' => 'No students found'], 404);
    //     }

    //     $subjects = std_score::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['clsid', $clsm],
    //     ])->groupBy('sbj')->pluck('sbj')->toArray();

    //     $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

    //     $subjectAverages = [];

    //     foreach ($subjects as $subjectId) {
    //         $studentsScores = std_score::select(
    //             'stid',
    //             DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //             DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //             DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //         )
    //         ->where([
    //             ['sbj', $subjectId],
    //             ['schid', $schid],
    //             ['ssn', $ssn],
    //             ['clsid', $clsm],
    //         ])
    //         ->groupBy('stid')
    //         ->get()
    //         ->map(function ($item) {
    //             $total = 0;
    //             $count = 0;
    //             if ($item->t1 > 0) { $total += $item->t1; $count++; }
    //             if ($item->t2 > 0) { $total += $item->t2; $count++; }
    //             if ($item->t3 > 0) { $total += $item->t3; $count++; }
    //             $average = $count > 0 ? round($total / $count, 2) : 0;
    //             return ['stid' => $item->stid, 'average' => $average];
    //         })->sortByDesc('average')->values()->toArray();

    //         $ranked = [];
    //         $position = 1;
    //         $lastAvg = null;
    //         $sameRankCount = 0;

    //         foreach ($studentsScores as $index => $row) {
    //             if ($row['average'] === $lastAvg) {
    //                 $sameRankCount++;
    //             } else {
    //                 $position += $sameRankCount;
    //                 $sameRankCount = 1;
    //             }
    //             $ranked[$row['stid']] = $position;
    //             $lastAvg = $row['average'];
    //         }

    //         $subjectAverages[$subjectId] = $ranked;
    //     }

    //     $response = [];

    //     foreach ($students as $student) {
    //         $stid = $student->sid;
    //         $result = [];

    //         foreach ($subjects as $subjectId) {
    //             $score = std_score::select(
    //                 DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //                 DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //                 DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //             )
    //             ->where([
    //                 ['sbj', $subjectId],
    //                 ['schid', $schid],
    //                 ['ssn', $ssn],
    //                 ['clsid', $clsm],
    //                 ['stid', $stid]
    //             ])
    //             ->first();

    //             if (
    //                 (is_null($score->t1) || $score->t1 == 0) &&
    //                 (is_null($score->t2) || $score->t2 == 0) &&
    //                 (is_null($score->t3) || $score->t3 == 0)
    //             ) {
    //                 continue;
    //             }

    //             $total = 0;
    //             $termCount = 0;
    //             if (!is_null($score->t1) && $score->t1 > 0) { $total += $score->t1; $termCount++; }
    //             if (!is_null($score->t2) && $score->t2 > 0) { $total += $score->t2; $termCount++; }
    //             if (!is_null($score->t3) && $score->t3 > 0) { $total += $score->t3; $termCount++; }

    //             $average = $termCount > 0 ? number_format($total / $termCount, 2, '.', '') : number_format(0, 2, '.', '');
    //             $grade = $assignGrade($average);

    //             $result[] = [
    //                 'subject_id' => $subjectId,
    //                 'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
    //                 '1st_term_total' => $score->t1,
    //                 '2nd_term_total' => $score->t2,
    //                 '3rd_term_total' => $score->t3,
    //                 'yearly_average' => $average,
    //                 'grade' => $grade,
    //                 'position' => $subjectAverages[$subjectId][$stid] ?? null
    //             ];
    //         }

    //         $stuRecord = student::where('schid', $schid)->where('sid', $stid)->first();
    //         $suid = null;
    //         if ($stuRecord) {
    //             $ssn_real = $stuRecord->year;
    //             $term = $stuRecord->term;
    //             $count = $stuRecord->count;
    //             $suid = $stuRecord->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //         }

    //         $gender = student_basic_data::where('user_id', $stid)->value('sex');

    //         $totalAverage = 0;
    //         $subjectCount = count($result);

    //         foreach ($result as $subject) {
    //             $totalAverage += $subject['yearly_average'];
    //         }

    //         $finalAverage = $subjectCount > 0 ? number_format($totalAverage / $subjectCount, 2, '.', '') : number_format(0, 2, '.', '');
    //         $finalGrade = $assignGrade($finalAverage);

    //         $response[] = [
    //             'sid' => $stid,
    //             'student_id' => $suid,
    //             'student_name' => $student->lname . ' ' . $student->fname,
    //             'gender' => $gender ?? 'Unknown',
    //             'class' => $className ?? 'Class ID: ' . $clsm,
    //             'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
    //             'session' => $ssn,
    //             'school_id' => $schid,
    //             'subjects' => $result,
    //             'final_average' => $finalAverage,
    //             'final_average_grade' => $finalGrade
    //         ];
    //     }

    //     // Final sorting by final average
    //     usort($response, function ($a, $b) {
    //         return $b['final_average'] <=> $a['final_average'];
    //     });

    //     return response()->json([
    //         'start' => $start,
    //         'count' => $count,
    //         'pld' => $response
    //     ]);
    // }


    // public function getAllStudentCumulativeResult(Request $request)
    // {
    //     $schid = $request->input('schid');
    //     $ssn = $request->input('ssn');
    //     $clsm = $request->input('clsm');
    //     $clsa = $request->input('clsa');
    //     $start = $request->input('start', 0);
    //     $count = $request->input('count', 20);

    //     // Load grades defined by school for class and session
    //     $grades = \App\Models\sch_grade::where([
    //         ['schid', $schid],
    //         ['clsid', $clsm],
    //         ['ssn', $ssn]
    //     ])->orderByDesc('g1')->get()->mapWithKeys(function ($item) {
    //         return [$item->grd => [(float) $item->g0, (float) $item->g1]];
    //     })->toArray();

    //     $assignGrade = function($average) use ($grades) {
    //         foreach ($grades as $grade => [$min, $max]) {
    //             if ($average >= $min && $average <= $max) {
    //                 return $grade;
    //             }
    //         }
    //         return 'N/A';
    //     };

    //     $className = cls::where('id', $clsm)->value('name');
    //     $classArmName = sch_cls::where('id', $clsa)->value('name');

    //     $students = old_student::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['clsm', $clsm],
    //         ['clsa', $clsa]
    //     ])->offset($start)->limit($count)->get();

    //     if ($students->isEmpty()) {
    //         return response()->json(['message' => 'No students found'], 404);
    //     }

    //     $subjects = std_score::where([
    //         ['schid', $schid],
    //         ['ssn', $ssn],
    //         ['clsid', $clsm],
    //     ])->groupBy('sbj')->pluck('sbj')->toArray();

    //     $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');

    //     $subjectAverages = [];

    //     foreach ($subjects as $subjectId) {
    //         $studentsScores = std_score::select(
    //             'stid',
    //             DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //             DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //             DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //         )
    //         ->where([
    //             ['sbj', $subjectId],
    //             ['schid', $schid],
    //             ['ssn', $ssn],
    //             ['clsid', $clsm],
    //         ])
    //         ->groupBy('stid')
    //         ->get()
    //         ->map(function ($item) {
    //             $total = 0;
    //             $count = 0;
    //             if ($item->t1 > 0) { $total += $item->t1; $count++; }
    //             if ($item->t2 > 0) { $total += $item->t2; $count++; }
    //             if ($item->t3 > 0) { $total += $item->t3; $count++; }
    //             $average = $count > 0 ? round($total / $count, 2) : 0;
    //             return ['stid' => $item->stid, 'average' => $average];
    //         })->sortByDesc('average')->values()->toArray();

    //         $ranked = [];
    //         $position = 1;
    //         $lastAvg = null;
    //         $sameRankCount = 0;

    //         foreach ($studentsScores as $index => $row) {
    //             if ($row['average'] === $lastAvg) {
    //                 $sameRankCount++;
    //             } else {
    //                 $position += $sameRankCount;
    //                 $sameRankCount = 1;
    //             }
    //             $ranked[$row['stid']] = $position;
    //             $lastAvg = $row['average'];
    //         }

    //         $subjectAverages[$subjectId] = $ranked;
    //     }

    //     $response = [];

    //     foreach ($students as $student) {
    //         $stid = $student->sid;
    //         $result = [];

    //         foreach ($subjects as $subjectId) {
    //             $score = std_score::select(
    //                 DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
    //                 DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
    //                 DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
    //             )
    //             ->where([
    //                 ['sbj', $subjectId],
    //                 ['schid', $schid],
    //                 ['ssn', $ssn],
    //                 ['clsid', $clsm],
    //                 ['stid', $stid]
    //             ])
    //             ->first();

    //             if (
    //                 (is_null($score->t1) || $score->t1 == 0) &&
    //                 (is_null($score->t2) || $score->t2 == 0) &&
    //                 (is_null($score->t3) || $score->t3 == 0)
    //             ) {
    //                 continue;
    //             }

    //             $total = 0;
    //             $termCount = 0;
    //             if (!is_null($score->t1) && $score->t1 > 0) { $total += $score->t1; $termCount++; }
    //             if (!is_null($score->t2) && $score->t2 > 0) { $total += $score->t2; $termCount++; }
    //             if (!is_null($score->t3) && $score->t3 > 0) { $total += $score->t3; $termCount++; }

    //             $average = $termCount > 0 ? number_format($total / $termCount, 2, '.', '') : number_format(0, 2, '.', '');
    //             $grade = $assignGrade($average);

    //             $result[] = [
    //                 'subject_id' => $subjectId,
    //                 'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
    //                 '1st_term_total' => $score->t1,
    //                 '2nd_term_total' => $score->t2,
    //                 '3rd_term_total' => $score->t3,
    //                 'yearly_average' => $average,
    //                 'grade' => $grade,
    //                 'position' => $subjectAverages[$subjectId][$stid] ?? null
    //             ];
    //         }

    //         $stuRecord = student::where('schid', $schid)->where('sid', $stid)->first();
    //         $suid = null;
    //         if ($stuRecord) {
    //             $ssn_real = $stuRecord->year;
    //             $term = $stuRecord->term;
    //             $count = $stuRecord->count;
    //             $suid = $stuRecord->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //         }

    //         $gender = student_basic_data::where('user_id', $stid)->value('sex');

    //         $totalAverage = 0;
    //         $subjectCount = count($result);

    //         foreach ($result as $subject) {
    //             $totalAverage += $subject['yearly_average'];
    //         }

    //         $finalAverage = $subjectCount > 0 ? number_format($totalAverage / $subjectCount, 2, '.', '') : number_format(0, 2, '.', '');
    //         $finalGrade = $assignGrade($finalAverage);

    //         $response[] = [
    //             'sid' => $stid,
    //             'student_id' => $suid,
    //             'student_name' => $student->lname . ' ' . $student->fname,
    //             'gender' => $gender ?? 'Unknown',
    //             'class' => $className ?? 'Class ID: ' . $clsm,
    //             'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
    //             'session' => $ssn,
    //             'school_id' => $schid,
    //             'subjects' => $result,
    //             'final_average' => $finalAverage,
    //             'final_average_grade' => $finalGrade
    //         ];
    //     }

    //     // Sort by final average descending
    //     usort($response, function ($a, $b) {
    //         return $b['final_average'] <=> $a['final_average'];
    //     });

    //     // Class average
    //     $totalFinal = 0;
    //     foreach ($response as $item) {
    //         $totalFinal += $item['final_average'];
    //     }
    //     $classAverage = count($response) > 0 ? number_format($totalFinal / count($response), 2, '.', '') : '0.00';

    //     // Attach class average to each student
    //     foreach ($response as &$item) {
    //         $item['class_average'] = $classAverage;
    //     }

    //     return response()->json([
    //         'start' => $start,
    //         'count' => $count,
    //         'pld' => $response
    //     ]);
    // }


    public function getAllStudentCumulativeResult(Request $request)
    {
        $schid = $request->input('schid');
        $ssn = $request->input('ssn');
        $clsm = $request->input('clsm');
        $clsa = $request->input('clsa');
        $start = $request->input('start', 0);
        $count = $request->input('count', 20);

        $gradeList = sch_grade::where([
            ['schid', $schid],
            ['clsid', $clsm],
            ['ssn', $ssn],
        ])->orderBy('g0', 'desc')->get();

        $grades = [];

        if ($gradeList->isEmpty()) {
            $grades = [
                'A' => [70, 100],
                'B' => [60, 69],
                'C' => [50, 59],
                'D' => [45, 49],
                'E' => [40, 44],
                'F' => [0, 39]
            ];
        } else {
            foreach ($gradeList as $row) {
                $grades[$row->grd] = [$row->g0, $row->g1];
            }
        }

        // $assignGrade = function ($average) use ($grades) {
        //     foreach ($grades as $grade => [$min, $max]) {
        //         if ($average >= $min && $average <= $max) {
        //             return $grade;
        //         }
        //     }
        //     return 'N/A';
        // };

        $assignGrade = function ($average) use ($grades) {
            $check = floor($average); // round down to whole number
            foreach ($grades as $grade => [$min, $max]) {
                if ($check >= $min && $check <= $max) {
                    return $grade;
                }
            }
            return 'N/A';
        };


        $className = cls::where('id', $clsm)->value('name');
        $classArmName = sch_cls::where('id', $clsa)->value('name');

        $students = old_student::where([
            ['schid', $schid],
            ['ssn', $ssn],
            ['clsm', $clsm],
            ['clsa', $clsa]
        ])->offset($start)->limit($count)->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found'], 404);
        }

        $subjects = std_score::where([
            ['schid', $schid],
            ['ssn', $ssn],
            ['clsid', $clsm],
        ])->groupBy('sbj')->pluck('sbj')->toArray();

        $subjectNames = subj::whereIn('id', $subjects)->pluck('name', 'id');
        $subjectAverages = [];

        foreach ($subjects as $subjectId) {
            $studentsScores = std_score::where([
                ['sbj', $subjectId],
                ['schid', $schid],
                ['ssn', $ssn],
                ['clsid', $clsm],
            ])->get()
                ->groupBy('stid')
                ->map(function ($scores) {
                    $grouped = $scores->unique(function ($item) {
                        return $item->trm . '-' . $item->aid;
                    });

                    $t1 = $grouped->where('trm', 1)->sum('scr');
                    $t2 = $grouped->where('trm', 2)->sum('scr');
                    $t3 = $grouped->where('trm', 3)->sum('scr');

                    $termCount = collect([$t1, $t2, $t3])->filter(fn($v) => $v > 0)->count();
                    $average = $termCount > 0 ? round(($t1 + $t2 + $t3) / $termCount, 2) : 0;

                    return ['stid' => $scores->first()->stid, 'average' => $average];
                })->sortByDesc('average')->values()->toArray();

            $ranked = [];
            $position = 1;
            $lastAvg = null;
            $sameRankCount = 0;

            foreach ($studentsScores as $index => $row) {
                if ($row['average'] === $lastAvg) {
                    $sameRankCount++;
                } else {
                    $position += $sameRankCount;
                    $sameRankCount = 1;
                }
                $ranked[$row['stid']] = $position;
                $lastAvg = $row['average'];
            }

            $subjectAverages[$subjectId] = $ranked;
        }

        $response = [];
        $classTotalAverage = 0;
        $totalStudentsWithScores = 0;

        foreach ($students as $student) {
            $stid = $student->sid;
            $result = [];

            foreach ($subjects as $subjectId) {
                $scores = std_score::where([
                    ['sbj', $subjectId],
                    ['schid', $schid],
                    ['ssn', $ssn],
                    ['clsid', $clsm],
                    ['stid', $stid]
                ])->get();

                $grouped = $scores->unique(function ($item) {
                    return $item->trm . '-' . $item->aid;
                });

                $t1 = $grouped->where('trm', 1)->sum('scr');
                $t2 = $grouped->where('trm', 2)->sum('scr');
                $t3 = $grouped->where('trm', 3)->sum('scr');

                if (($t1 + $t2 + $t3) == 0) continue;

                $termCount = collect([$t1, $t2, $t3])->filter(fn($v) => $v > 0)->count();
                $average = $termCount > 0 ? number_format(($t1 + $t2 + $t3) / $termCount, 2, '.', '') : number_format(0, 2, '.', '');
                $grade = $assignGrade($average);

                $result[] = [
                    'subject_id' => $subjectId,
                    'subject_name' => $subjectNames[$subjectId] ?? 'Unknown',
                    '1st_term_total' => $t1,
                    '2nd_term_total' => $t2,
                    '3rd_term_total' => $t3,
                    'yearly_average' => $average,
                    'grade' => $grade,
                    'position' => $subjectAverages[$subjectId][$stid] ?? null
                ];
            }

            $stuRecord = student::where('schid', $schid)->where('sid', $stid)->first();
            $suid = null;
            if ($stuRecord) {
                $ssn_real = $stuRecord->year;
                $term = $stuRecord->term;
                $count = $stuRecord->count;
                $suid = $stuRecord->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
            }

            $gender = student_basic_data::where('user_id', $stid)->value('sex');
            $totalAverage = 0;
            $subjectCount = count($result);

            foreach ($result as $subject) {
                $totalAverage += $subject['yearly_average'];
            }

            $finalAverage = $subjectCount > 0 ? number_format($totalAverage / $subjectCount, 2, '.', '') : number_format(0, 2, '.', '');
            $finalGrade = $assignGrade($finalAverage);

            if ($subjectCount > 0) {
                $classTotalAverage += $finalAverage;
                $totalStudentsWithScores++;
            }

            $response[] = [
                'sid' => $stid,
                'student_id' => $suid,
                'student_name' => $student->lname . ' ' . $student->fname,
                'gender' => $gender ?? 'Unknown',
                'class' => $className ?? 'Class ID: ' . $clsm,
                'class_arm' => $classArmName ?? 'Class Arm ID: ' . $clsa,
                'session' => $ssn,
                'school_id' => $schid,
                'subjects' => $result,
                'final_average' => $finalAverage,
                'final_average_grade' => $finalGrade
            ];
        }

        usort($response, fn($a, $b) => $b['final_average'] <=> $a['final_average']);

        $classAverage = $totalStudentsWithScores > 0
            ? number_format($classTotalAverage / $totalStudentsWithScores, 2, '.', '')
            : '0.00';

        // ✅ Inject class average into each student's data
        foreach ($response as &$student) {
            $student['class_average'] = $classAverage;
        }

        return response()->json([
            'start' => $start,
            'count' => $count,
            'pld' => $response
        ]);
    }





    /**
     * @OA\Get(
     *     path="/api/getYearlyAssessmentAverage",
     *     summary="Get yearly psychomotor average and term scores for a student",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         description="School ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="query",
     *         description="Student ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="query",
     *         description="Academic Session",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with student psychomotor data",
     *         @OA\JsonContent(
     *             @OA\Property(property="student_name", type="string", example="John Doe"),
     *             @OA\Property(property="session", type="string", example="2023/2024"),
     *             @OA\Property(property="class", type="string", example="Primary 4"),
     *             @OA\Property(property="class_arm", type="string", example="A"),
     *             @OA\Property(
     *                 property="psychomotor_term_scores",
     *                 type="object",
     *                 @OA\Property(
     *                     property="term_1",
     *                     type="object",
     *                     @OA\Property(property="punc", type="number", example=4),
     *                     @OA\Property(property="hon", type="number", example=3)
     *                 ),
     *                 @OA\Property(
     *                     property="term_2",
     *                     type="object",
     *                     @OA\Property(property="punc", type="number", example=4),
     *                     @OA\Property(property="hon", type="number", example=3)
     *                 ),
     *                 @OA\Property(
     *                     property="term_3",
     *                     type="object",
     *                     @OA\Property(property="punc", type="number", example=4),
     *                     @OA\Property(property="hon", type="number", example=3)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="psychomotor_averages",
     *                 type="object",
     *                 @OA\Property(property="punc", type="number", example=3.67),
     *                 @OA\Property(property="hon", type="number", example=4.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No records found for this student",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No records found for this student.")
     *         )
     *     )
     * )
     */


    // public function getYearlyAssessmentAverage(Request $request)
    // {
    //     $validated = $request->validate([
    //         'schid' => 'required',
    //         'stid'  => 'required',
    //         'ssn'   => 'required',
    //         'clsm'  => 'required', // class main id (from cls)
    //         'clsa'  => 'required'  // class arm id (from sch_cls)
    //     ]);

    //     $psyRecords = student_psy::where('schid', $validated['schid'])
    //         ->where('stid', $validated['stid'])
    //         ->where('ssn', $validated['ssn'])
    //         ->where('clsm', $validated['clsm'])
    //         ->where('clsa', $validated['clsa'])
    //         ->get();

    //     if ($psyRecords->isEmpty()) {
    //         return response()->json(['message' => 'No records found for this student.'], 404);
    //     }

    //     $fields = ['punc', 'hon', 'pol', 'neat', 'pers', 'rel', 'dil', 'cre', 'pat', 'verb', 'gam', 'musc', 'drw', 'wrt'];
    //     $totals = array_fill_keys($fields, 0);
    //     $counts = array_fill_keys($fields, 0);
    //     $termWise = [];

    //     foreach ([1, 2, 3] as $term) {
    //         $termRecord = $psyRecords->firstWhere('trm', $term);
    //         if ($termRecord) {
    //             $termWise["term_$term"] = [];
    //             foreach ($fields as $field) {
    //                 $value = $termRecord->$field ?? null;
    //                 $termWise["term_$term"][$field] = $value;

    //                 if (!is_null($value)) {
    //                     $totals[$field] += $value;
    //                     $counts[$field]++;
    //                 }
    //             }
    //         }
    //     }

    //     // Round to nearest whole number
    //     $averages = [];
    //     foreach ($fields as $field) {
    //         $averages[$field] = $counts[$field] > 0
    //             ? round($totals[$field] / $counts[$field])
    //             : null;
    //     }

    //     $student = student::where('sid', $validated['stid'])
    //         ->where('schid', $validated['schid'])
    //         ->first();

    //     // Generate SUID
    //     $suid = null;
    //     if ($student) {
    //         $ssn_real = $student->year;
    //         $term = $student->term;
    //         $count = $student->count;
    //         $suid = $student->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count);
    //     }

    //     // Get class name from cls model
    //     $className = cls::where('id', $validated['clsm'])->value('name');

    //     // Get class arm name from sch_cls model
    //     $classArmName = sch_cls::where('schid', $validated['schid'])
    //         ->where('cls_id', $validated['clsa'])
    //         ->value('name');

    //     return response()->json([
    //         'student_id' => $suid,
    //         'student_name' => $student ? $student->fname . ' ' . $student->lname : null,
    //         'session' => $validated['ssn'],
    //         'class' => $className,
    //         'class_arm' => $classArmName,
    //         'psychomotor_term_scores' => $termWise,
    //         'psychomotor_averages' => $averages,
    //     ]);
    // }

    public function getYearlyAssessmentAverage(Request $request)
    {
        $validated = $request->validate([
            'schid' => 'required',
            'stid'  => 'required',
            'ssn'   => 'required'
        ]);

        // Get student from old_student for class info
        $oldStudent = old_student::where('sid', $validated['stid'])
            ->where('schid', $validated['schid'])
            ->where('ssn', $validated['ssn'])
            ->first();

        if (!$oldStudent || !$oldStudent->clsm || !$oldStudent->clsa) {
            return response()->json(['message' => 'Class or class arm information not found for student.'], 404);
        }

        $clsm = $oldStudent->clsm;
        $clsa = $oldStudent->clsa;

        // Fetch psychomotor records
        $psyRecords = student_psy::where('schid', $validated['schid'])
            ->where('stid', $validated['stid'])
            ->where('ssn', $validated['ssn'])
            ->where('clsm', $clsm)
            ->where('clsa', $clsa)
            ->get();

        if ($psyRecords->isEmpty()) {
            return response()->json(['message' => 'No psychomotor records found for this student.'], 404);
        }

        $fields = ['punc', 'hon', 'pol', 'neat', 'pers', 'rel', 'dil', 'cre', 'pat', 'verb', 'gam', 'musc', 'drw', 'wrt'];
        $totals = array_fill_keys($fields, 0);
        $counts = array_fill_keys($fields, 0);
        $termWise = [];

        foreach ([1, 2, 3] as $term) {
            $termRecord = $psyRecords->firstWhere('trm', $term);
            if ($termRecord) {
                $termWise["term_$term"] = [];
                foreach ($fields as $field) {
                    $value = $termRecord->$field ?? null;
                    $termWise["term_$term"][$field] = $value;

                    if (!is_null($value)) {
                        $totals[$field] += $value;
                        $counts[$field]++;
                    }
                }
            }
        }

        $averages = [];
        foreach ($fields as $field) {
            $averages[$field] = $counts[$field] > 0
                ? round($totals[$field] / $counts[$field])
                : null;
        }

        // Get extra info from student table (for SUID and name)
        $student = student::where('sid', $validated['stid'])
            ->where('schid', $validated['schid'])
            ->first();

        $suid = $student
            ? $student->sch3 . '/' . $student->year . '/' . $student->term . '/' . strval($student->count)
            : ($oldStudent->suid ?? null);

        $studentName = $student
            ? trim($student->fname . ' ' . ($student->mname ?? '') . ' ' . $student->lname)
            : trim($oldStudent->fname . ' ' . ($oldStudent->mname ?? '') . ' ' . $oldStudent->lname);

        // Get class and arm names
        $className = \App\Models\cls::where('id', $clsm)->value('name') ?? 'Unknown';
        $classArmName = \App\Models\sch_cls::where('id', $clsa)->value('name') ?? 'Unknown';

        return response()->json([
            'student_id' => $suid,
            'student_name' => $studentName,
            'session' => $validated['ssn'],
            'class' => $className,
            'class_arm' => $classArmName,
            'psychomotor_term_scores' => $termWise,
            'psychomotor_averages' => $averages,
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getAllStudentsYearlyAssessmentAverages",
     *     summary="Get all students' yearly psychomotor assessment averages and term scores",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         description="School ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="query",
     *         description="Session (e.g. 2024)",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="query",
     *         description="Class main ID (from cls table)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="query",
     *         description="Class arm ID (from sch_cls table)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         description="Offset for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         description="Number of students to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with psychomotor scores",
     *         @OA\JsonContent(
     *             @OA\Property(property="class", type="string", example="Primary 5"),
     *             @OA\Property(property="class_arm", type="string", example="Arm A"),
     *             @OA\Property(property="session", type="string", example="2024"),
     *             @OA\Property(property="total_students_returned", type="integer", example=3),
     *             @OA\Property(
     *                 property="students",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="student_name", type="string", example="Jane Doe"),
     *                     @OA\Property(property="student_id", type="string", example="ST12345"),
     *                     @OA\Property(
     *                         property="psychomotor_term_scores",
     *                         type="object",
     *                         @OA\Property(
     *                             property="term_1",
     *                             type="object",
     *                             @OA\Property(property="punc", type="integer", example=4),
     *                             @OA\Property(property="hon", type="integer", example=3),
     *                             @OA\Property(property="pol", type="integer", example=5),
     *                             @OA\Property(property="neat", type="integer", example=4),
     *                             @OA\Property(property="pers", type="integer", example=3),
     *                             @OA\Property(property="rel", type="integer", example=5),
     *                             @OA\Property(property="dil", type="integer", example=4),
     *                             @OA\Property(property="cre", type="integer", example=4),
     *                             @OA\Property(property="pat", type="integer", example=3),
     *                             @OA\Property(property="verb", type="integer", example=5),
     *                             @OA\Property(property="gam", type="integer", example=4),
     *                             @OA\Property(property="musc", type="integer", example=3),
     *                             @OA\Property(property="drw", type="integer", example=2),
     *                             @OA\Property(property="wrt", type="integer", example=4)
     *                         ),
     *                         @OA\Property(property="term_2", ref="#/components/schemas/PsychomotorTermScores"),
     *                         @OA\Property(property="term_3", ref="#/components/schemas/PsychomotorTermScores")
     *                     ),
     *                     @OA\Property(
     *                         property="psychomotor_averages",
     *                         type="object",
     *                         @OA\Property(property="punc", type="integer", example=4),
     *                         @OA\Property(property="hon", type="integer", example=3),
     *                         @OA\Property(property="pol", type="integer", example=5),
     *                         @OA\Property(property="neat", type="integer", example=4),
     *                         @OA\Property(property="pers", type="integer", example=3),
     *                         @OA\Property(property="rel", type="integer", example=5),
     *                         @OA\Property(property="dil", type="integer", example=4),
     *                         @OA\Property(property="cre", type="integer", example=4),
     *                         @OA\Property(property="pat", type="integer", example=3),
     *                         @OA\Property(property="verb", type="integer", example=5),
     *                         @OA\Property(property="gam", type="integer", example=4),
     *                         @OA\Property(property="musc", type="integer", example=3),
     *                         @OA\Property(property="drw", type="integer", example=2),
     *                         @OA\Property(property="wrt", type="integer", example=4)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No students found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No students found.")
     *         )
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="PsychomotorTermScores",
     *     type="object",
     *     @OA\Property(property="punc", type="integer", example=4),
     *     @OA\Property(property="hon", type="integer", example=3),
     *     @OA\Property(property="pol", type="integer", example=5),
     *     @OA\Property(property="neat", type="integer", example=4),
     *     @OA\Property(property="pers", type="integer", example=3),
     *     @OA\Property(property="rel", type="integer", example=5),
     *     @OA\Property(property="dil", type="integer", example=4),
     *     @OA\Property(property="cre", type="integer", example=4),
     *     @OA\Property(property="pat", type="integer", example=3),
     *     @OA\Property(property="verb", type="integer", example=5),
     *     @OA\Property(property="gam", type="integer", example=4),
     *     @OA\Property(property="musc", type="integer", example=3),
     *     @OA\Property(property="drw", type="integer", example=2),
     *     @OA\Property(property="wrt", type="integer", example=4)
     * )
     */

    public function getAllStudentsYearlyAssessmentAverages(Request $request)
    {
        $validated = $request->validate([
            'schid' => 'required',
            'ssn'   => 'required',
            'clsm'  => 'required', // class main id (from cls)
            'clsa'  => 'required'  // class arm id (from sch_cls)
        ]);

        $start = $request->input('start', 0);
        $count = $request->input('count', 20);

        // Get student IDs from old_student based on class main and class arm
        $studentIds = old_student::where('schid', $validated['schid'])
            ->where('clsm', $validated['clsm'])
            ->where('clsa', $validated['clsa'])
            ->pluck('sid');

        // Fetch students from student table based on those IDs and school id
        $students = student::where('schid', $validated['schid'])
            ->whereIn('sid', $studentIds)
            ->skip($start)
            ->take($count)
            ->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found.'], 404);
        }

        $fields = ['punc', 'hon', 'pol', 'neat', 'pers', 'rel', 'dil', 'cre', 'pat', 'verb', 'gam', 'musc', 'drw', 'wrt'];
        $results = [];

        foreach ($students as $student) {
            $psyRecords = student_psy::where('schid', $validated['schid'])
                ->where('stid', $student->sid)
                ->where('ssn', $validated['ssn'])
                ->where('clsm', $validated['clsm'])
                ->where('clsa', $validated['clsa'])
                ->get();

            if ($psyRecords->isEmpty()) {
                continue;
            }

            $totals = array_fill_keys($fields, 0);
            $counts = array_fill_keys($fields, 0);
            $termScores = [];

            foreach ([1, 2, 3] as $term) {
                $termRecord = $psyRecords->firstWhere('trm', $term);
                $termKey = 'term_' . $term;

                $termData = [];
                foreach ($fields as $field) {
                    $value = $termRecord ? $termRecord->$field : null;
                    $termData[$field] = $value;

                    if (!is_null($value)) {
                        $totals[$field] += $value;
                        $counts[$field]++;
                    }
                }

                $termScores[$termKey] = $termData;
            }

            $averages = [];
            foreach ($fields as $field) {
                $averages[$field] = $counts[$field] > 0
                    ? round($totals[$field] / $counts[$field])
                    : null;
            }

            // Generate suid
            $suid = null;
            if ($student) {
                $ssn_real = $student->year;
                $term = $student->term;
                $count_val = $student->count;
                $suid = $student->sch3 . '/' . $ssn_real . '/' . $term . '/' . strval($count_val);
            }

            $results[] = [
                'student_name' => $student->fname . ' ' . $student->lname,
                'student_id' => $suid,
                'psychomotor_term_scores' => $termScores,
                'psychomotor_averages' => $averages,
            ];
        }

        $className = cls::where('id', $validated['clsm'])->value('name');
        $classArmName = sch_cls::where('schid', $validated['schid'])
            ->where('cls_id', $validated['clsa'])
            ->value('name');

        return response()->json([
            'class' => $className,
            'class_arm' => $classArmName,
            'session' => $validated['ssn'],
            'total_students_returned' => count($results),
            'students' => $results
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/getEveryStudentCumulativeResult",
     *     tags={"Api"},
     *     summary="Get Every Student's Cumulative Result",
     *     description="Returns 1st term, 2nd term, 3rd term and yearly average (excluding totals) for all students",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="no", type="string", example="1"),
     *                     @OA\Property(property="name", type="string", example="First Term"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */



    public function getEveryStudentCumulativeResult()
    {
        // Sum all scores by term (no grouping)
        $totals = std_score::select(
            DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
            DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
            DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
        )->first();

        $t1 = $totals->t1 ?? 0;
        $t2 = $totals->t2 ?? 0;
        $t3 = $totals->t3 ?? 0;

        $yearly_average = round(($t1 + $t2 + $t3) / 3, 2);

        $now = now()->toIso8601String();

        $pld = [
            [
                'no' => '1',
                'name' => 'First Term',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'no' => '2',
                'name' => 'Second Term',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'no' => '3',
                'name' => 'Third Term',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'no' => '4',
                'name' => 'Cummulative',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'pld' => $pld
        ]);
    }


    ////////////////////////////

    /**
     * @OA\Get(
     *     path="/api/getLoggedInUserDetails",
     *     summary="Get logged-in user details",
     *     description="Returns the authenticated user and their staff or old staff profile with role names.",
     *     operationId="getLoggedInUserDetails",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User and profile fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1927),
     *                 @OA\Property(property="email", type="string", example="stellajonah@gmail.com"),
     *                 @OA\Property(property="typ", type="string", example="w")
     *             ),
     *             @OA\Property(property="profile", type="object",
     *                 @OA\Property(property="sid", type="string", example="1927"),
     *                 @OA\Property(property="schid", type="string", example="13"),
     *                 @OA\Property(property="fname", type="string", example="Mrs."),
     *                 @OA\Property(property="lname", type="string", example="Stella"),
     *                 @OA\Property(property="role", type="string", example="*21"),
     *                 @OA\Property(property="role2", type="string", example="-1"),
     *                 @OA\Property(property="role_name", type="string", example="Vice Principal"),
     *                 @OA\Property(property="role2_name", type="string", example="Head Teacher")
     *             )
     *         )
     *     )
     * )
     */
    public function getLoggedInUserDetails()
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $staff = $user->staff()->first();

        if (!$staff) {
            $staff = $user->oldStaff()->first();
        }

        if ($staff) {
            $role = ltrim($staff->role, '*-');
            $role2 = ltrim($staff->role2, '*-');

            $roleName = sch_staff_role::where('role', $role)
                ->where('schid', $staff->schid)
                ->value('name');

            $role2Name = sch_staff_role::where('role', $role2)
                ->where('schid', $staff->schid)
                ->value('name');

            $profile = [
                ...$staff->toArray(),
                'role_name' => $roleName,
                'role2_name' => $role2Name,
            ];
        } else {
            $profile = null;
        }

        return response()->json([
            'user' => $user,
            'profile' => $profile,
        ]);
    }


    ////////////////////////////////////////////////////////////////////

    /**
     * @OA\Get(
     *     path="/api/getCummulativeBroadsheet/{schid}/{ssn}/{clsm}/{clsa}",
     *     summary="Get cumulative broadsheet for a given school, session, class, and arm",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Class arm identifier or -1 if not applicable",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of cumulative broadsheet",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="name", type="string", example="DOE JOHN"),
     *                     @OA\Property(property="learner_id", type="string", example="STU123"),
     *                     @OA\Property(property="class_name", type="string", example="Primary 5"),
     *                     @OA\Property(property="class_arm_name", type="string", example="Red"),
     *                     @OA\Property(property="clsm", type="integer", example=5),
     *                     @OA\Property(property="clsa", type="string", example="Red"),
     *                     @OA\Property(property="final_average", type="number", format="float", example=85.6),
     *                     @OA\Property(property="overall_position", type="integer", example=1),
     *                     @OA\Property(property="no_of_subjects", type="integer", example=5),
     *                     @OA\Property(
     *                         property="subjects",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="subject_id", type="integer", example=1),
     *                             @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                             @OA\Property(property="average", type="number", format="float", example=88.0),
     *                             @OA\Property(property="position", type="integer", example=1),
     *                             @OA\Property(property="ca", type="integer", example=25)
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="psychomotor",
     *                         type="object",
     *                         @OA\Property(property="punc", type="string", example="Excellent"),
     *                         @OA\Property(property="hon", type="string", example="Good"),
     *                         @OA\Property(property="pol", type="string", example="Fair"),
     *                         @OA\Property(property="neat", type="string", example="Very Good"),
     *                         @OA\Property(property="pers", type="string", example="Good"),
     *                         @OA\Property(property="rel", type="string", example="Excellent"),
     *                         @OA\Property(property="dil", type="string", example="Good"),
     *                         @OA\Property(property="cre", type="string", example="Fair"),
     *                         @OA\Property(property="pat", type="string", example="Good"),
     *                         @OA\Property(property="verb", type="string", example="Very Good"),
     *                         @OA\Property(property="gam", type="string", example="Fair"),
     *                         @OA\Property(property="musc", type="string", example="Average"),
     *                         @OA\Property(property="drw", type="string", example="Poor"),
     *                         @OA\Property(property="wrt", type="string", example="Good")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No records found"
     *     )
     * )
     */



    public function getCummulativeBroadsheet($schid, $ssn, $clsm, $clsa)
    {
        $students = old_student::where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('clsm', $clsm)
            ->when($clsa != '-1', fn($q) => $q->where('clsa', $clsa))
            ->get();

        $allClassStudentIds = old_student::where('schid', $schid)
            ->where('ssn', $ssn)
            ->where('clsm', $clsm)
            ->pluck('sid');

        // $allClassStudentIds = old_student::where('schid', $schid)
        // ->where('ssn', $ssn)
        // ->where('clsm', $clsm)
        // ->when($clsa != '-1', fn($q) => $q->where('clsa', $clsa))
        // ->pluck('sid');


        $className = cls::where('id', $clsm)->value('name') ?? "CLS-$clsm";

        $armName = $clsa !== '-1'
            ? sch_cls::where('cls_id', $clsm)
            ->where('schid', $schid)
            ->where('id', $clsa)
            ->value('name')
            : null;

        $subjects = class_subj::where('schid', $schid)
            ->where('clsid', $clsm)
            ->pluck('subj_id')
            ->toArray();

        $subjectPositions = [];
        $subjectAverages = [];

        foreach ($subjects as $sbj) {
            $subjectScores = std_score::select(
                'stid',
                DB::raw("SUM(CASE WHEN trm = 1 THEN scr ELSE 0 END) as t1"),
                DB::raw("SUM(CASE WHEN trm = 2 THEN scr ELSE 0 END) as t2"),
                DB::raw("SUM(CASE WHEN trm = 3 THEN scr ELSE 0 END) as t3")
            )
                ->where('schid', $schid)
                ->where('ssn', $ssn)
                ->where('clsid', $clsm)
                ->where('sbj', $sbj)
                ->whereIn('stid', $allClassStudentIds)
                ->groupBy('stid')
                ->get()
                ->map(function ($item) {
                    $total = 0;
                    $count = 0;
                    if ($item->t1 > 0) {
                        $total += $item->t1;
                        $count++;
                    }
                    if ($item->t2 > 0) {
                        $total += $item->t2;
                        $count++;
                    }
                    if ($item->t3 > 0) {
                        $total += $item->t3;
                        $count++;
                    }
                    $avg = $count > 0 ? round($total / $count, 2) : 0;
                    return ['stid' => $item->stid, 'average' => $avg];
                })
                ->sortByDesc('average')
                ->values();

            $rank = [];
            $position = 1;
            $lastAvg = null;
            $sameRankCount = 0;

            foreach ($subjectScores as $row) {
                if ($row['average'] === $lastAvg) {
                    $sameRankCount++;
                } else {
                    $position += $sameRankCount;
                    $sameRankCount = 1;
                }
                $rank[$row['stid']] = $position;
                $lastAvg = $row['average'];
            }

            foreach ($subjectScores as $row) {
                $subjectAverages[$row['stid']][$sbj] = $row['average'];
                $subjectPositions[$row['stid']][$sbj] = $rank[$row['stid']] ?? null;
            }
        }

        $finalAverages = [];
        foreach ($students as $std) {
            $stid = $std->sid;
            $subjectsTaken = student_subj::where('stid', $stid)->pluck('sbj')->toArray();

            $total = 0;
            $count = 0;
            foreach ($subjectsTaken as $sbj) {
                $avg = $subjectAverages[$stid][$sbj] ?? 0;
                if ($avg > 0) {
                    $total += $avg;
                    $count++;
                }
            }

            $finalAverages[$stid] = $count > 0 ? round($total / $count, 2) : 0;
        }

        $overallSorted = collect($finalAverages)->sortDesc();
        $overallPosition = [];
        $rank = 1;
        $last = null;
        $tieCount = 0;

        foreach ($overallSorted as $stid => $avg) {
            if ($avg === $last) {
                $tieCount++;
            } else {
                $rank += $tieCount;
                $tieCount = 1;
            }
            $overallPosition[$stid] = $rank;
            $last = $avg;
        }

        $final = [];

        foreach ($students as $std) {
            $stid = $std->sid;
            $name = strtoupper(trim($std->lname . ' ' . $std->fname));
            $subjectsTaken = student_subj::where('stid', $stid)->pluck('sbj')->toArray();

            $subjectsInfo = [];
            $total = 0;
            $count = 0;

            foreach ($subjectsTaken as $sbj) {
                $avg = $subjectAverages[$stid][$sbj] ?? 0;
                $pos = $subjectPositions[$stid][$sbj] ?? null;
                $total += $avg;
                $count++;

                $subjectsInfo[] = [
                    'subject_id' => $sbj,
                    'subject_name' => subj::find($sbj)?->name ?? 'Subject',
                    'yearly_average' => number_format($avg, 2),
                    'subject_position' => $pos,
                ];
            }

            $psy = student_psy::where('stid', $stid)
                ->where('schid', $schid)
                ->where('ssn', $ssn)
                ->where('trm', 2)
                ->where('clsm', $clsm)
                ->when($clsa !== '-1', fn($q) => $q->where('clsa', (int)$clsa))
                ->first();

            $psychomotor = $psy ? [
                'punc' => $psy->punc,
                'hon'  => $psy->hon,
                'pol'  => $psy->pol,
                'neat' => $psy->neat,
                'pers' => $psy->pers,
                'rel'  => $psy->rel,
                'dil'  => $psy->dil,
                'cre'  => $psy->cre,
                'pat'  => $psy->pat,
                'verb' => $psy->verb,
                'gam'  => $psy->gam,
                'musc' => $psy->musc,
                'drw'  => $psy->drw,
                'wrt'  => $psy->wrt,
            ] : [];

            $finalAvg = $finalAverages[$stid] ?? 0;
            $final[] = [
                'uid' => $std->uid, // ✅ Now includes the UID
                'sid' => $stid,
                'name' => $name,
                'learner_id' => $std->suid ?? $std->sid,
                'class_name' => $className,
                'class_arm_name' => $armName ?? 'N/A',
                'clsm' => $clsm,
                'clsa' => $clsa,
                'final_average' => number_format($finalAvg, 2),
                'overall_position' => $overallPosition[$stid] ?? null,
                'no_of_subjects' => $count,
                'subjects' => $subjectsInfo,
                'psychomotor' => $psychomotor
            ];
        }

        $final = collect($final)->sortBy('overall_position')->values()->all();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'pld' => $final
        ]);
    }







    /**
     * @OA\Get(
     *     path="/api/getComment/{stid}/{schid}/{clsm}/{sesn}",
     *     summary="Get all student comments for a given class, arm, session, and term",
     *     description="Returns all comments for a student based on stid, schid, class, class arm, session, and term",
     *     operationId="getStudentComments",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *         @OA\Schema(type="string", example="ST001")
     *     ),
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="SCH123")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="sesn",
     *         in="path",
     *         required=true,
     *         description="Session (academic year)",
     *         @OA\Schema(type="string", example="2023")
     *     ),

     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="com", type="string", example="Excellent progress."),
     *                 @OA\Property(property="schid", type="string", example="12"),
     *                 @OA\Property(property="class_id", type="string", example="1"),
     *                 @OA\Property(property="class_name", type="string", example="JSS 2"),
     *                 @OA\Property(property="class_arm_name", type="string", example="Blue")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No records found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No records found")
     *         )
     *     )
     * )
     */


    public function getComment($stid, $schid, $clsm, $sesn)
    {
        // Get all matching result records, including sid
        $records = cummulative_comment::where('sid', $stid)
            ->where('schid', $schid)
            ->where('clsm', $clsm)
            ->where('ssn', $sesn)
            ->select('sid', 'comm', 'clsm', 'schid')
            ->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No records found'], 404);
        }

        // Get class name only
        $className = cls::where('id', $clsm)->value('name');

        // Append extra info to each record
        $results = $records->map(function ($record) use ($className) {
            return [
                'sid' => $record->sid,
                'com' => $record->comm,
                'schid' => $record->schid,
                'class_id' => $record->clsm,
                'class_name' => $className,
            ];
        });

        return response()->json($results);
    }





    /**
     * @OA\Get(
     *     path="/api/getAllComment/{schid}/{clsm}/{clsa}/{ssn}",
     *     summary="Get all comments for a student in a given session and term",
     *     operationId="getAllStudentComments",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="string", example="SCH123")
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="path",
     *         required=true,
     *         description="Class Arm ID",
     *         @OA\Schema(type="string", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="path",
     *         required=true,
     *         description="Session",
     *         @OA\Schema(type="string", example="2023")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="com", type="string", example="Good performance"),
     *                 @OA\Property(property="schid", type="string", example="SCH123"),
     *                 @OA\Property(property="class_id", type="string", example="1"),
     *                 @OA\Property(property="class_name", type="string", example="JSS 2"),
     *                 @OA\Property(property="class_arm_id", type="string", example="2"),
     *                 @OA\Property(property="class_arm_name", type="string", example="Blue")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No records found")
     *         )
     *     )
     * )
     */

    public function getAllComment($schid, $clsm, $clsa, $ssn)
    {
        // Get all matching result records, now including sid
        $records = cummulative_comment::where('schid', $schid)
            ->where('clsm', $clsm)
            ->where('clsa', $clsa)
            ->where('ssn', $ssn)
            ->select('sid', 'comm', 'clsm', 'clsa', 'schid')
            ->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No records found'], 404);
        }

        // Get class name once
        $className = cls::where('id', $clsm)->value('name');

        // Get class arm name once
        $classArmName = sch_cls::where('id', $clsa)
            ->where('schid', $schid)
            ->value('name');

        // Add class name and arm name to each result
        $results = $records->map(function ($record) use ($className, $classArmName) {
            return [
                'sid' => $record->sid,
                'com' => $record->comm,
                'schid' => $record->schid,
                'class_id' => $record->clsm,
                'class_name' => $className,
                'class_arm_id' => $record->clsa,
                'class_arm_name' => $classArmName,
            ];
        });

        return response()->json($results);
    }



    /**
     * @OA\Post(
     *     path="/api/storeComment",
     *     summary="Create or update a cumulative comment",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     operationId="storeComment",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"uid", "sid", "schid", "clsm", "clsa", "ssn", "comm"},
     *             @OA\Property(property="uid", type="string", example="UID12345"),
     *             @OA\Property(property="sid", type="string", example="565"),
     *             @OA\Property(property="schid", type="string", example="12"),
     *             @OA\Property(property="clsm", type="string", example="11"),
     *             @OA\Property(property="clsa", type="string", example="1"),
     *             @OA\Property(property="ssn", type="string", example="2025"),
     *             @OA\Property(property="comm", type="string", example="Excellent performance this term."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cumulative comment saved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cumulative comment saved successfully."),
     *             @OA\Property(
     *                 property="pld",
     *                 type="object",
     *                 @OA\Property(property="uid", type="string", example="UID12345"),
     *                 @OA\Property(property="sid", type="string", example="565"),
     *                 @OA\Property(property="schid", type="string", example="12"),
     *                 @OA\Property(property="clsm", type="string", example="11"),
     *                 @OA\Property(property="clsa", type="string", example="1"),
     *                 @OA\Property(property="ssn", type="string", example="2025"),
     *                 @OA\Property(property="comm", type="string", example="Excellent performance this term."),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */



    public function storeComment(Request $request)
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'sid' => 'required|string',
            'schid' => 'required|string',
            'clsm' => 'required|string',
            'clsa' => 'required|string',
            'ssn' => 'required|string',
            'comm' => 'required|string',
        ]);

        $comment = cummulative_comment::updateOrCreate(
            ['uid' => $validated['uid']], // Match by primary key
            [
                'sid' => $validated['sid'],
                'schid' => $validated['schid'],
                'clsm' => $validated['clsm'],
                'clsa' => $validated['clsa'],
                'ssn' => $validated['ssn'],
                'comm' => $validated['comm'],
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Cumulative comment saved successfully.',
            'pld' => $comment
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/getStudentScoreSummary",
     *     summary="Get a summary of student scores and class positions for a term",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="query",
     *         required=true,
     *         description="School ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="ssn",
     *         in="query",
     *         required=true,
     *         description="Session (e.g., 2024)",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Parameter(
     *         name="trm",
     *         in="query",
     *         required=true,
     *         description="Term number (1, 2, or 3)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="clsm",
     *         in="query",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="integer", example=11)
     *     ),
     *     @OA\Parameter(
     *         name="clsa",
     *         in="query",
     *         required=true,
     *         description="Class arm ID or -1 to include all arms",
     *         @OA\Schema(type="string", example="-1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student score summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student Score Summary"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="uid", type="string", example="a1b2c3d4"),
     *                     @OA\Property(property="sid", type="integer", example=563),
     *                     @OA\Property(property="learner_id", type="string", example="AB78999"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="avg_score", type="number", format="float", example=58.75),
     *                     @OA\Property(property="class", type="integer", example=11),
     *                     @OA\Property(property="no_of_subjects", type="integer", example=5),
     *                     @OA\Property(
     *                         property="scores",
     *                         type="object",
     *                         description="Each subject name maps to an array of scores",
     *                         @OA\AdditionalProperties(
     *                             @OA\Schema(
     *                                 type="array",
     *                                 @OA\Items(type="integer", example=45)
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(property="clsid", type="integer", example=11),
     *                     @OA\Property(property="clsa", type="string", example="2"),
     *                     @OA\Property(property="ssn", type="integer", example=2024),
     *                     @OA\Property(property="trm", type="integer", example=1),
     *                     @OA\Property(property="position", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request due to invalid or missing parameters"
     *     )
     * )
     */

    public function getStudentScoreSummary(Request $request)
    {
        $schid = $request->schid;
        $ssn = $request->ssn;
        $trm = $request->trm;
        $clsm = $request->clsm;
        $clsa = $request->clsa;

        // Get class name and class arm name
        $className = cls::where('id', $clsm)->value('name') ?? "CLS-$clsm";

        $classArmName = $clsa !== '-1'
            ? sch_cls::where('schid', $schid)->where('cls_id', $clsm)->where('id', $clsa)->value('name') ?? 'N/A'
            : 'All Arms';

        // Get all active students
        $students = old_student::where("schid", $schid)
            ->where("status", "active")
            ->where("ssn", $ssn)
            ->where("clsm", $clsm)
            ->when($clsa !== '-1', fn($q) => $q->where("clsa", $clsa))
            ->get();

        $scoreSheet = [];

        foreach ($students as $student) {
            $stid = $student->sid;

            // Get the student's subjects
            $subjects = student_subj::where("stid", $stid)->pluck("sbj")->toArray();

            $grouped = [];
            $totalScore = 0;
            $subjectCount = 0;

            foreach ($subjects as $sbjId) {
                // Get all scores with assessment info
                $scores = std_score::where("stid", $stid)
                    ->where("sbj", $sbjId)
                    ->where("schid", $schid)
                    ->where("ssn", $ssn)
                    ->where("trm", $trm)
                    ->where("clsid", $clsm)
                    ->get();

                if ($scores->isNotEmpty()) {
                    $grouped[$sbjId] = [];

                    foreach ($scores as $score) {
                        $markName = sch_mark::where('id', $score->aid)
                            ->where('schid', $schid)
                            ->where('clsid', $clsm)
                            ->where('ssn', $ssn)
                            ->where('trm', $trm)
                            ->value('name') ?? 'Unknown';

                        $grouped[$sbjId][] = [
                            'assessment' => $markName,
                            'score' => $score->scr,
                        ];

                        $totalScore += $score->scr;
                    }

                    $subjectCount++;
                }
            }

            // Average is based on number of subjects with scores
            $avg = $subjectCount ? round($totalScore / $subjectCount, 2) : 0;

            // Format scores with subject names
            $formattedScores = [];
            foreach ($grouped as $sbjid => $scrArray) {
                $subjectName = subj::find($sbjid)?->name ?? "Subject-$sbjid";
                $formattedScores[] = [
                    'subject_id' => $sbjid,
                    'subject_name' => $subjectName,
                    'scores' => $scrArray // array of [assessment, score]
                ];
            }

            $scoreSheet[] = [
                'uid' => $student->uid,
                'schid' => $student->schid,
                'sid' => $stid,
                'learner_id' => $student->suid ?? $stid,
                'name' => trim($student->fname . ' ' . $student->lname),
                'avg_score' => $avg,
                'class' => $clsm,
                'class_name' => $className,
                'class_arm_name' => $classArmName,
                'no_of_subjects' => $subjectCount,
                'scores' => $formattedScores,
                'clsid' => $clsm,
                'clsa' => $student->clsa,
                'ssn' => $ssn,
                'trm' => $trm,
                'position' => 0 // placeholder, assigned below
            ];
        }

        // Assign position based on avg_score
        usort($scoreSheet, fn($a, $b) => $b['avg_score'] <=> $a['avg_score']);

        $rank = 1;
        $lastAvg = null;
        $tieCount = 0;

        foreach ($scoreSheet as $index => &$std) {
            if ($std['avg_score'] === $lastAvg) {
                $tieCount++;
            } else {
                $rank += $tieCount;
                $tieCount = 1;
            }
            $std['position'] = $rank;
            $lastAvg = $std['avg_score'];
        }

        return response()->json([
            'status' => true,
            'message' => 'Student Score Summary',
            'data' => $scoreSheet
        ]);
    }





    /**
     * @OA\Post(
     *     path="/api/autoCommentTemplate",
     *     summary="Save or update auto comments by grade for a specific role (Principal, Head Teacher, School Admin)",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "ssn", "trm", "clsm", "clsa", "role", "comment"},
     *             @OA\Property(property="schid", type="string", example="12"),
     *             @OA\Property(property="ssn", type="string", example="2024"),
     *             @OA\Property(property="trm", type="string", example="1"),
     *             @OA\Property(property="clsm", type="string", example="11"),
     *             @OA\Property(property="clsa", type="string", example="2"),
     *             @OA\Property(
     *                 property="role",
     *                 type="string",
     *                 enum={"Principal", "Head Teacher", "School Admin"},
     *                 example="Principal"
     *             ),
     *             @OA\Property(
     *                 property="comment",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"grade", "comment"},
     *                     @OA\Property(
     *                         property="grade",
     *                         type="string",
     *                         enum={"A","B","C","D","E","F"},
     *                         example="A"
     *                     ),
     *                     @OA\Property(
     *                         property="comment",
     *                         type="string",
     *                         example="Excellent performance"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Auto comments saved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Auto comments saved."),
     *             @OA\Property(property="role", type="string", example="Principal"),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="grade", type="string", example="A"),
     *                     @OA\Property(property="comment", type="string", example="Excellent performance")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "role": {"The selected role is invalid."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */


    public function autoCommentTemplate(Request $request)
    {
        $validated = $request->validate([
            'schid' => 'required|string',
            'ssn'   => 'required|string',
            'trm'   => 'required|string',
            'clsm'  => 'required|string',
            'clsa'  => 'required|string',
            'role'  => 'required|string|in:Principal,Head Teacher,School Admin',
            'comment' => 'required|array',
            'comment.*.grade' => 'required|string|in:A,B,C,D,E,F',
            'comment.*.comment' => 'required|string|max:500',
        ]);

        // Save each comment by role name
        foreach ($validated['comment'] as $entry) {
            auto_comment_template::updateOrCreate(
                [
                    'schid' => $validated['schid'],
                    'ssn'   => $validated['ssn'],
                    'trm'   => $validated['trm'],
                    'clsm'  => $validated['clsm'],
                    'clsa'  => $validated['clsa'],
                    'role'  => $validated['role'],  // using role name directly
                    'grade' => $entry['grade'],
                ],
                ['comment' => $entry['comment']]
            );
        }

        // Build response for all 6 grades
        $grades = ['A', 'B', 'C', 'D', 'E', 'F'];
        $response = [];

        foreach ($grades as $grade) {
            $comment = auto_comment_template::where([
                'schid' => $validated['schid'],
                'ssn'   => $validated['ssn'],
                'trm'   => $validated['trm'],
                'clsm'  => $validated['clsm'],
                'clsa'  => $validated['clsa'],
                'role'  => $validated['role'],
                'grade' => $grade,
            ])->value('comment') ?? '';

            $response[] = [
                'grade' => $grade,
                'comment' => $comment
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Auto comments saved.',
            'role' => $validated['role'],
            'pld' => $response
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/filterCommentByRole",
     *     summary="Get auto-comments filtered by role",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=true,
     *         description="Role ID to filter auto-comments (e.g. 1, 2, 3)",
     *         @OA\Schema(type="string", example="2")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Auto comments returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Auto comments filtered by role."),
     *             @OA\Property(property="role_name", type="string", example="Principal"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=4),
     *                 @OA\Property(property="schid", type="string", example="12"),
     *                 @OA\Property(property="ssn", type="string", example="2024"),
     *                 @OA\Property(property="trm", type="string", example="2"),
     *                 @OA\Property(property="clsm", type="string", example="11"),
     *                 @OA\Property(property="clsa", type="string", example="2"),
     *                 @OA\Property(property="role", type="string", example="2"),
     *                 @OA\Property(property="grade", type="string", example="A"),
     *                 @OA\Property(property="comment", type="string", example="Excellent performance"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-15T18:09:48"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-15T18:09:48")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The role field is required.")
     *         )
     *     )
     * )
     */

    public function filterCommentByRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        $comments = auto_comment_template::where('role', $request->role)
            ->orderBy('grade')
            ->get();

        $roleName = staff_role::where('id', $request->role)->value('name') ?? 'Unknown Role';

        return response()->json([
            'status' => true,
            'message' => 'Auto comments filtered by role.',
            'role_name' => $roleName,
            'pld' => $comments
        ]);
    }






    /**
     * @OA\Post(
     *     path="/api/allStudentResultsComment",
     *     summary="Generate and save student result comments using manual or auto-comment templates",
     *     description="This endpoint auto-generates comments based on average grade and selected role (Principal, Head Teacher, School Admin). Manual comments take priority if they exist.",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schid", "ssn", "trm", "clsm", "clsa", "role"},
     *             @OA\Property(property="schid", type="string", example="12"),
     *             @OA\Property(property="ssn", type="string", example="2024"),
     *             @OA\Property(property="trm", type="string", example="1"),
     *             @OA\Property(property="clsm", type="string", example="11"),
     *             @OA\Property(property="clsa", type="string", example="2"),
     *             @OA\Property(property="role", type="string", enum={"Principal", "Head Teacher", "School Admin"}, example="Principal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All student results saved with auto-comments.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All student results saved with auto-comments."),
     *             @OA\Property(
     *                 property="pld",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="uid", type="integer", example=101),
     *                     @OA\Property(property="stid", type="integer", example=1001),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="avg", type="number", format="float", example=87.5),
     *                     @OA\Property(property="grade", type="string", example="A"),
     *                     @OA\Property(property="comment", type="string", example="Excellent performance"),
     *                     @OA\Property(property="position", type="integer", example=1),
     *                     @OA\Property(property="manual_comment_used", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "schid": {"The schid field is required."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */



    public function allStudentResultsComment(Request $request)
    {
        $request->validate([
            'schid' => 'required|string',
            'ssn'   => 'required|string',
            'trm'   => 'required|string',
            'clsm'  => 'required|string',
            'clsa'  => 'required|string',
            'role'  => 'required|string|in:Principal,Head Teacher,School Admin',
        ]);

        // Get score summary for the selected class
        $students = $this->getStudentScoreSummary($request)->getData(true)['data'];

        $updatedResults = [];

        foreach ($students as $std) {
            $avg = $std['avg_score'];

            // ✅ Get grade from dynamic sch_grade table
            $grade = $this->gradeFromAvg($avg, $std['schid'], $std['clsid'], $std['ssn'], $std['trm']);

            // Check for manually entered comment
            $existing = student_res::where([
                'stid' => $std['sid'],
                'schid' => $std['schid'],
                'ssn' => $std['ssn'],
                'trm' => $std['trm'],
                'clsm' => $std['clsid'],
                'clsa' => $std['clsa'],
            ])->first();

            $manualComment = $existing?->com;
            if ($manualComment === 'NIL~NIL' || $manualComment === null) {
                $manualComment = '';
            }

            // Auto comment from configured template
            $autoComment = auto_comment_template::where([
                'schid' => $std['schid'],
                'ssn'   => $std['ssn'],
                'trm'   => $std['trm'],
                'clsm'  => $std['clsid'],
                'clsa'  => $std['clsa'],
                'role'  => $request->role,
                'grade' => $grade,
            ])->value('comment') ?? 'No comment configured for this grade.';

            // Use manual if exists, else fallback to auto
            $finalComment = !empty($manualComment) ? $manualComment : $autoComment;

            // Save or update the result
            $result = student_res::updateOrCreate(
                ['uid' => $std['uid']],
                [
                    'stid' => $std['sid'],
                    'schid' => $std['schid'],
                    'ssn' => $std['ssn'],
                    'trm' => $std['trm'],
                    'clsm' => $std['clsid'],
                    'clsa' => $std['clsa'],
                    'stat' => '1',
                    'com' => $finalComment,
                    'pos' => $std['position'],
                    'avg' => $avg,
                    'cavg' => $existing?->cavg ?? 0,
                ]
            );

            $updatedResults[] = [
                'uid' => $result->uid,
                'stid' => $result->stid,
                'name' => $std['name'],
                'avg' => $avg,
                'grade' => $grade,
                'comment' => $finalComment,
                'position' => $std['position'],
                'manual_comment_used' => !empty($manualComment),
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'All student results saved with auto-comments.',
            'pld' => $updatedResults
        ]);
    }

    private function gradeFromAvg($avg, $schid, $clsm, $ssn, $trm)
    {
        $grades = sch_grade::where('schid', $schid)
            ->where('clsid', $clsm)
            ->where('ssn', $ssn)
            ->where('trm', $trm)
            ->orderByDesc('g0') // From highest to lowest
            ->get();

        foreach ($grades as $grade) {
            if ($avg >= $grade->g0 && $avg <= $grade->g1) {
                return $grade->grd;
            }
        }

        return 'N/A'; // If no grade matched..
    }




    /**
     * @OA\Get(
     *     path="/api/getSubjectsByClass/{classId}",
     *     summary="Get subjects by class ID",
     *     description="Returns a list of subjects for a given class.",
     *     operationId="getSubjectsByClass",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="ID of the class",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="cls", type="integer", example=11),
     *             @OA\Property(
     *                 property="sub",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="subj_id", type="string", example="98"),
     *                     @OA\Property(property="name", type="string", example="FRENCH")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Class not found"
     *     )
     * )
     */
    public function getSubjectsByClass($classId)
    {
        $subjects = class_subj::where('clsid', $classId)
            ->select('subj_id', 'name')
            ->distinct()
            ->get();

        return response()->json([
            'cls' => $classId,
            'sub' => $subjects
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/promoteStudent",
     *     summary="Promote a student to the next class",
     *     description="Creates a new promotion record for the student. Previous records remain unchanged.",
     *     operationId="promoteStudent",
     *     tags={"Api"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sid","schid","sesn","trm","clsm","clsa"},
     *             @OA\Property(property="sid", type="integer", example=1000, description="Student ID"),
     *             @OA\Property(property="schid", type="integer", example=13, description="School ID"),
     *             @OA\Property(property="sesn", type="string", example="2025", description="Academic session"),
     *             @OA\Property(property="trm", type="integer", example=1, description="Term number"),
     *             @OA\Property(property="clsm", type="integer", example=12, description="Main class ID"),
     *             @OA\Property(property="clsa", type="string", example="1", description="Class arm/section"),
     *             @OA\Property(property="suid", type="string", example="HRS/2025/1/68", description="Student unique ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student promoted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student promoted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The sid field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    // public function promoteStudent(Request $request)
    // {
    //     $request->validate([
    //         'sid'   => 'required',
    //         'schid' => 'required',
    //         'sesn'  => 'required',
    //         'trm'   => 'required',
    //         'clsm'  => 'required', // main class
    //         'clsa'  => 'required', // class arm
    //         'suid'  => 'required',
    //     ]);

    //     // Find the student
    //     $student = student::where('sid', $request->sid)->firstOrFail();

    //     // Generate a unique promotion ID (could be session+term+student ID)
    //     $uid = $request->sesn . $request->trm . $request->sid;

    //     // Always create a new row in old_student (no update)
    //     old_student::create([
    //         'uid'    => $uid,
    //         'sid'    => $request->sid,
    //         'schid'  => $request->schid,
    //         'fname'  => $student->fname,
    //         'mname'  => $student->mname,
    //         'lname'  => $student->lname,
    //         'status' => 'active',
    //         'suid'   => $request->suid,
    //         'ssn'    => $request->sesn,
    //         'trm'    => $request->trm,
    //         'clsm'   => $request->clsm, // main class
    //         'clsa'   => $request->clsa, // arm
    //         'more'   => '',
    //     ]);

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Student promoted successfully',
    //     ]);
    // }


    public function promoteStudent(Request $request)
    {
        $request->validate([
            'sid'   => 'required',
            'schid' => 'required',
            'sesn'  => 'required',
            'trm'   => 'required',
            'clsm'  => 'required', // main class
            'clsa'  => 'required', // class arm
            'suid'  => 'required',
        ]);

        // Find the student
        $student = student::where('sid', $request->sid)->firstOrFail();

        // Check if student has already been promoted for this session and term
        $existingPromotion = old_student::where('sid', $request->sid)
            ->where('ssn', $request->sesn)
            ->where('trm', $request->trm)
            ->first();

        if ($existingPromotion) {
            return response()->json([
                'status'  => false,
                'message' => 'Student has already been promoted for this session and term',
            ], 409); // 409 Conflict
        }

        // Generate a unique promotion ID
        $uid = $request->sesn . $request->trm . $request->sid;

        // Create a new promotion record
        old_student::create([
            'uid'    => $uid,
            'sid'    => $request->sid,
            'schid'  => $request->schid,
            'fname'  => $student->fname,
            'mname'  => $student->mname,
            'lname'  => $student->lname,
            'status' => 'active',
            'suid'   => $request->suid,
            'ssn'    => $request->sesn,
            'trm'    => $request->trm,
            'clsm'   => $request->clsm, // main class
            'clsa'   => $request->clsa, // arm
            'more'   => '',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Student promoted successfully',
        ]);
    }





    /**
     * @OA\Post(
     *     path="/api/exitStudent/{schid}/{stid}",
     *     summary="Exit a student and move them to alumni",
     *     description="Marks a student as inactive and moves them to the alumni database if they are not already present.",
     *     operationId="exitStudent",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="schid",
     *         in="path",
     *         required=true,
     *         description="The school ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="stid",
     *         in="path",
     *         required=true,
     *         description="The student ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason_for_exit"},
     *             @OA\Property(property="reason_for_exit", type="string", example="Graduated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student successfully exited and moved to alumni",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student has been exited successfully and moved to alumni.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Student has already been moved to alumni",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student has already been moved to alumni.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found.")
     *         )
     *     )
     * )
     */

    public function exitStudent(Request $request, $schid, $stid)
    {
        $request->validate([
            'reason_for_exit' => 'required|string|max:555',
        ]);

        $student = student::where('schid', $schid)->where('sid', $stid)->first();

        if (!$student) {
            return response()->json([
                'status' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        if (alumni::where('stid', $stid)->where('schid', $schid)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Student has already been moved to alumni.',
            ], 400);
        }

        // Retrieve term_of_entry from school table
        $school = school::where('sid', $schid)->first();
        $termOfEntry = null;

        if ($school) {
            $termOfEntry = trm::where('no', $school->ctrm)->value('name'); // Get term name
        }

        // Generate the `suid`
        $ssn = $student->year;
        $term = $student->term;
        $count = $student->count;
        $suid = $student->sch3 . '/' . $ssn . '/' . $term . '/' . strval($count);

        // Fetch class details
        $oldStudent = old_student::where('schid', $schid)->where('sid', $stid)->first();
        $exitClassId = $oldStudent ? $oldStudent->clsm : null;
        $exitClassArmId = $oldStudent ? $oldStudent->clsa : null;

        $classDetails = sch_cls::where('cls_id', $exitClassId)
            ->where('schid', $schid)
            ->first();

        $exitClass = $classDetails ? $classDetails->cls_id : null;
        $exitClassArm = $classDetails ? $classDetails->name : null;

        $className = cls::where('id', $exitClass)->value('name');

        // Fetch session and term names
        $sessionOfEntry = sesn::where('year', $student->year)->value('name'); // Get session name
        $sessionOfExit = sesn::where('year', now()->year)->value('name'); // Get current session name
        $termOfExit = trm::where('no', $student->term)->value('name'); // Same term as entry

        DB::beginTransaction();

        try {
            $student->update([
                'status' => 'inactive',
                'exit_status' => 'exited'
            ]);

            if (DB::table('old_student')->where('schid', $schid)->where('sid', $stid)->exists()) {
                DB::table('old_student')
                    ->where('schid', $schid)
                    ->where('sid', $stid)
                    ->update(['status' => 'inactive']);
            }

            $student->refresh();

            if ($student->status === 'inactive') {
                alumni::create([
                    'stid' => $student->sid,
                    'schid' => $student->schid,
                    'suid' => $suid,
                    'lname' => $student->lname,
                    'fname' => $student->fname,
                    'mname' => $student->mname,
                    'sch3' => $student->sch3,
                    'count' => strval($count),
                    'session_of_entry' => $sessionOfEntry, // Actual session name
                    'term_of_entry' => $termOfEntry, // Fetched from school table
                    'date_of_entry' => $student->created_at->toDateString(),
                    'session_of_exit' => $sessionOfExit, // Actual session name
                    'term_of_exit' => $termOfExit, // Actual term name
                    'date_of_exit' => now()->toDateString(),
                    'reason_for_exit' => $request->input('reason_for_exit'),
                    'exit_class' => $className,
                    'exit_class_arm' => $exitClassArm,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Student has been exited successfully and moved to alumni.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to exit student. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/repeatStudent",
     *     summary="Mark a student to repeat the same class",
     *     description="Creates a new record for a student to repeat their current class/arm in a new session/term. Previous records remain unchanged.",
     *     operationId="repeatStudent",
     *     tags={"Api"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sid","schid","sesn","trm","clsm","clsa","suid"},
     *             @OA\Property(property="sid", type="integer", example=1000, description="Student ID"),
     *             @OA\Property(property="schid", type="integer", example=13, description="School ID"),
     *             @OA\Property(property="sesn", type="string", example="2025", description="Academic session"),
     *             @OA\Property(property="trm", type="integer", example=1, description="Term number"),
     *             @OA\Property(property="clsm", type="integer", example=12, description="Main class ID"),
     *             @OA\Property(property="clsa", type="string", example="1", description="Class arm/section"),
     *             @OA\Property(property="suid", type="string", example="HRS/2025/1/68", description="Student unique ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student marked to repeat successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student marked to repeat successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The sid field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    // public function repeatStudent(Request $request)
    // {
    //     $request->validate([
    //         'sid'   => 'required',
    //         'schid' => 'required',
    //         'sesn'  => 'required',
    //         'trm'   => 'required',
    //         'clsm'  => 'required', // main class
    //         'clsa'  => 'required', // class arm
    //         'suid'  => 'required|string'
    //     ]);

    //     // Find the student
    //     $student = student::where('sid', $request->sid)->firstOrFail();

    //     // Generate a unique ID for the repeat record
    //     $uid = $request->sesn . $request->trm . $request->sid;

    //     // Create a new old_student record for repeating
    //     old_student::create([
    //         'uid'    => $uid,
    //         'sid'    => $request->sid,
    //         'schid'  => $request->schid,
    //         'fname'  => $student->fname,
    //         'mname'  => $student->mname,
    //         'lname'  => $student->lname,
    //         'status' => 'active',
    //         'suid'   => $request->suid,
    //         'ssn'    => $request->sesn,
    //         'trm'    => $request->trm,
    //         'clsm'   => $request->clsm, // same class
    //         'clsa'   => $request->clsa, // same arm
    //         'more'   => '',       // mark as repeat
    //     ]);

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Student marked to repeat successfully',
    //     ]);
    // }

    public function repeatStudent(Request $request)
    {
        $request->validate([
            'sid'   => 'required',
            'schid' => 'required',
            'sesn'  => 'required',
            'trm'   => 'required',
            'clsm'  => 'required', // main class
            'clsa'  => 'required', // class arm
            'suid'  => 'required|string'
        ]);

        // Find the student
        $student = student::where('sid', $request->sid)->firstOrFail();

        // Check if student is already marked to repeat for the same session, term, and class/arm
        $existingRepeat = old_student::where('sid', $request->sid)
            ->where('ssn', $request->sesn)
            ->where('trm', $request->trm)
            ->where('clsm', $request->clsm)
            ->where('clsa', $request->clsa)
            ->first();

        if ($existingRepeat) {
            return response()->json([
                'status'  => false,
                'message' => 'Student is already marked to repeat for this session and term',
            ], 409); // 409 Conflict
        }

        // Generate a unique ID for the repeat record
        $uid = $request->sesn . $request->trm . $request->sid;

        // Create a new old_student record for repeating
        old_student::create([
            'uid'    => $uid,
            'sid'    => $request->sid,
            'schid'  => $request->schid,
            'fname'  => $student->fname,
            'mname'  => $student->mname,
            'lname'  => $student->lname,
            'status' => 'active',
            'suid'   => $request->suid,
            'ssn'    => $request->sesn,
            'trm'    => $request->trm,
            'clsm'   => $request->clsm,
            'clsa'   => $request->clsa,
            'more'   => '', // mark as repeat
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Student marked to repeat successfully',
        ]);
    }
}
