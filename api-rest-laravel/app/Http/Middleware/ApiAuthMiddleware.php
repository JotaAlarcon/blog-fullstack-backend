<?php

namespace App\Http\Middleware;

use Closure;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //Comprobar si el usuario esta identificado
        //recoger token desde el header
        $token = $request->header('authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        if($checkToken){
            
            return $next($request);
        }else{
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'El usuario no esta identificado'
            );  
            return response()->json($data, $data['code']); 
        }
    }

    
}
