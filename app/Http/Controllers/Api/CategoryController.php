<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Categories
 *
 * APIs for browsing course categories.
 */
class CategoryController extends Controller
{
    /**
     * List Categories
     *
     * Get all active categories with course counts.
     *
     * @unauthenticated
     *
     * @queryParam sort string Sort by field. Example: name
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->active()
            ->withCount('courses')
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Show Category
     *
     * Get a single category by slug.
     *
     * @unauthenticated
     */
    public function show(Category $category): CategoryResource
    {
        $category->loadCount('courses');

        return new CategoryResource($category);
    }
}
