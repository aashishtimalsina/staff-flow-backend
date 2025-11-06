<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

use function Laravel\Prompts\error;

class AuthController extends Controller
{
    use ApiResponse;

    public function loginWithGoogle(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'token' => 'required|string',
            ]);

            $googleToken = $request->input('token');

            // Get user from Google token
            $googleUser = Socialite::driver('google')->userFromToken($googleToken);

            if (!$googleUser) {
                return $this->unauthorizedResponse('Invalid Google token');
            }

            // Extract Google user info
            $userData = [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'oauth_id' => $googleUser->getId(),
                'photo' => $googleUser->getAvatar()
            ];

            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'oauth_id' => $userData['oauth_id'],
                    'photo' => $userData['photo'],
                    'password' => bcrypt(Str::random(12)),
                    'role' => User::ROLE_USER,
                    'is_active' => true,
                    'is_verified' => true,
                ]
            );

            if (!$user) {
                return $this->serverErrorResponse('Failed to create or update user. Please try again.');
            }

            // Update last login and IP address
            $user->update([
                'last_login' => now(),
                'ip_address' => $request->ip(),
            ]);

            // Create token
            $token = $user->createToken('google_login')->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'user' => $user,
            ], 'Login successful');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {

            return $this->serverErrorResponse('Failed to login with Google. Please try again.');
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {

            return $this->serverErrorResponse('Failed to logout. Please try again.');
        }
    }

    public function login(Request $request)
    {
        try {
            // Validate credentials
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            // Attempt to authenticate
            if (!Auth::attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid email or password');
            }

            $user = Auth::user();

            // Update last login and IP address
            $user->update([
                'last_login' => now(),
                'ip_address' => $request->ip(),
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'user' => $user,
            ], 'Login successful');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {

            return $this->serverErrorResponse('Failed to login. Please try again.');
        }
    }

    public function register(Request $request)
    {
        try {
            $credentials = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => 'required|string|min:8|max:255',
                'password_confirmation' => 'required|same:password',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|in:england,scotland,wales,northern_ireland',
                'postal_code' => 'nullable|string|max:20',
                'photo' => 'nullable|image|max:2048|mimes:jpg,jpeg,png,bmp,tiff,gif',
            ]);

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('images/user'), $photoName);
                $credentials['photo'] = $photoName;
            }

            $user = User::create([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => bcrypt($credentials['password']),
                'role' => User::ROLE_USER,
                'is_active' => true,
                'is_verified' => false,
                'phone' => $credentials['phone'] ?? null,
                'address' => $credentials['address'] ?? null,
                'city' => $credentials['city'] ?? null,
                'state' => $credentials['state'] ?? null,
                'postal_code' => $credentials['postal_code'] ?? null,
                'photo' => $credentials['photo'] ?? null,
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'user' => $user,
            ], 'User registered successfully', 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Illuminate\Database\QueryException $e) {


            // Check for duplicate entry error
            if ($e->getCode() == 23000) {
                return $this->errorResponse('Email already exists', 409);
            }

            return $this->serverErrorResponse('Failed to register user. Please try again.');
        } catch (\Exception $e) {

            return $this->serverErrorResponse('Failed to register user. Please try again.');
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated');
            }

            return $this->successResponse([
                'user' => $user,
            ], 'User retrieved successfully');
        } catch (\Exception $e) {

            return $this->serverErrorResponse('Failed to retrieve user information');
        }
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => 'required|string|min:8|max:255',
            'password_confirmation' => 'required|same:password',
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return $this->successResponse([
            'user' => $user,
        ], 'Password updated successfully');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->all();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|in:england,scotland,wales,northern_ireland',
            'postal_code' => 'sometimes|nullable|string|max:20',
            'photo' => 'sometimes|nullable|image|max:2048|mimes:jpg,jpeg,png,bmp,tiff,gif',
        ]);


        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images/user'), $photoName);
            $data['photo'] = $photoName;
        }

        $user->update($data);

        return $this->successResponse([
            'user' => $user,
        ], 'Profile updated successfully');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()
                ->json(
                    [
                        'status' => false,
                        'message' => 'Email not found.'
                    ],
                    404
                );
        }

        $token = 'PWD-@' . Str::random(5);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Send email
        Mail::raw(
            "Use this token to reset your password: $token",
            function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Password Reset Request');
            }
        );

        return response()->json(['message' => 'Password reset token sent to your email.']);
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|min:6|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/',
            ], [
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 6 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one digit.',
            ]);

            $reset = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$reset) {
                return response()->json(['message' => 'Invalid token or email.'], 400);
            }

            // Check if token is expired (older than 60 min)
            if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
                return response()->json(['message' => 'Token expired.'], 400);
            }

            // Update user password
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Delete token after use
            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json(['message' => 'Password reset successful.']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to reset password. Please try again.'], 500);
        }
    }
}
