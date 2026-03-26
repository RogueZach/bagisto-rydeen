@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.resources.title'))

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">@lang('rydeen-dealer::app.shop.resources.title')</h1>

        <form action="{{ route('dealer.resources') }}" method="GET" class="flex gap-2">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ trans('rydeen-dealer::app.shop.resources.search-placeholder') }}"
                   class="border border-gray-300 rounded px-3 py-2 text-sm w-64">
            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-black">
                @lang('rydeen-dealer::app.shop.catalog.search')
            </button>
        </form>
    </div>

    @if ($grouped->isEmpty())
        <div class="text-center py-12 bg-white rounded-lg shadow text-gray-500">
            @lang('rydeen-dealer::app.shop.resources.no-resources')
        </div>
    @else
        <div class="space-y-8">
            @foreach ($grouped as $category => $items)
                <div class="bg-white rounded-lg shadow">
                    <h2 class="text-lg font-semibold text-gray-800 px-6 py-4 border-b">
                        {{ $category }}
                    </h2>

                    <div x-data="{ openItem: null }">
                        @foreach ($items as $index => $item)
                            <div class="border-b last:border-b-0">
                                <button @click="openItem = openItem === {{ $item->id }} ? null : {{ $item->id }}"
                                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition">
                                    <span class="text-sm font-medium text-gray-700">{{ $item->title }}</span>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform"
                                         :class="{ 'rotate-180': openItem === {{ $item->id }} }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <div x-show="openItem === {{ $item->id }}"
                                     x-transition
                                     class="px-6 pb-4">
                                    @if ($item->content)
                                        <div class="text-sm text-gray-600 leading-relaxed prose max-w-none">
                                            {!! nl2br(e($item->content)) !!}
                                        </div>
                                    @endif

                                    @if ($item->file_path)
                                        <div class="mt-3">
                                            <a href="{{ asset('storage/' . $item->file_path) }}"
                                               class="inline-flex items-center text-sm text-gray-900 hover:text-gray-700"
                                               target="_blank" download>
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                @lang('rydeen-dealer::app.shop.resources.download')
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
