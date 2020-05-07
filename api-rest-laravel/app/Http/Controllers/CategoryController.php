<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;

class CategoryController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth',['except' => ['index', 'show']]);
    }

    public function index()
 {
    $categories = Category::all();

    return response()->json([
        'code'      => 200,
        'status'    => 'success',
        'message'   => $categories
    ]);
 }

 public function show($id) {
    //Obtener las categoriaas desde el modelo con el ID
    $category = Category::find($id);
    //comprobar si es un objeto
    if(is_object($category)) {
        $data = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => $category
        ];
    }else{
        $data = [
            'code'      => 400,
            'status'    => 'error',
            'message'   => 'La categoria no existe'
        ];
    }
    return response()->json($data, $data['code']);
 }

 public function store(Request $request) {
     //Recoger los datos por post
     $json = $request->input('json', null);
     $params_array = json_decode($json, true);

     // validar los datos
     if(!empty($params_array)) {
         $validate = \Validator::make($params_array, [
             'name'      => 'required'
         ]);
          // Guardar la categoria
             if($validate->fails()){
                 $data = [
                     'code'      => 400,
                     'status'    => 'error',
                     'message'   => 'No se guardo la categoria'
                 ];
             }else{
     
                 $category = new Category();
                 $category->name = $params_array['name'];
                 $category->save();
     
                 $data = [
                     'code'      => 200,
                     'status'    => 'success',
                     'message'   => $category
                 ];
                 
                
             }

     }else{
        $data = [
            'code'      => 400,
            'status'    => 'error',
            'message'   => 'No has enviado ninguna categoria'
        ];
     }
     // Devolver el resultado
     return response()->json($data, $data['code']);
     

 }

 public function update($id, Request $request) {

    //Recoger datos que vienen por post
    $json = $request->input('json', null);
    $params_array = json_decode($json, true);

    if (!empty($params_array)){
    //Validar los datos
    $validate = \Validator::make($params_array, [
        'name'      => 'required'
    ]);
    //Quitar lo que no quiero actualizar
        unset($params_array['id']);
        unset($params_array['created_at']);
    //Actualizar el registro (categoria)
    $category = Category::where('id', $id)->update($params_array);

    $data = [
        'code'      => 200,
        'status'    =>'success',
        'category'  => $params_array
    ];
   
    }else{
        $data = [
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'No has enviado ninguna categoria'
            ];
    }
    //devolver el resutado
    return response()->json($data, $data['code']);
 }
 

}