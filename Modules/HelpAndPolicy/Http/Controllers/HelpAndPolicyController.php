<?php

namespace Modules\HelpAndPolicy\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class HelpAndPolicyController extends Controller
{

    public function __construct()
    {
            $this->middleware('permission:view helpandpolicys')->only('index');
            $this->middleware('permission:create helpandpolicy')->only('create');
            $this->middleware('permission:store helpandpolicy')->only('store');
            $this->middleware('permission:edit helpandpolicy')->only('edit');
            $this->middleware('permission:update helpandpolicy')->only('update');
            $this->middleware('permission:destroy helpandpolicy')->only('destroy');
    }


    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        return view('helpandpolicy::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('helpandpolicy::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            //TODO:STORE FUNCTIONS

            return response()->json(__('Data successfully created!'));
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Show the specified resource.
     */
    public function show()
    {
        return view('helpandpolicy::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('helpandpolicy::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

            //TODO:UPDATE FUNCTIONS

            return response()->json(__('Data successfully updated!'));
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        try {

            //TODO:DESTROY FUNCTIONS

            return response()->json(__('Data successfully deleted!'));
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
