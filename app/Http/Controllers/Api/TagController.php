<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tags = $request->user()
            ->tags()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $request->user()->tags()->create($request->validated());

        return (new TagResource($tag))->response()->setStatusCode(201);
    }

    public function show(Tag $tag): TagResource
    {
        Gate::authorize('view', $tag);

        return new TagResource($tag);
    }

    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $tag->update($request->validated());

        return new TagResource($tag);
    }

    public function destroy(Tag $tag): Response
    {
        Gate::authorize('delete', $tag);
        $tag->delete();

        return response()->noContent();
    }
}
