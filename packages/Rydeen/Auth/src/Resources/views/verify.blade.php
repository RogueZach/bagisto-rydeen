@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-auth::app.verify-title'))

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">RYDEEN</h1>
            <p class="mt-2 text-gray-600">{{ trans('rydeen-auth::app.verify-title') }}</p>
        </div>

        <p class="text-sm text-gray-600 mb-6 text-center">
            {{ trans('rydeen-auth::app.enter-code') }} <strong>{{ $email }}</strong>
        </p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('dealer.verify') }}">
            @csrf

            <div class="mb-6">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ trans('rydeen-auth::app.verify-title') }}
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    required
                    autofocus
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none transition text-center text-2xl tracking-widest font-mono"
                    placeholder="000000"
                >
                <p class="mt-2 text-xs text-gray-500 text-center">
                    {{ trans('rydeen-auth::app.code-expires') }}
                </p>
            </div>

            <button
                type="submit"
                class="w-full bg-gray-900 text-white py-2 px-4 rounded-md hover:bg-black focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 transition font-medium"
            >
                {{ trans('rydeen-auth::app.verify-code') }}
            </button>
        </form>

        <form method="POST" action="{{ route('dealer.resend-code') }}" class="mt-4 text-center">
            @csrf
            <button
                type="submit"
                class="text-sm text-gray-900 hover:text-gray-700 underline transition"
            >
                {{ trans('rydeen-auth::app.resend-code') }}
            </button>
        </form>
    </div>
</div>
@endsection
