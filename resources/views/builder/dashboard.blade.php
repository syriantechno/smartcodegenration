@extends('layouts.builder')

@section('content')
    <div class="space-y-8">

        <!-- 🔹 العنوان الرئيسي -->
        <div>
            <h1 class="text-3xl font-bold text-gray-800">⚙️ AutoCrudSmart Dashboard</h1>
            <p class="text-gray-500 mt-1">Manage all builder tools from one place.</p>
        </div>

        <!-- 🔹 شبكة الأدوات -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- 🧱 Tables -->
            <a href="{{ route('builder.tables') }}"
               class="group p-6 bg-white rounded-xl shadow hover:shadow-lg border border-gray-200 transition-all">
                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">🧱</div>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Tables Manager</h2>
                <p class="text-gray-500 text-sm leading-snug">Define tables and fields easily, stored as JSON files.</p>
            </a>

            <!-- 🔗 Relations -->
            <a href="{{ route('builder.relations') }}"
               class="group p-6 bg-white rounded-xl shadow hover:shadow-lg border border-gray-200 transition-all">
                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">🔗</div>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Relations Manager</h2>
                <p class="text-gray-500 text-sm leading-snug">Create belongsTo / hasMany relations between tables.</p>
            </a>

            <!-- 🎨 Designer -->
            <a href="{{ route('builder.form.master') }}"

               class="group p-6 bg-white rounded-xl shadow hover:shadow-lg border border-gray-200 transition-all">
                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">🎨</div>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Form Designer</h2>
                <p class="text-gray-500 text-sm leading-snug">Customize fields, colors, radius, and full visual style.</p>
            </a>

            <!-- 🧩 Form Runtime -->
            <a href="{{ route('builder.form.master') }}"

               class="group p-6 bg-white rounded-xl shadow hover:shadow-lg border border-gray-200 transition-all">
                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">🧩</div>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Form Runtime</h2>
                <p class="text-gray-500 text-sm leading-snug">Preview generated forms and test real data inputs.</p>
            </a>

            <!-- ⚙️ CRUD Generator -->
            <a href="{{ route('builder.crud') }}"
               class="group p-6 bg-white rounded-xl shadow hover:shadow-lg border border-gray-200 transition-all">
                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">⚙️</div>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">CRUD Generator</h2>
                <p class="text-gray-500 text-sm leading-snug">Generate Controller, Views, and full Laravel routes instantly.</p>
            </a>

            <!-- 📂 Output -->
            <a href="{{ route('builder.output') }}"
               class="group p-6 bg-white rounded-xl shadow hover:shadow-lg border border-gray-200 transition-all">
                <div class="text-4xl mb-2 group-hover:scale-110 transition-transform">📂</div>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Generated Output</h2>
                <p class="text-gray-500 text-sm leading-snug">Preview all generated files and resources in one place.</p>
            </a>

        </div>

        <!-- 🔹 فوتر -->
        <div class="text-center text-gray-400 text-sm pt-8 border-t">
            SmartCode Generator © {{ date('Y') }} – Built for Laravel 11
        </div>

    </div>
@endsection
