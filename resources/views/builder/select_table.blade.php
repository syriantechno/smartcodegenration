@extends('layouts.builder')

@section('content')
    <div class="flex-1 p-8 bg-gray-50 min-h-screen">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">🎨 اختر جدول لتصميم الفورم</h1>

        @if($files->isEmpty())
            <div class="bg-white border rounded-lg shadow p-6 text-center text-slate-500">
                لا توجد جداول محفوظة بعد.
            </div>
        @else
            <div class="bg-white rounded-lg shadow p-6 border">
                <ul class="divide-y">
                    @foreach($files as $t)
                        <li class="py-3 flex justify-between items-center">
                            <span class="font-medium text-slate-700">{{ $t }}</span>
                            <a href="{{ url('/builder/form-designer/' . $t) }}"
                               class="px-4 py-1.5 text-white bg-blue-600 hover:bg-blue-700 rounded-md text-sm">
                                ✏️ تصميم الفورم
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
