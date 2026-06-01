<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::getAllCategory();
        return view('backend.category.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parent_cats = Category::where('is_parent', 1)->orderBy('title', 'ASC')->get();
        return view('backend.category.create', compact('parent_cats'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
  public function store(Request $request)
{
    $validatedData = $request->validate([
        'title' => 'required|string',
        'summary' => 'nullable|string',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        'status' => 'required|in:active,inactive',
        'is_parent' => 'sometimes|in:1',
        'parent_id' => 'nullable|exists:categories,id',
    ]);

    $slug = generateUniqueSlug($request->title, Category::class);
    $validatedData['slug'] = $slug;
    $validatedData['is_parent'] = $request->input('is_parent', 0);

    if ($request->hasFile('photo')) {

        $image = $request->file('photo');

        $manager = new ImageManager(new Driver());

        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        $manager->read($image)
            ->resize(300, 300)
            ->save(public_path('upload/category/' . $name_gen));

        $validatedData['photo'] = 'upload/category/' . $name_gen;
    }

    $category = Category::create($validatedData);

    return redirect()->route('category.index')->with(
        $category ? 'success' : 'error',
        $category
            ? 'Category successfully added'
            : 'Error occurred, Please try again!'
    );
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Implement if needed
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $parent_cats = Category::where('is_parent', 1)->get();
        return view('backend.category.edit', compact('category', 'parent_cats'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
  public function update(Request $request, $id)
{
    $category = Category::findOrFail($id);

    $validatedData = $request->validate([
        'title' => 'required|string',
        'summary' => 'nullable|string',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        'status' => 'required|in:active,inactive',
        'is_parent' => 'sometimes|in:1',
        'parent_id' => 'nullable|exists:categories,id',
    ]);

    $validatedData['is_parent'] = $request->input('is_parent', 0);

    // Update slug if title changes
    $validatedData['slug'] = generateUniqueSlug(
        $request->title,
        Category::class,
        $category->id
    );

    if ($request->hasFile('photo')) {

        // Delete old image
        if ($category->photo && file_exists(public_path($category->photo))) {
            unlink(public_path($category->photo));
        }

        $image = $request->file('photo');

        $manager = new ImageManager(new Driver());

        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        $manager->read($image)
            ->resize(300, 300)
            ->save(public_path('upload/category/' . $name_gen));

        $validatedData['photo'] = 'upload/category/' . $name_gen;
    }

    $status = $category->update($validatedData);

    return redirect()->route('category.index')->with(
        $status ? 'success' : 'error',
        $status
            ? 'Category successfully updated'
            : 'Error occurred, Please try again!'
    );
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $child_cat_id = Category::where('parent_id', $id)->pluck('id');

        $status = $category->delete();

        if ($status && $child_cat_id->count() > 0) {
            Category::shiftChild($child_cat_id);
        }

        $message = $status
            ? 'Category successfully deleted'
            : 'Error while deleting category';

        return redirect()->route('category.index')->with(
            $status ? 'success' : 'error',
            $message
        );
    }

    /**
     * Get child categories by parent ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getChildByParent(Request $request)
    {
        $category = Category::findOrFail($request->id);
        $child_cat = Category::getChildByParentID($request->id);

        if ($child_cat->count() <= 0) {
            return response()->json(['status' => false, 'msg' => '', 'data' => null]);
        }

        return response()->json(['status' => true, 'msg' => '', 'data' => $child_cat]);
    }
}
