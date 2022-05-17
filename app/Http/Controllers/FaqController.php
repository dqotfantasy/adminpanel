<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $query = Faq::query();
        $perPage = \request('per_page') ?? 15;
        $data = $query->paginate($perPage);
        return apiResponse(true, null, ['faqs' => $data]);
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
            'title' => 'required|min:5|unique:faqs',
            'description' => 'required|min:5'
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        Faq::query()->create($validator->validated());

        return apiResponse(true, 'FAQ added.');
    }

    /**
     * Display the specified resource.
     *
     * @param Faq $faq
     * @return Response
     */
    public function show(Faq $faq)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Faq $faq
     * @return Response
     */
    public function update(Request $request, Faq $faq)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:5|unique:faqs,id,' . $faq->id,
            'description' => 'required|min:5'
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $faq->update($validator->validated());

        return apiResponse(true, 'FAQ updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Faq $faq
     * @return Response
     * @throws Exception
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        return apiResponse(true, 'FAQ removed.');
    }
}
