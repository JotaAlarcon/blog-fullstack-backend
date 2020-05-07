<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    
    public function register(Request $request) {

        //Recoger los datos del usuario por POST
        $json = $request->input('json', null);
        //Decodificar Json
        $params = json_decode($json); //objeto
        $params_array = json_decode($json, true); //array

        if (!empty($params) && !empty($params_array)) {

            //quitar espacios antes y despues de los datos
            $params_array = array_map('trim', $params_array);

            //Validar los datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                //Comprobar si el usuario ya existe con unique
                'email'     => 'required|email|unique:users',
                'password'  => 'required'
            ]);

            if ($validate->fails()) {
                //Validacion fallida
                $data = array(
                    'status'    => 'error',
                    'code'      => 404,
                    'message'   => "El usuario no se a creado",
                    'errors'    => $validate->errors()
                );
            } else {
                //Validacion pasada Correctamente
                //Cifrar la contraseña
                $pwd = hash('sha256', $params->password);


                //Crear usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                //Guardar usuario
                $user->save();
                //mensajes success
                $data = array(
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => "El usuario se ha creado correctamente",
                    'USER'      => $user
                );
            }
        } else {

            $data = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos enviados no son correctos'

            );
        }

        //response()->json para transformar el array en json
        return response()->json($data, $data['code']);
    }

    public function login(Request $request)  {

        $jwtAuth = new \JwtAuth();
        //Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Validar los datos recividos
        $validate = \Validator::make($params_array, [
            'email'         => 'required|email',
            'password'      => 'required'
        ]);

        if($validate->fails()){
            $signup = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'El usuario no esta identificado',
                'errors'    => $validate->errors()
            );
        }else{
            //Cifrar la contraseña
            $pwd = hash('sha256', $params->password);
            //Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);
            if(isset($params->gettoken)){
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }       
        return response()->json($signup, 200);
    }

    public function update(Request $request){

        //Comprobar si el usuario esta identificado

        //recoger token desde el header
        $token = $request->header('authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Recoger los datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if($checkToken && !empty($params)){
            //Para Actualizar Usuario 
            
            //Rescatar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            //Validar los datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                //Comprobar si el usuario ya existe con unique
                'email'     => 'required|email|unique:users,'.$user->sub
            ]);

            //Quitar los datos que no deseeo actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Actualizar los datos en la BD
            $user_update = User::where('id', $user->sub)->update($params_array);
            //devolver array con resultado
            $data = array(
                'code'      => 400,
                'status'    => 'success',
                'message'   => $user,
                'changes'   => $params_array
            );
            //echo "<h1>Login Correcto</h1";
        }else{
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'El usuario no esta identificado'
            );            
            //echo "<h1>Login incorrecto</h1>";
        }

        return response()->json($data, $data['code']);
    }


    public function upload(Request $request){

        //Recoger los datos de la petición (imagen)
        $image = $request->file('file0');

        //Validacion de imagen
        $validate = \Validator::make($request->all(), [
            'file0'     =>'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //subir y guardar imagen 
        if($image || $validate->fails()){

            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al subir imagen'        
            );
        }else{
            //time() sirve para evitar que la imagen se guarde mas de una vez
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'image'     => $image_name
            );

        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename){

        $isset = \Storage::disk('users')->exists($filename);
        if($isset){
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        }else{
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'La imagen no existe'        
            );
        }
        return response()->json($data, $data['code']);
    }

    public function detail($id) {

        //Buscar el usuario por el id enviado por la url
        $user = User::find($id);
        //conprobar si es un objeto
        if(is_object($user)) {
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'user'      =>$user
            );
        }else{
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'user'      => 'El usuario no existe'
            );
        }

        return response()->json($data, $data['code']);

    }
    
}