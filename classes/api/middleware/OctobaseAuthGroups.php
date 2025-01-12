<?php namespace Dilexus\Octobase\Classes\Api\Middleware;

//
// Copyright 2023 Chatura Dilan Perera. All rights reserved.
// Use of this source code is governed by license that can be
//  found in the LICENSE file.
// Website: https://www.dilan.me
//

use Closure;
use October\Rain\Auth\Models\User;
use RainLab\User\Facades\Auth;

class OctobaseAuthGroups{
    public function handle($request, Closure $next, $groups, $own = 'false'){
        $authGroups = explode(':', $groups);
        $authroization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authroization);
        $user = User::whereRaw('SHA2(persist_code, 256) = ?', [$token])->first();
        if(!$user){
            return response()->json(['error' => 'Unauthorized Accesss'], 401);
        }
        $authUser = Auth::findUserById($user->id);
        $groups = $authUser['groups']->lists('code');
        $commonGroups=array_intersect($groups,$authGroups);
        if(empty($commonGroups)){
            return response()->json(['error' => 'Unauthorized Accesss'], 401);
        }
        if(in_array('admin', $groups)){
            $request->merge(['userId' => $user['id']]);
            $request->attributes->add(['own' => $own]);
            return $next($request);
        }else {
            return response()->json(['error' => 'Unauthorized Accesss'], 401);
        }

    }
}
