@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto mt-10 bg-white shadow rounded p-6">
        <h1 class="text-xl font-bold mb-4 text-slate-700">🧱 CRUD Generator</h1>

        {{-- اختيار الجدول --}}
        <form id="select-table" class="mb-6">
            <label class="block mb-2 text-slate-600 font-semibold">اختر الجدول</label>
            <select name="table" class="border rounded px-3 py-2 w-full" onchange="this.form.submit()">
                <option value="">-- اختر جدول --</option>
                @foreach($files as $t)
                    <option value="{{ $t }}" @selected($selected === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </form>

        @if($selected)
            <form id="crud-generator-form" class="space-y-4">
                @csrf
                <input type="hidden" name="table" value="{{ $selected }}">

                <h2 class="text-lg font-semibold mb-2">الحقول المتاحة في جدول: <span class="text-indigo-600">{{ $selected }}</span></h2>

                <div class="border rounded p-4 max-h-64 overflow-y-auto">
                    @foreach($fields as $f)
                        @php $name = $f['name'] ?? null; $type = $f['type'] ?? 'string'; @endphp
                        @if($name)
                            <label class="flex items-center justify-between border-b py-1">
                            <span>
                                <input type="checkbox" name="fields[]" value="{{ $name }}" class="mr-2">
                                <span class="font-medium">{{ $name }}</span>
                                <span class="text-xs text-slate-500 ml-2">({{ $type }})</span>
                            </span>
                            </label>
                        @endif
                    @endforeach
                </div>

                {{-- المعاينة --}}
                <div class="mt-4">
                    <button type="button" id="preview-btn" class="bg-slate-600 text-white px-4 py-2 rounded mr-2">
                        معاينة
                    </button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">
                        توليد CRUD
                    </button>
                </div>

                <div id="preview-box" class="mt-4 hidden border rounded p-3 bg-slate-50 text-xs font-mono whitespace-pre-wrap"></div>
            </form>
        @else
            <div class="text-slate-500">اختر جدول من الأعلى لعرض الحقول.</div>
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

            const modelName = table.replace(/(^|_)(\w)/g, (_, __, c) => c.toUpperCase().trim());
            const routeName = `generated.${table}`;

            const preview = `
Model: App\\\\Models\\\\${modelName}
Controller: App\\\\Http\\\\Controllers\\\\Generated\\\\${modelName}Controller
View: resources/views/generated/${table}/index.blade.php

Fields:
- ${fields.join('\\n- ')}

Route suggestion:
Route::resource('/generated/${table}', App\\\\Http\\\\Controllers\\\\Generated\\\\${modelName}Controller::class)->names('${routeName}');
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
