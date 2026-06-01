<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Category;

use App\Models\Brand;

use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::getAllProduct();
        return view('backend.product.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $brands = Brand::get();
        $categories = Category::where('is_parent', 1)->get();
        return view('backend.product.create', compact('categories', 'brands'));
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
        'summary' => 'required|string',
        'description' => 'nullable|string',
        'photo' => 'required|image|mimes:jpg,jpeg,png,gif,webp',
        'size' => 'nullable',
        'stock' => 'required|numeric',
        'cat_id' => 'required|exists:categories,id',
        'brand_id' => 'nullable|exists:brands,id',
        'child_cat_id' => 'nullable|exists:categories,id',
        'is_featured' => 'sometimes|in:1',
        'status' => 'required|in:active,inactive',
        'condition' => 'required|in:default,new,hot',
        'price' => 'required|numeric',
        'discount' => 'nullable|numeric',
    ]);

    // Generate slug
    $validatedData['slug'] = generateUniqueSlug(
        $request->title,
        Product::class
    );

    // Featured checkbox
    $validatedData['is_featured'] = $request->input('is_featured', 0);

    // Sizes
    $validatedData['size'] = $request->has('size')
        ? implode(',', $request->size)
        : '';

    // Upload image
    if ($request->hasFile('photo')) {

        $image = $request->file('photo');

        $manager = new ImageManager(new Driver());

        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        $manager->read($image)
            ->resize(600, 600)
            ->save(public_path('upload/product/' . $name_gen));

        $validatedData['photo'] = 'upload/product/' . $name_gen;
    }

    $product = Product::create($validatedData);

    return redirect()->route('product.index')->with(
        $product ? 'success' : 'error',
        $product
            ? 'Product Successfully added'
            : 'Please try again!!'
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
        $brands = Brand::get();
        $product = Product::findOrFail($id);
        $categories = Category::where('is_parent', 1)->get();
        $items = Product::where('id', $id)->get();

        return view('backend.product.edit', compact('product', 'brands', 'categories', 'items'));
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
    $product = Product::findOrFail($id);

    $validatedData = $request->validate([
        'title' => 'required|string',
        'summary' => 'required|string',
        'description' => 'nullable|string',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        'size' => 'nullable',
        'stock' => 'required|numeric',
        'cat_id' => 'required|exists:categories,id',
        'child_cat_id' => 'nullable|exists:categories,id',
        'is_featured' => 'sometimes|in:1',
        'brand_id' => 'nullable|exists:brands,id',
        'status' => 'required|in:active,inactive',
        'condition' => 'required|in:default,new,hot',
        'price' => 'required|numeric',
        'discount' => 'nullable|numeric',
    ]);

    // Update slug
    $validatedData['slug'] = generateUniqueSlug(
        $request->title,
        Product::class,
        $product->id
    );

    // Featured checkbox
    $validatedData['is_featured'] = $request->input('is_featured', 0);

    // Sizes
    $validatedData['size'] = $request->has('size')
        ? implode(',', $request->size)
        : '';

    // Upload new image
    if ($request->hasFile('photo')) {

        // Delete old image
        if ($product->photo && file_exists(public_path($product->photo))) {
            unlink(public_path($product->photo));
        }

        $image = $request->file('photo');

        $manager = new ImageManager(new Driver());

        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        $manager->read($image)
            ->resize(600, 600)
            ->save(public_path('upload/product/' . $name_gen));

        $validatedData['photo'] = 'upload/product/' . $name_gen;
    }

    $status = $product->update($validatedData);

    return redirect()->route('product.index')->with(
        $status ? 'success' : 'error',
        $status
            ? 'Product Successfully updated'
            : 'Please try again!!'
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
        $product = Product::findOrFail($id);
        $status = $product->delete();

        $message = $status
            ? 'Product successfully deleted'
            : 'Error while deleting product';

        return redirect()->route('product.index')->with(
            $status ? 'success' : 'error',
            $message
        );
    }
}
