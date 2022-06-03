<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $Validator = $this->validator($request->all());
        if ($Validator->fails()) {
            return new JsonResponse([
                'errors' => $Validator->messages()->toArray()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdPost = Post::create([
                'title' => $request->get('post_title'),
                'content' => $request->get('post_content')
            ]);

            $response = new JsonResponse([
                'id' => $createdPost->id,
                'url' => route('post.show', $createdPost->id)
            ], 200);

            DB::commit();
        } catch (\Exception $Exception) {
            DB::rollBack();
        }

        return $response;
    }

    public function newPost()
    {
        return view('post.edit', [
            'actionRoute' => route('post.create')
        ]);
    }

    public function show(string $id)
    {
        return view('post.show', [
            'id' => $id
        ]);
    }

    public function list()
    {
        $posts = DB::table('posts')->paginate(30);
        return view('post.list', [
            'posts' => $posts
        ]);
    }

    public function getPostToJson(string $id): JsonResponse
    {
        $wherePost = Post::where('id', (int)$id);
        if ($wherePost->exists()) {
            $takeOne = $wherePost->get()[0];
            $postValues = [
                'post_title' => $takeOne->title,
                'post_content' => $takeOne->content
            ];

            return new JsonResponse($postValues, 200);
        }

        return new JsonResponse([
        ], 422);
    }

    protected function validator(array $data): \Illuminate\Validation\Validator
    {
        $validations = [
            'post_title' => ['required'],
            'post_content' => ['required'],
        ];

        $messages = [
            'post_title.required' => 'タイトルを空にはできません',
            'post_content.required' => 'コンテンツを空にはできません'
        ];

        return Validator::make($data, $validations, $messages);
    }
}
