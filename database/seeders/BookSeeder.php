<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\User;
use App\Models\Author;
use Illuminate\Http\UploadedFile;
use App\Models\Genre;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create();
        list($cover_image, $genres, $author) = $this->book_save_assets($user);
        $genre_array = [];
        foreach($genres as $genre){
            $genre_array[$genre] = ['created_by'=>$user->id, 'updated_by'=>$user->id, 'created_at'=>date('Y-m-d H:i:s')];
        }
        Book::factory()
            ->count(50)
            ->create(['author_id'=>$author->id, 'cover_image'=>$cover_image, 'created_by'=>$user->id, 'updated_by'=>$user->id])
            ->each(function($book) use($genre_array){
                $book->genres()->attach($genre_array);
            });
        
    }

    protected function book_save_assets($user){
        //file upload
        $cover_image = 'uploads/books/placeholder.png';
        //genres
        Genre::factory()->count(3)->create(['created_by'=>$user->id, 'updated_by'=>$user->id]);
        $genres = Genre::get()->pluck('id')->toArray();
        //author
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        return [$cover_image, $genres, $author];
    }
}
