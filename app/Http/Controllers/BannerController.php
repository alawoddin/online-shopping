<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use App\Models\Banner;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $banners = Banner::latest('id')->paginate(10);
        return view('backend.banner.index', compact('banners'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.banner.create');
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
        'title' => 'required|string|max:50',
        'description' => 'nullable|string',
        'photo' => 'required|image',
        'status' => 'required|in:active,inactive',
    ]);

    $slug = generateUniqueSlug($request->title, Banner::class);

    if ($request->hasFile('photo')) {

        $image = $request->file('photo');

        $manager = new ImageManager(new Driver());

        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        $img = $manager->read($image);

        $img->resize(635, 380)
            ->save(public_path('upload/banner/' . $name_gen));

        $validatedData['photo'] = 'upload/banner/' . $name_gen;
    }

    $validatedData['slug'] = $slug;

    Banner::create($validatedData);

    return redirect()
        ->route('banner.index')
        ->with('success', 'Banner successfully added');
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
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
        $banner = Banner::findOrFail($id);
        return view('backend.banner.edit', compact('banner'));
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
    $banner = Banner::findOrFail($id);

    $validatedData = $request->validate([
        'title' => 'required|string|max:50',
        'description' => 'nullable|string',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        'status' => 'required|in:active,inactive',
    ]);

    if ($request->hasFile('photo')) {

        // Delete old image
        if ($banner->photo && file_exists(public_path($banner->photo))) {
            unlink(public_path($banner->photo));
        }

        $image = $request->file('photo');

        $manager = new ImageManager(new Driver());

        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        $manager->read($image)
            ->resize(635, 380)
            ->save(public_path('upload/banner/' . $name_gen));

        $validatedData['photo'] = 'upload/banner/' . $name_gen;
    }

    // Update slug if title changed
    $validatedData['slug'] = generateUniqueSlug(
        $request->title,
        Banner::class,
        $banner->id
    );

    $status = $banner->update($validatedData);

    return redirect()->route('banner.index')->with(
        $status ? 'success' : 'error',
        $status
            ? 'Banner successfully updated'
            : 'Error occurred while updating banner'
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
        $banner = Banner::findOrFail($id);
        $status = $banner->delete();

        $message = $status
            ? 'Banner successfully deleted'
            : 'Error occurred while deleting banner';

        return redirect()->route('banner.index')->with(
            $status ? 'success' : 'error',
            $message
        );
    }

}
