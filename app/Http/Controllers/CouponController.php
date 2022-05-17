<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $perPage = \request('per_page') ?? 15;
        $query = Coupon::query();
        $coupons = $query->paginate($perPage);

        return apiResponse(true, null, $coupons);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:coupons',
            'min_amount' => 'required|integer|min:5',
            'max_cashback' => 'required|integer|min:0',
            'cashback_percentage' => 'required|integer|min:0|max:100',
            'usage_limit' => 'required|integer|min:0',
            'limit_per_user' => 'required|integer|min:0',
            'expire_at' => [Rule::requiredIf($request->usage_limit > 0), 'date'],
            'wallet_type' => 'required|in:MAIN,BONUS',
            'is_active' => 'required|boolean'
        ]);

        Coupon::query()->create($data);

        return apiResponse(true, 'Coupon created.');
    }

    /**
     * Display the specified resource.
     *
     * @param Coupon $coupon
     * @return Response
     */
    public function show(Coupon $coupon)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return Response
     */
    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code' => 'required|unique:coupons,id,' . $coupon->id,
            'min_amount' => 'required|integer|min:5',
            'max_cashback' => 'required|integer|min:0',
            'cashback_percentage' => 'required|integer|min:0|max:100',
            'usage_limit' => 'required|integer|min:0',
            'limit_per_user' => 'required|integer|min:0',
            'expire_at' => [Rule::requiredIf($request->usage_limit > 0), 'date'],
            'wallet_type' => 'required|in:MAIN,BONUS',
            'is_active' => 'required|boolean'
        ]);

        if ($data['usage_limit'] == 0) {
            $data['expire_at'] = null;
        }

        $coupon->update($data);

        return apiResponse(true, 'Coupon updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Coupon $coupon
     * @return Response
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return apiResponse(true, 'Coupon removed.');
    }
}
