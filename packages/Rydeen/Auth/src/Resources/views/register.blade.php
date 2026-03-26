@extends('rydeen::shop.layouts.master')

@section('title', 'Apply for a Dealer Account')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">RYDEEN</h1>
            <p class="mt-2 text-gray-600">Apply for a Dealer Account</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('dealer.register.submit') }}">
            @csrf

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                        First Name
                    </label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="{{ old('first_name') }}"
                        required
                        autofocus
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                    >
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Last Name
                    </label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="{{ old('last_name') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                    >
                </div>
            </div>

            <div class="mb-4">
                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Name
                </label>
                <input
                    type="text"
                    id="business_name"
                    name="business_name"
                    value="{{ old('business_name') }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                >
            </div>

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
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                    placeholder="you@dealership.com"
                >
            </div>

            <div class="mb-6">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Phone <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="{{ old('phone') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-yellow-400 text-gray-900 py-3 px-4 rounded-md hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition font-semibold"
            >
                Submit Application
            </button>
        </form>

        <p class="mt-4 text-center text-xs text-gray-500">
            Your application will be reviewed by Rydeen. You'll receive an email once approved.
        </p>

        <p class="mt-4 text-center text-sm text-gray-600">
            Already a dealer? <a href="{{ route('dealer.login') }}" class="text-gray-900 hover:text-gray-700 font-medium">Sign in</a>
        </p>
    </div>
</div>
@endsection
