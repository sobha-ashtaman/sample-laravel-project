<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Http\Resources\BookResource;
use App\Http\Resources\BookResourceCollection;
use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookController extends Controller
{
    public function index(Request $request){
        $limit = $request->limit?$request->limit:12;
        $results = new Book;

        if($request->keyword)
            $results = $results->where('title', 'LIKE', '%'.$request->keyword.'%');

        $order_field = ($request->sort_field)?$request->sort_field:'updated_at';
        $order_dir = ($request->sort_order)?$request->sort_order:'DESC';

        $results = $results->orderBy($order_field, $order_dir)->paginate($limit)->withQueryString();
        return new BookResourceCollection($results);
    }

    public function store(BookRequest $request){
        $request->validated();
        $data = $request->all();
        if($request->hasFile('cover_image') && $request->file('cover_image')->isValid())
            $data['cover_image'] = $this->fileUpload($request->file('cover_image'), 'books');

        $book = new Book();
        $book->fill($data);
        $book->save();

        if($request->has('genres')){
            $this->save_genres($book, $request->genres);
        }

        return new BookResource($book);
    }

    public function update(BookRequest $request){
        $request->validated();
        $book = $this->checkExist($request->all());
        if($book){
            $data = $request->all();
            if($request->hasFile('cover_image') && $request->file('cover_image')->isValid())
            {
                if($book->cover_image)
                    @unlink(public_path($book->cover_image));

                $data['cover_image'] = $this->fileUpload($request->file('cover_image'), 'books');
            }
            $book->update($data);
            if($request->has('genres')){
                $this->save_genres($book, $request->genres);
            }
            return new BookResource($book);
        }
        else
            throw new ModelNotFoundException("Invalid Book.");
    }

    protected function save_genres($book, $genres): void
    {
        $genre_array = [];
        if($genres)
            foreach($genres as $genre){
                $genre_array[$genre] = ['created_by'=>auth()->user()->id, 'updated_by'=>auth()->user()->id, 'created_at'=>date('Y-m-d H:i:s')];
            }
        $book->genres()->sync($genre_array);
    }

    protected function checkExist($data){
        if(!empty($data['id']))
            return Book::find($data['id']);
        else
            return false;
    }

    public function view($id){
        if($book = Book::with(['genres'])->where('id', $id)->first()){
            return new BookResource($book);
        }
        else
            throw new ModelNotFoundException("Invalid Book.");
    }

    public function delete(Request $request){
        $book = $this->checkExist($request->all());
        if($book){
            $book->genres()->detach();
            if($book->cover_image)
                @unlink(public_path($book->cover_image));
            return $book->delete();
        }
        else
            throw new ModelNotFoundException("Invalid Book.");
    }
}
