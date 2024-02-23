<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'author' => new Author($this->author),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'cover_image' => $this->cover_image?asset($this->cover_image):null,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'status' => $this->status,
            'created_by_user' => new User($this->created_user),
            'updated_by_user' => new User($this->updated_user),
            'created_at' => date('d-m-Y h:i A', strtotime($this->created_at)),
            'updated_at' => date('d-m-Y h:i A', strtotime($this->updated_at))
        ];
    }
}
