<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $query = Banner::query();
        $perPage = \request('per_page') ?? 15;
        $getCoupon = Coupon::query()->where('is_active',1)->pluck('code');
        $couponArray=[];
        foreach($getCoupon as $val){
            $couponArray[]=['id'=>$val,'name'=>$val];
        }

        $data = $query->paginate($perPage);
        return apiResponse(true, '', ['banners' => $data,'coupon' => $couponArray]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {

        // $data = $request->validate([
        //     'type' => 'required',
        //     'photo' => 'required|file|image',
        //     //'is_active' => 'required|boolean'
        // ]);

        $data['type'] = \request('type');
        $data['value'] = \request('offer');
        if(empty($data['value'])){
            $data['value'] = \request('competition_id');
        }
        if(empty($data['value'])){
            $data['value'] = \request('fixture_id');
        }
        $mode = \request('mode');

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->storePublicly('banners', 's3');
            $imageFile = Storage::disk('s3')->url($path);
            $data['image'] = $imageFile;
        }

        Banner::query()->create($data);

        return apiResponse(true, 'Banner added.');
    }

    /**
     * Display the specified resource.
     *
     * @param Banner $banner
     * @return Response
     */
    public function show(Banner $banner)
    {
        return apiResponse(true, null, $banner);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Banner $banner
     * @return Response
     */
    public function update(Request $request, Banner $banner)
    {
        // $data = $request->validate([
        //     'photo' => 'nullable|file|image',
        // ]);
        $data['type'] = \request('type');
        $data['value'] = \request('offer');
        if(empty($data['value'])){
            $data['value'] = \request('competition_id');
        }
        if(empty($data['value'])){
            $data['value'] = \request('fixture_id');
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->storePublicly('banners', 's3');
            $imageFile = Storage::disk('s3')->url($path);

            if (isset($banner->image)) {
                $path = $banner->image;
                if ($path) {
                    Storage::disk('s3')->delete("banners/" . Str::after($path, "/banners"));
                }
            }

            $data['image'] = $imageFile;
        }

        unset($data['photo']);

        $banner->update($data);

        return apiResponse(true, 'Banner updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Banner $banner
     * @return Response
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();
        return apiResponse(true, 'Banner removed.');
    }
}
