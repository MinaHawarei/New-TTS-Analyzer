<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FindPackage;


class MainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = FindPackage::allPackages();

        return view('index', ['items' => $items]);
    }
    public function compensation()
    {
        $items = FindPackage::allPackages();

        return view('compensation', ['items' => $items]);
    }
    public function revaidation()
    {
        $items = FindPackage::allPackages();

        return view('revaidation-compensation', ['items' => $items]);
    }
    public function showWithData(Request $request)
    {
        $data = $request->only(['inputText', 'tktID', 'selectPackage']);

        $items = FindPackage::allPackages();

        return view('compensation', [
            'items' => $items,
            'inputText' => $data['inputText'] ?? '',
            'tktID' => $data['tktID'] ?? '',
            'selectPackage' => $data['selectPackage'] ?? '',
        ]);
    }
    public function outage()
    {
        $items = FindPackage::allPackages();

        return view('outage-compensation', ['items' => $items]);
    }

    public function omhelper()
    {
        return view('omhelper');

    }
}
