@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5">
    <div class="flex flex-col md:flex-row gap-5">
        @include('admin.partials.sidebar')
        <main class="flex-1 min-w-0">
            @yield('admin')
        </main>
    </div>
</div>
@endsection
