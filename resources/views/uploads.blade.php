@extends('layouts.app')

@section('content')
    <div class="flex justify-end mb-4">
        <a href="/products"
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg shadow transition duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18m-6 6l6-6-6-6" />
            </svg>
            Go to Products
        </a>
    </div>

    <upload></upload>
@endsection