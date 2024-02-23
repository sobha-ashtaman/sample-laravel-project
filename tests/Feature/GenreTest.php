<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Genre;
use Illuminate\Testing\Fluent\AssertableJson;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_an_error_if_mandatory_fields_not_set(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('api/genres/store', []);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors'=>['name']]);
    }

    public function test_should_return_an_error_if_genre_name_is_not_unique_in_store(){
        $user = User::factory()->create();
        $genre1 = Genre::factory()->create(['name'=>'Not unique genre', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $genre2 = $this->actingAs($user)->postJson('api/genres/store', ['name'=>$genre1->name]);
        $genre2->assertStatus(422);
        $genre2->assertJsonStructure(['message', 'errors'=>['name']]);
    }

    public function test_should_return_success_if_everything_okay_in_store(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('api/genres/store', ['name'=>'Genre Name', 'description'=>'Genre Description']);
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.name', 'Genre Name')->etc();
        });
    }

    public function test_should_not_return_an_error_in_case_of_same_name_in_update(){
        $user = User::factory()->create();
        $genre1 = Genre::factory()->create(['name'=>'Unique genre 1', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $genre1_update = $this->actingAs($user)->postJson('api/genres/update', ['id'=>$genre1->id, 
                'name'=> $genre1->name]);
        $genre1_update->assertStatus(200);
    }

    public function test_should_return_an_error_if_genre_trying_to_update_is_not_exist(){
        $user = User::factory()->create();
        $genre = $this->actingAs($user)->postJson('api/genres/update', ['id'=>1, 'name'=>'Not existing genre']);
        $genre->assertStatus(404);
    }

    public function test_should_return_success_if_everything_okay_in_update(){
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name'=>'Genre Name', 'description'=>'Genre Description', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->postJson('api/genres/update', ['id'=>$genre->id, 'name'=>'Genre Name Edit', 'description'=>'Genre Description Edit']);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.name', 'Genre Name Edit')->etc();
        });
    }

    public function test_should_return_a_paginated_response_while_listing(){
        $user = User::factory()->create();
        Genre::factory()->count(25)->create(['created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->get('api/genres');
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('meta.current_page', 1)->where('meta.total', 25)->where('meta.last_page', 3)->etc();
        });
    }

    public function test_should_return_correct_last_page_if_manually_send_different_limit_per_page(){
        $user = User::factory()->create();
        Genre::factory()->count(25)->create(['created_by'=>$user->id, 'updated_by'=>$user->id]);
        $url = 'api/genres?limit=20';
        $response = $this->actingAs($user)->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('meta.total', 25)->where('meta.last_page', 2)->etc();
        });
    }

    public function test_should_return_filtered_result_while_passing_a_keyword_attribute(){
        $user = User::factory()->create();
        for($i=1; $i<=10; $i++){
            Genre::factory()->create(['name'=>'sortable'.$i, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        }
        for($i=1; $i<=10; $i++){
            Genre::factory()->create(['name'=>'test'.$i, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        }
        
        $url = 'api/genres?keyword=sort';
        $response = $this->actingAs($user)->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('meta.total', 10)->etc();
        });
    }

    public function test_should_return_a_sorted_result_while_passing_sort_field_and_sort_order_attributes(){
        $user = User::factory()->create();
        foreach(['a', 'b', 'c', 'd', 'e'] as $val){
            Genre::factory()->create(['name'=>$val, 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        }
        
        $url = 'api/genres?sort_field=name&sort_order=ASC';
        $response = $this->actingAs($user)->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->has('data')->where('data.0.name', 'a')->where('data.1.name', 'b')->where('data.2.name', 'c')->etc();
        });
    }

    public function test_should_return_404_response_while_trying_to_access_non_existing_record_while_viewing(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('api/genres/view/1');
        $response->assertStatus(404);
    }

    public function test_should_return_success_if_everything_okay_in_viewing(){
        $user = User::factory()->create();
        $genres = Genre::factory()->create(['name'=>'Genre Name', 'description'=>'Genre Description', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->get('api/genres/view/'.$genres->id);
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson(function(AssertableJson $json){
            $json->where('data.name', 'Genre Name')->etc();
        });
    }

    public function test_should_return_404_response_while_trying_to_delete_non_existing_record(){
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('api/genres/delete', ['id'=>1]);
        $response->assertStatus(404);
    }

    public function test_should_return_success_if_everything_okay_in_delete(){
        $user = User::factory()->create();
        $genres = Genre::factory()->create(['name'=>'Genre Name', 'description'=>'Genre Description', 'created_by'=>$user->id, 'updated_by'=>$user->id]);
        $response = $this->actingAs($user)->postJson('api/genres/delete', ['id'=>$genres->id]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, Genre::count());       
    }
}
