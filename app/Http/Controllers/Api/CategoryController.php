<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Category;
use \App\Notifications\CategoryChanged;
use \App\Models\User;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query();
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        if ($request->has('q')) {
            $categories = $categories->search($request->get('q'));
        }

        if ($request->has('is_published')) {
            $categories = $categories->where('is_published', $request->get('is_published'));
        }

        $categories = $categories->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'error' => false,
            'data' => $categories,
        ]);
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'error' => true,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'error' => false,
            'data' => $category,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'is_published' => 'required',
        ]);

        $error = false;
        $message = 'Category created successfully.';
        try {
            $category = Category::create($request->all());
            $users = User::all();
            $changedBy = auth()->user();
            foreach ($users as $user) {
                $user->notify(new CategoryChanged($user, $changedBy, $category, 'created'));
            }
            return response()->json([
                'error' => false,
                'message' => 'Category created successfully.',
            ]);
        } catch (\Exception $e) {
            $error = true;
            $message = $e->getMessage();
            return response()->json([
                'error' => $error,
                'message' => $message,
            ]);
        }

    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'is_published' => 'required',
        ]);

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'error' => true,
                'message' => 'Category not found.',
            ], 404);
        }

        $error = false;
        $message = 'Category updated successfully.';
        try {
            $cat = $category;
            $category->update($request->all());
            $users = User::all();
            $changedBy = auth()->user();
            foreach ($users as $user) {
                $user->notify(new CategoryChanged($user, $changedBy, $cat, 'updated'));
            }
        } catch (\Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }

        return response()->json([
            'error' => $error,
            'message' => $message,
        ]);
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'error' => true,
                'message' => 'Category not found.',
            ],404);
        }
        $cat = $category;
        $category->delete();
        $users = User::all();
        $changedBy = auth()->user();
        foreach ($users as $user) {
            $user->notify(new CategoryChanged($user, $changedBy, $cat, 'deleted'));
        }

        return response()->json([
            'error' => false,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
