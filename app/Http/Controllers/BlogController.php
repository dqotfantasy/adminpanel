<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $query = Blog::query();
        $perPage = \request('per_page') ?? 15;
        $data = $query->paginate($perPage);
        $currentURL = url()->current();
        $currentURL=str_replace("api/blogs","",$currentURL);
        //return url('storage/images').'/blogs/ilcWbbn4UJK5vicv6GTdfeTl4Wv2RWsFvHj7ulqO.jpg';
        return apiResponse(true, '', ['blogs' => $data,'currentURL'=>$currentURL]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'meta_title' => 'required',
            'meta_description' => 'required',
            'meta_keyword' => 'required',
            'description' => 'required',
            // 'status'=>'required',
            'photo' => 'required'
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }


        $blog = new Blog();

        if ($request->hasFile('photo')) {
            $blog->photo=$imageName = time().'.'.$request->photo->extension();
            $request->photo->move(public_path('blogs'), $imageName);
            //$blog->photo = $request->file('photo')->store('blogs');
        }

        $blog->slug = $this->slugify($request->title);
        $blog->title = $request->title;
        $blog->meta_title = $request->meta_title;
        $blog->meta_description = $request->meta_description;
        $blog->meta_keyword = $request->meta_keyword;
        $blog->description = $request->description;
        $blog->status = $request->status ? $request->status : '1';
        $blog->save();

        return apiResponse(true, 'Blog Added.');
    }

    /**
     * Display the specified resource.
     *
     * @param Blog $page
     * @return Response
     */
    public function show(Blog $blog)
    {
        return apiResponse(true, null, $blog);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Blog $page
     * @return Response
     */
    public function update(Request $request, Blog $blog)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'meta_title' => 'required',
            'meta_description' => 'required',
            'meta_keyword' => 'required',
            'description' => 'required',
            // 'status'=>'required',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        if ($request->hasFile('photo')) {
            if(file_exists(public_path('blogs').'/'.$blog->photo)){
                unlink(public_path('blogs').'/'.$blog->photo);
            }
            $blog->photo=$imageName = time().'.'.$request->photo->extension();
            $request->photo->move(public_path('blogs'), $imageName);

            // Storage::delete($blog->photo);
            // $blog->photo = $request->file('photo')->store('blogs');
        }

        $blog->slug = $this->slugify($request->title);
        $blog->title = $request->title;
        $blog->meta_title = $request->meta_title;
        $blog->meta_description = $request->meta_description;
        $blog->meta_keyword = $request->meta_keyword;
        $blog->description = $request->description;
        $blog->status = $request->status ? $request->status : '1';
        $blog->save();

        return apiResponse(true, 'Blog updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Blog $page
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        $blog = Blog::find($id);
        $blog->delete();
        if(file_exists(public_path('blogs').'/'.$blog->photo)){
            unlink(public_path('blogs').'/'.$blog->photo);
        }
        //Storage::delete($blog->photo);
        return apiResponse(true, 'Blog Deleted.');
    }

    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}
