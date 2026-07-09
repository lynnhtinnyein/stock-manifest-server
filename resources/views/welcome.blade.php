@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-900 px-5">
        <div class="text-center max-w-lg">
            <h1 class="text-4xl font-semibold mb-4 text-white">Modern Charm API Server</h1>
            <p class="text-base text-gray-400 leading-relaxed">
                This is a protected API server. Access is restricted to authorized clients only.
            </p>
            <div class="inline-block mt-6 px-4 py-2 bg-gray-800 border border-gray-700 rounded-md text-sm">
                <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                <span class="text-gray-300">Online</span>
            </div>
        </div>
    </div>
@endsection
