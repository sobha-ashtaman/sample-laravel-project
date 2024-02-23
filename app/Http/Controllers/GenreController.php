<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use App\Http\Requests\GenreRequest;
use App\Http\Resources\GenreResource;
use App\Http\Resources\GenreResourceCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GenreController extends Controller
{
    public function index(Request $request){
        $limit = $request->limit?$request->limit:10;
        $results = new Genre;

        if($request->keyword)
            $results = $results->where('name', 'LIKE', '%'.$request->keyword.'%');

        $order_field = ($request->sort_field)?$request->sort_field:'updated_at';
        $order_dir = ($request->sort_order)?$request->sort_order:'DESC';

        $results = $results->orderBy($order_field, $order_dir)->paginate($limit);
        return new GenreResourceCollection($results);
    }
    
    public function store(GenreRequest $request){

        $request->validated();
        $genre = new Genre();
        $genre->fill($request->all());
        $genre->save();
        return new GenreResource($genre);
    }

    public function update(GenreRequest $request){

        $request->validated();
        $genre = $this->checkExist($request->all());
        if($genre){
            $genre->update($request->all());
            return new GenreResource($genre);
        }
        else
            throw new ModelNotFoundException("Invalid Genre.");
    }


    protected function checkExist($data){
        if(!empty($data['id']))
            return Genre::find($data['id']);
        else
            return false;

    }

    public function view($id){
        if($genre = Genre::find($id)){
            return new GenreResource($genre);
        }
        else
            throw new ModelNotFoundException("Invalid Genre.");
    }

    public function delete(Request $request){
        $genre = $this->checkExist($request->all());
        if($genre){
            return $genre->delete();
        }
        else
            throw new ModelNotFoundException("Invalid Genre.");
    }
}
