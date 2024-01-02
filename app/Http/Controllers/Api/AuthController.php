<?php

namespace App\Http\Controllers\Api;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\Cast\Object_;
// use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Models\Role;
use stdClass;

class AuthController extends Controller
{
    use HasApiTokens;
    public function register(Request $request)
    {

     
            // Validate the incoming request data
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255',
                // 'depo_id' => 'required_unless:role,depo|exists:depo,id',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|string', // Adjust validation rules based on your needs
                'nomor_telepon' => 'required|string', // Adjust validation rules based on your needs
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            // return response()->json(['error' => $request->role], 422);
            
            // Create a new user
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                // 'role' => $request->role,
                'nomor_telepon' => $request->nomor_telepon,
            ]);
            $role = Role::where('name', $request->role)->first();
            if (!$role) {
                // Handle the case where the specified role doesn't exist
                $user->delete(); // Rollback the user creation
                return response()->json(['error' => 'Invalid role specified'], 422);
            }
          
            $user->assignRole($role);
            // Generate token for the registered user
            $token = $user->createToken('MyAppToken')->plainTextToken;
            // Create a new sales record
                    // Check if the assigned role contains the word 'sales'
            if(!$user->id){
                return response()->json(['error' => 'Failed to regis user'], 422);
            }
            $request->merge(['fromAuthController' => true]);
            $request->merge(['user_id' => $user->id]);
            try {
                if (Str::contains(strtolower($role->name), 'sales')) {
                    $request->merge(['tipe' => $role->name]);
                    $salesController = new SalesController();
                    $userRole = $salesController->store($request);
                } elseif (Str::contains(strtolower($role->name), 'depo')) {
                    $depoController = new DepoController(); 
                    $userRole =  $depoController->store($request);
                } elseif (Str::contains(strtolower($role->name), 'driver')) {
                    $driverController = new DriverController(); 
                    $userRole = $driverController->store($request);
                }elseif (Str::contains(strtolower($role->name), 'super admin')){
                    $userRole =$role;
                } 
                else {
                    // Handle other roles or raise an exception if no matching role is found
                    throw new \Exception('Unsupported role: ' . $role->name);
                }
            
                // Continue with other logic if needed
            
            } catch (\Exception $e) {
                // Handle the exception (e.g., log, rollback, or report the error)
                // Undo store operations or take appropriate action based on your requirements
                // Example: $salesController->undoStore();
                // Example: $depoController->undoStore();
                // Example: $driverController->undoStore();
            
                // Optionally rethrow the exception if needed
                throw $e;
            }

           
            
            return response()->json([
                'data' => $user,
                'access_token' => $token,
                'role' => $role->name,
                'token_type' => 'Bearer',
                // 'salesController' => $salesController->getSalesData()
                'detail_role' => $userRole,
            ]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $user = User::where('email', $request->email)->first();
        // return new AllResource(false, 'Invalid credentials',  $user->id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $userRoles = Auth::user()->roles->pluck('name')->toArray();
        
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            //check collection in model user
            $allRoles = collect(['depo', 'sales', 'driver'])
            ->map(function ($role) use ($user) {
                return $user->$role;
            })
            ->filter()
            ->values()
            ->first();
            if (in_array('super admin', $userRoles)) {
             
                $allRoles = new stdClass();
                $allRoles->id=  $user->id;
                $allRoles->nama=  $user->username;
                $allRoles->alamat=  "Jakarta";
          
            }
                if ($user) {
                    $roles = $user->roles; 
                    foreach ($roles as $role) {
                        $roleName = $role->name;
                        $role_id = $role->id;
                    }
                } else {
                    return response()->json(['error' => 'Role not found'], 404);

                }
                $loginTime = Carbon::now(); // Get the current time using Carbon
                $expirationTime = $loginTime->copy()->addHours(2); // Set the expiration time to be 2 hours after login time

                $loginTimeMilliseconds = $loginTime->valueOf(); // Get the login time in milliseconds
                $expirationMilliseconds = $expirationTime->valueOf();
                $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.expiration')))->plainTextToken;
                $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')))->plainTextToken;
                $token = $user->createToken('auth_token', ['expires_in' => 2 * 60 * 60])->plainTextToken;
                $user->remember_token = $accessToken;
                $user->save();

                
                // return new AllResource(true, 'Success', $user->remember_token);
                $login = [
                    'login_time' => $loginTimeMilliseconds,
                    'exp' => $expirationMilliseconds,
                    'api_token' => $token,
                    'refresh_token' => $refreshToken,
                    'user' => [
                        'id' => $allRoles->id,
                        'nama' => $allRoles->nama,
                        'email' => $user->email,
                        'alamat' => $allRoles->alamat,
                        'no_hp' => $user->nomor_telepon,
                    ],
                    'role' => [
                        'role_id' => $role_id,
                        'role' => $roleName,
                        'permission' => [],
                    ],
                ];
                    
                    $response = new AllResource(true, 'Success', $login);

                    // Add the access token as a cookie
                    $cookie = cookie('api_token', $accessToken, config('sanctum.expiration'));

                    // Return the response with the cookie
                    return $response->response()->withCookie($cookie);      
                 } else {
                            return new AllResource(false, 'Invalid credentials', null);
                        }
                        
        
    }

    public function logout(Request $request)
    {
        $user=$request->user();

        return new AllResource(true, 'Tokens revoked', $user);

        // return response()->json(['message' => 'Tokens revoked']);
    }
  
public function refreshToken(Request $request)
{
    
    // Assuming you already have a logged-in user
    $user = User::where('remember_token', $request->api_token)->first();
    return new AllResource(false, 'Invalid credentials', null);

    if (!$user) {
    }

    // Set the login time in the Jakarta time zone
    $loginTime = now()->setTimezone('Asia/Jakarta');

    // Set the expiration time to be 2 hours after login time
    $expirationTime = $loginTime->addHours(2);

    // Calculate the new expiration time in seconds
    $expiresIn = $expirationTime->diffInSeconds($loginTime);

    // Get the current user's access token
    $currentAccessToken = $user->remember_token;

   
    // Get the current user's access token

    // If the user has an existing access token, create a new one
    if ($currentAccessToken) {
        // Revoke the current access token
        

        // Create a new access token
        $newAccessToken = $user->createToken('auth_token', ['expires_in' => $expiresIn])->plainTextToken;
        $user->update([
            'remember_token' => hash('sha256', $newAccessToken)
            // 'remember_token_expires_at' => $expiresAt,
        ]);
        return new AllResource(true, 'Token refreshed successfully', ['api_token' => $newAccessToken]);
    } else {
        // If the user doesn't have an existing access token, handle accordingly
        return new AllResource(false, 'Invalid credentials', null);
    }

}
}