<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use App\Http\Resources\Author as AuthorResource;
use App\Http\Resources\AuthorCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthorController extends Controller
{
    public function index(Request $request){
        $limit = $request->limit?$request->limit:10;
        $results = new Author;

        if($request->keyword)
            $results = $results->where('name', 'LIKE', '%'.$request->keyword.'%');

        $order_field = ($request->sort_field)?$request->sort_field:'updated_at';
        $order_dir = ($request->sort_order)?$request->sort_order:'DESC';

        $results = $results->orderBy($order_field, $order_dir)->paginate($limit);
        return new AuthorCollection($results);
    }

    public function store(AuthorRequest $request){

        $request->validated();
        $author = new Author();
        $author->fill($request->all());
        $author->save();

        return new AuthorResource($author);
    }

    public function update(AuthorRequest $request){

        $request->validated();
        $author = $this->checkExist($request->all());
        if($author){
            $author->update($request->all());
            return new AuthorResource($author);
        }
        else
            throw new ModelNotFoundException("Invalid Author");
    }

    protected function checkExist($data){
        if(!empty($data['id']))
            return Author::find($data['id']);
        else
            return false;

    }

    public function view($id){
        if($author = Author::find($id)){
            return new AuthorResource($author);
        }
        else
            throw new ModelNotFoundException("Invalid Author");
    }

    public function delete(Request $request){
        $author = $this->checkExist($request->all());
        if($author){
            return $author->delete();
        }
        else
            throw new ModelNotFoundException("Invalid Author");
    }
}
