<?php
use Dilexus\Octobase\Models\Settings;
use Illuminate\Http\Request;
use October\Rain\Auth\Models\User;
use RainLab\User\Facades\Auth;

Route::prefix('octobase')->group(function () {

    Route::post('signin', function (Request $request)  {
        try{
                $authroization = $request->header('Authorization');
                $token = str_replace('Bearer ', '', $authroization);
                $credentials = explode(':', base64_decode($token));
                $username = $credentials[0];
                $password = $credentials[1];

                $user = Auth::authenticate([
                    'login' => $username,
                    'password' => $password
                ]);
                if(!$user){
                    return response()->json(['error' => 'Authentication Failed'], 400);
                }
            return response()->json([ 'first_name' => $user['name'],
                'last_name' => $user['surname'],
                'email' => $user['email'],
                'username' => $user['username'],
                'groups' => $user['groups']->lists('code'),
                'token' => hash('sha256',$user['persist_code']),
            ]);
        }catch(\Exception $e){
            return response()->json(['error' =>  $e->getMessage()], 400);
        }
    });

    Route::post('signout', function (Request $request)  {
        try{
            $authroization = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $authroization);
            $user = User::whereRaw('SHA2(persist_code, 256) = ?', [$token])->first();
            if(!$user){
                return response()->json(['error' => 'Authentication Failed'], 400);
            }
            Auth::setUser($user);
            Auth::logout();
            return response()->json(['success' => 'Signout Success']);
        }catch(\Exception $e){
            return response()->json(['error' =>  $e->getMessage()], 400);
        }
    });

    Route::post('signup', function (Request $request)  {
        try{
            $registration_disabled = Settings::get('registration_disabled');
            $require_activation = Settings::get('require_activation');
            Settings::get('registration_disabled');
            if($registration_disabled){
                return response()->json(['error' => 'Registration is disabled'], 400);
            }else {
                $payload = [
                    'name' => $request->input('first_name'),
                    'surname' => $request->input('last_name'),
                    'email' => $request->input('email'),
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                    'password_confirmation' => $request->input('password_confirmation'),
                ];
                $user = Auth::register($payload, $require_activation);

                return response()->json($user);
            }
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    });

    Route::get('user', function (Request $request)  {
        try{
            $authroization = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $authroization);
            $user = User::whereRaw('SHA2(persist_code, 256) = ?', [$token])->first();

            if(!$user){
                return response()->json(['error' => 'Authentication Failed'], 400);
            }

            $authUser = Auth::findUserById($user->id);

            if($authUser){
                 return response()->json([ 'first_name' => $user['name'],
                 'last_name' => $authUser['surname'],
                 'email' => $authUser['email'],
                 'username' => $authUser['username'],
                 'is_activated' => $authUser['is_activated'],
                 'groups' => $authUser['groups']->lists('code'),
                 'token' => $token]
            );

            }else{
                return response()->json(['error' => 'User Not Found for the given token'], 400);
            }
        }catch(\Exception $e){
            return response()->json(['error' =>  $e->getMessage()], 400);
        }
    });

    Route::post('refresh', function (Request $request)  {
        try{
            $authroization = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $authroization);
            $user = User::whereRaw('SHA2(persist_code, 256) = ?', [$token])->first();

            if(!$user){
                return response()->json(['error' => 'Authentication Failed'], 400);
            }

            Auth::setUser($user);
            Auth::logout();
            Auth::login($user, true);
            $authUser = Auth::findUserById($user->id);

            if($authUser){
                 return response()->json([ 'first_name' => $authUser['name'],
                 'last_name' => $authUser['surname'],
                 'email' => $authUser['email'],
                 'username' => $authUser['username'],
                 'groups' => $authUser['groups']->lists('code'),
                 'token' => hash('sha256',$authUser['persist_code'])]
            );

            }else{
                return response()->json(['error' => 'User Not Found for the given token'], 400);
            }
        }catch(\Exception $e){
            return response()->json(['error' =>  $e->getMessage()], 400);
        }
    });

    function getAuthUser($username, $password){
        try{
            $user = Auth::authenticate([
                'login' => $username,
                'password' => $password
            ]);
            return $user;
        }catch(\Exception $e){
            return response()->json(['error' =>  $e->getMessage()], 400);
        }
    }
});
