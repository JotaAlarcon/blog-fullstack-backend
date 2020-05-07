<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    //Funcion para solicitar autenticacion
    public function __construct(){
        $this->middleware('api.auth',['except' => [
            'index',
            'show', 
            'getImage',
            'getPostsByCategory',
            'getPostsByUser'
        ]]);
    }

    public function index() {
        //rescatar todos los datos
        $posts = Post::all()->load('category');

        return response()->json([
            'code'      => 200,
            'status'    => 'success',
            'posts'     => $posts
        ], 200);
    }

    public function show($id){
        //busca una entrada (post) con un id determinado
        $posts = Post::find($id)->load('category');

        if(is_object($posts)){
            $data = [
            'code'      => 200,
            'status'    => 'success',
            'posts'     => $posts
            ];
        }else{
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'posts'     => 'La entrada no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        //recoger datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        

        if(!empty($params_array)){

            //Conseguir el usuario identificado
            $user =$this->getIdentity($request);
            
            // validar los datos
            $validate = \Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required',
                'image'         => 'required'
            ]);

            if($validate->fails()){
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   =>  'No se ha guardado correctamente el articulo, faltan datos'
            );
            }else{
                //guardar el articulo
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                $data = [
                    'code'      => 200,
                    'status'    => 'success',
                    'post'      => $post
                ];
            }            
    
            //devolver la repuesta
        }else{
            $data = [
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Envia los datos correctamente'
            ];

        }
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request){
        //Recoger los datos que llegan por POST
        // en la funcion input('json', null), json es el parametro que esperamos
        //recivir y sera null en caso de que no se encuentre 'json'
        $json = $request->input('json', null);
        //json decode recive primero el json a decodificar
        //y el segundo parametro sera true para transformarlo en array
        $params_array = json_decode($json, true);
        //errores por defecto
        $data = array(
            'code'      => 400,
            'status'   => 'error',
            'post'      => 'Datos enviados incorrectamente, o usuario no identificado'
        );

        if(!empty($params_array)){        
            //Validar los datos
            //Validator::make() recive como parametro el array($params_array)
            //y como segundo parametro un array con las validaciones que queremos
            $validate = \Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required'
            ]);

            if($validate->fails()){
                $data['errors'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            //Quitar lo que no queremos actualizar
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['category_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            //Conseguir usuario identificado
            $user =$this->getIdentity($request);
            //Buscar el registro
            $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

            if(!empty($post)){

                $where = [
                    'id'        =>$id,//evalua el id del articulo que viene por url
                    'user_id'   => $user->sub//evalua si el usuario es quien creó el articulo
                ];
            
                //Actualizar el registro
                $post = Post::updateOrCreate($where, $params_array);
                
                $data = array(
                    'code'      => 200,
                    'status'    => 'success',
                    'post'      => $post,
                    'changes'   => $params_array
                );

            }

            //Actualizar el registro
            /*$where = [
                'id'        =>$id,//evalua el id del articulo que viene por url
                'user_id'   => $user->sub//evalua si el usuario es quien creó el articulo
            ];
            //actualiza el registro
            $post = Post::updateOrCreate($where, $params_array);
            */
            //Devolver resultado
            

        }   

        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request){

        //Conseguir el usuario identificado
        $user =$this->getIdentity($request);

        //Conseguir el registro
        $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

        if(!empty($post)){
        //Borrar el registro
        $post->delete();
        //Devolver resultado
        $data = [
            'code'      => 200,
            'status'    => 'success',
            'post'      => $post
        ];
        }else{
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'el articulo no existe, ó el usuario no esta identificado'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request){
        //Recoger la imagen de la peticion
        $image = $request->file('file0');

        //Validar la imagen
        $validate = \Validator::make($request->all(), [
            'file0'     =>'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Guardar la imagen
        if(!$image || $validate->fails()){
            $data = [
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al subir la imagen'
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code'      => 200,
                'status'    => 'success',
                'image'     => $image_name
            ];
        }
        //Devolver un resultado
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
        if($isset){
            //conseguir la imagen
            $file = \Storage::disk('images')->get($filename);
            //devolver la imagen
            return new Response($file, 200);
        }else{
            //mostrar posible error
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'La imagen no existe'
            ];
        }
        return response()->json($data, $data['code']);

    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
            'status'    =>'success ',
            'post'      => $posts     
        ],200);
    }

    public function getPostsByUser($id){
        $posts = Post::where('user_id', $id)->get();
        return response()->json([
            'status'    => 'success',
            'post'      => $posts
        ], 200);
    }





    //FUNCION PARA OBTENER EL USUARIO
    private function getIdentity($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }
}
