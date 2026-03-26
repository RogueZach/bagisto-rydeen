@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-auth::app.login-title'))

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">RYDEEN</h1>
            <p class="mt-2 text-gray-600">{{ trans('rydeen-auth::app.login-title') }}</p>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('dealer.login.submit') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                    placeholder="dealer@example.com"
                >
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-gray-900 text-white py-3 px-4 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition font-semibold"
            >
                Sign In
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            Not a dealer yet? <a href="{{ route('dealer.register') }}" class="text-gray-900 hover:text-gray-700 font-medium">Apply for an account</a>
        </p>
    </div>
</div>
@endsection
