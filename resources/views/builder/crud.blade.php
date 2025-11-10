@extends('layouts.builder')

@section('content')
    <div class="flex-1 p-8 bg-gray-50 min-h-screen">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">⚙️ CRUD Generator</h1>

        <!-- 🧱 اختيار الجدول -->
        <div class="bg-white border rounded-lg shadow p-6 mb-8">
            <form id="select-table" class="space-y-3">
                <label class="block text-slate-600 font-semibold">اختر الجدول</label>
                <select name="table"
                        class="border rounded-lg px-3 py-2 w-full text-slate-700 focus:ring-2 focus:ring-blue-500"
                        onchange="this.form.submit()">
                    <option value="">-- اختر جدول --</option>
                    @foreach($files as $t)
                        <option value="{{ $t }}" @selected($selected === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- 📦 عرض الحقول -->
        @if($selected)
            <div class="bg-white border rounded-lg shadow p-6 space-y-6">
                <h2 class="text-lg font-semibold text-slate-700">
                    الحقول المتاحة في جدول: <span class="text-indigo-600">{{ $selected }}</span>
                </h2>

                <form id="crud-generator-form" class="space-y-4">
                    @csrf
                    <input type="hidden" name="table" value="{{ $selected }}">

                    <div class="border rounded-lg p-4 max-h-72 overflow-y-auto bg-slate-50">
                        @foreach($fields as $f)
                            @php $name = $f['name'] ?? null; $type = $f['type'] ?? 'string'; @endphp
                            @if($name)
                                <label class="flex items-center justify-between border-b py-2 hover:bg-slate-100 px-2 rounded">
                                <span>
                                    <input type="checkbox" name="fields[]" value="{{ $name }}" class="mr-2 accent-blue-600">
                                    <span class="font-medium text-slate-700">{{ $name }}</span>
                                    <span class="text-xs text-slate-500 ml-2">({{ $type }})</span>
                                </span>
                                </label>
                            @endif
                        @endforeach
                    </div>

                    <!-- 🔘 الأزرار -->
                    <div class="flex justify-between items-center mt-4">
                        <button type="button" id="preview-btn"
                                class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded shadow">
                            👁️ معاينة
                        </button>

                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded shadow">
                            🚀 توليد CRUD
                        </button>
                    </div>

                    <!-- 📜 صندوق المعاينة -->
                    <div id="preview-box"
                         class="hidden mt-5 border rounded-lg p-4 bg-gray-900 text-green-300 text-xs font-mono whitespace-pre-wrap overflow-x-auto">
                    </div>
                </form>
            </div>
        @else
            <div class="bg-white border rounded-lg shadow p-6 text-center text-slate-500">
                اختر جدول من الأعلى لعرض الحقول.
            </div>
        @endif
    </div>

    <script>
        const form = document.getElementById('crud-generator-form');
        const previewBtn = document.getElementById('preview-btn');
        const previewBox = document.getElementById('preview-box');

        previewBtn?.addEventListener('click', () => {
            const fd = new FormData(form);
            const table = fd.get('table');
            const fields = fd.getAll('fields[]');

            if (!table || fields.length === 0) {
                alert('⚠️ اختر جدول وبعض الحقول أولاً.');
                return;
            }

            const modelName = table.replace(/(^|_)(\w)/g, (_, __, c) => c.toUpperCase());
            const routeName = `generated.${table}`;

            const preview = `
Model: App\\\\Models\\\\${modelName}
Controller: App\\\\Http\\\\Controllers\\\\Generated\\\\${modelName}Controller
View: resources/views/generated/${table}/index.blade.php

Fields:
- ${fields.join('\\n- ')}

Route suggestion:
Route::resource('/generated/${table}', App\\\\Http\\\\Controllers\\\\Generated\\\\${modelName}Controller::class)
    ->names('${routeName}');
        `.trim();

            previewBox.textContent = preview;
            previewBox.classList.remove('hidden');
        });

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            const res = await fetch('{{ route("builder.crud.generate") }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await res.json();
            if (data.status === 'ok') {
                alert(data.message + "\\n\\nأضف هذا السطر في routes/web.php:\\n" + data.route);
                console.log('Model:', data.model_path);
                console.log('Controller:', data.controller_path);
                console.log('View:', data.view_path);
            } else {
                alert('❌ Error: ' + (data.message || data.error || 'unknown'));
            }
        });
    </script>
@endsection
