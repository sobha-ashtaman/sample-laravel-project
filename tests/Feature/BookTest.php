<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Book;
use App\Models\User;
use App\Models\Author;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\Genre;
use Illuminate\Support\Facades\Storage;
use File;

class BookTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_should_return_an_error_if_mandatory_fields_not_set(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('api/books/store', []);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors'=>['title', 'slug', 'author_id']]);
    }

    public function test_should_return_an_error_if_slug_is_not_unique_in_store(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book1 = Book::factory()->create(['title'=>'Not unique book', 'slug'=>'not-unique-book', 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book2 = $this->actingAs($user)->postJson('api/books/store', ['title'=>$book1->title, 'slug'=>$book1->slug, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book2->assertStatus(422);
        $book2->assertJsonStructure(['message', 'errors'=>['slug']]);
    }

    public function test_should_return_an_error_if_try_to_save_a_non_existing_author(){
        $user = User::factory()->create();
        $book = $this->actingAs($user)->postJson('api/books/store', ['title'=>'Book Title', 'slug'=>'unique-book-slug', 'author_id'=>999999, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book->assertStatus(422);
        $book->assertJsonStructure(['message', 'errors'=>['author_id']]);
    }

    public function test_should_return_an_error_if_try_to_aupload_a_non_image_as_cover_photo(){
        $user = User::factory()->create();
        $max_upload_limit = (int)ini_get("upload_max_filesize")*1024;
        $file_size = rand(1, $max_upload_limit);
        $wrong_cover_image = UploadedFile::fake()->create('document.pdf', $file_size);
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book = $this->actingAs($user)->postJson('api/books/store', ['title'=>'Book Title', 'slug'=>'unique-book-slug', 
        'cover_image'=>$wrong_cover_image, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book->assertStatus(422);
        $book->assertJsonStructure(['message', 'errors'=>['cover_image']]);
    }

    public function test_should_return_an_error_if_try_to_upload_an_image_with_size_grater_than_allowed_size(){
        $user = User::factory()->create();
        $max_upload_limit = (int)ini_get("upload_max_filesize")*1024;
        $file_size = $max_upload_limit+1;
        $wrong_cover_image = UploadedFile::fake()->create('image.jpg', $file_size);
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book = $this->actingAs($user)->postJson('api/books/store', ['title'=>'Book Title', 'slug'=>'unique-book-slug', 
        'cover_image'=>$wrong_cover_image, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book->assertStatus(422);
        $book->assertJsonStructure(['message', 'errors'=>['cover_image']]);
    }

    public function test_should_return_a_success_if_everything_is_oaky_without_genres(){
        $user = User::factory()->create();
        $max_upload_limit = (int)ini_get("upload_max_filesize")*1024;
        $file_size = rand(1, $max_upload_limit);
        $cover_image = UploadedFile::fake()->create('image.png', $file_size);
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->postJson('api/books/store', ['title'=>'Book Title', 'slug'=>'unique-book-slug', 
        'cover_image'=>$cover_image, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response->assertStatus(201);
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.title', 'Book Title')->etc();
        });
    }

    public function test_should_return_a_success_if_everything_is_oaky_with_genres(){
        $user = User::factory()->create();
        list($cover_image, $genres, $author) = $this->book_save_assets($user);
        $response = $this->actingAs($user)->postJson('api/books/store', ['title'=>'Book Title', 'slug'=>'unique-book-slug', 
        'cover_image'=>$cover_image, 'author_id'=>$author->id, 'genres'=>$genres, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response_data = json_decode($response->getContent());
        $response->assertStatus(201);
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.title', 'Book Title')
                ->has('data.author')
                ->has('data.genres', 3)->etc();
        });
        Storage::disk('public')->assertExists($response_data->data->cover_image);
    }

    public function test_should_not_return_an_error_in_case_of_same_slug_in_update(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book1 = Book::factory()->create(['title'=>'Not unique book', 'slug'=>'not-unique-book', 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book1_update = $this->actingAs($user)->postJson('api/books/update', ['id'=>$book1->id, 'title'=>$book1->title, 'slug'=>$book1->slug, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book1_update->assertStatus(200);
    }

    protected function book_save_assets($user){
        //file upload
        $max_upload_limit = (int)ini_get("upload_max_filesize")*1024;
        $file_size = rand(1, $max_upload_limit);
        $cover_image = UploadedFile::fake()->create('image.png', $file_size);
        //genres
        Genre::factory()->count(3)->create(['created_by'=>$user->id, 'updated_by'=>$user->id]);
        $genres = Genre::get()->pluck('id')->toArray();
        //author
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        return [$cover_image, $genres, $author];
    }

    public function test_should_return_an_error_if_book_trying_to_update_is_not_exist(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->postJson('api/books/update', ['id'=>1, 'title'=>'Book Title Updated', 
        'slug'=>'unique-book-slug-updated', 'cover_image'=>null, 'author_id'=>$author->id, 'genres'=>[], 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response->assertStatus(404);
    }

    public function test_should_return_success_if_everything_okay_in_update(){
        $user = User::factory()->create();
        list($cover_image, $genres, $author) = $this->book_save_assets($user);
        $author_save = Author::factory()->create(['name'=>'Author Save', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book = Book::factory()->create(['title'=>'Book Title', 'slug'=>'unique-book-slug', 
        'cover_image'=>null, 'author_id'=>$author_save->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);

        $response = $this->actingAs($user)->postJson('api/books/update', ['id'=>$book->id, 'title'=>'Book Title Updated', 
        'slug'=>'unique-book-slug-updated', 'cover_image'=>$cover_image, 'author_id'=>$author->id, 'genres'=>$genres, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response_data = json_decode($response->getContent());
        $response->assertStatus(200);
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.title', 'Book Title Updated')->where('data.slug', 'unique-book-slug-updated')
                ->has('data.author')
                ->has('data.genres', 3)->etc();
        });
        Storage::disk('public')->assertExists($response_data->data->cover_image);
    }

    public function test_should_return_a_paginated_response_while_listing(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author Save', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        Book::factory()->count(25)->create(['author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->get('api/books');
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('meta.current_page', 1)->where('meta.total', 25)->where('meta.last_page', 3)->etc();
        });
    }

    public function test_should_return_correct_last_page_if_manually_send_different_limit_per_page(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author Save', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        Book::factory()->count(25)->create(['author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $url = 'api/books?limit=20';
        $response = $this->actingAs($user)->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('meta.total', 25)->where('meta.last_page', 2)->etc();
        });
    }

    public function test_should_return_filtered_result_while_passing_a_keyword_attribute(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author Save', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        for($i=1; $i<=10; $i++){
            Book::factory()->create(['title'=>'sortable'.$i, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        }
        for($i=1; $i<=10; $i++){
            Book::factory()->create(['title'=>'test'.$i, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        }
        
        $url = 'api/books?keyword=sort';
        $response = $this->actingAs($user)->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('meta.total', 10)->etc();
        });
    }

    public function test_should_return_a_sorted_result_while_passing_sort_field_and_sort_order_attributes(){
        $user = User::factory()->create();
        $author = Author::factory()->create(['name'=>'Author Save', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        foreach(['a', 'b', 'c', 'd', 'e'] as $val){
            Book::factory()->create(['title'=>$val, 'author_id'=>$author->id, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        }

        $url = 'api/books?sort_field=title&sort_order=ASC';
        $response = $this->actingAs($user)->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('data.0.title', 'a')->where('data.1.title', 'b')->where('data.2.title', 'c')->etc();
        });
    }

    public function test_should_return_404_response_while_trying_to_access_non_existing_record_while_viewing(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('api/books/view/1');
        $response->assertStatus(404);
    }

    public function test_should_return_success_if_everything_okay_in_viewing(){
        $user = User::factory()->create();
        $book = $this->save_book_with_cover_image_and_gernes($user, false);
        $response = $this->actingAs($user)->get('api/books/view/'.$book->id);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.title', 'Book Title')->has('data.genres', 3)->etc();
        });
    }

    public function test_should_return_404_response_while_trying_to_delete_non_existing_record(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('api/books/delete', ['id'=>1]);
        $response->assertStatus(404);
    }

    public function test_should_return_success_if_everything_okay_in_delete(){
        $user = User::factory()->create();
        $book = $this->save_book_with_cover_image_and_gernes($user);
        $response = $this->actingAs($user)->post('api/books/delete', ['id'=>$book->id]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, Book::count());
        $this->assertEquals(0, \DB::table('book_genre')->count());
        Storage::disk('public')->assertMissing($book->cover_image);   
    }

    protected function save_book_with_cover_image_and_gernes($user, $upload_cover=true){
        list($cover_image, $genres, $author) = $this->book_save_assets($user);
        $genre_array = [];
        foreach($genres as $genre){
            $genre_array[$genre] = ['created_by'=>$user->id, 'updated_by'=>$user->id, 'created_at'=>date('Y-m-d H:i:s')];
        }
        $cover_image = null;
        $book = Book::factory()->create(['title'=>'Book Title', 'slug'=>'unique-book-slug', 'author_id'=>$author->id, 'cover_image'=>$cover_image, 
        'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $book->genres()->attach($genre_array);

        return $book;
    }
}
