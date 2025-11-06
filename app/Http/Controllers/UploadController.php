<?php

namespace App\Http\Controllers;

use App\Contracts\UploadRepositoryContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Resources\UploadResource;

class UploadController extends Controller
{
    public function __construct(private UploadRepositoryContract $uploads) {}

    public function index()
    {
        return UploadResource::collection($this->uploads->all());
    }

    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $upload = $this->uploads->create($request->file('file'));
        return new UploadResource($upload);
    }

    public function progress(string $id)
    {
        return ['progress' => (int) Redis::get("upload:progress:$id")];
    }
}
