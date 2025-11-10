@extends('layouts.builder')

@section('content')
    <div class="flex">
        <!-- 🔹 محتوى التصميم -->
        <div class="flex-1 p-8 bg-gray-50 min-h-screen space-y-8">
            <div>
                <h2 class="text-3xl font-bold text-slate-800 mb-2">🎨 Form Designer — {{ ucfirst($table) }}</h2>
                <p class="text-gray-500 text-sm">Customize inputs, styles, and preview the final form live.</p>
            </div>

            <!-- 🔹 الحقول -->
            <div id="previewArea" class="border p-6 rounded-xl bg-white shadow-sm space-y-6">
                @foreach($fields as $f)
                    @php
                        $label = ucfirst(str_replace('_',' ', $f['label'] ?? $f['name']));
                        $input = $f['input'] ?? 'text';
                        $style = $f['style'] ?? 'classic';
                    @endphp

                    <div class="border border-gray-200 rounded-xl bg-white shadow-sm p-4 hover:shadow-lg cursor-pointer field-block"
                         data-name="{{ $f['name'] }}" onclick="selectField(this)">
                        <label class="block font-semibold text-gray-700 mb-2">{{ $label }}</label>
                        <div id="preview_{{ $f['name'] }}" class="{{ $style }}">
                            @if($input === 'select')
                                <select class="border rounded-lg p-2 w-full"><option>Option</option></select>
                            @elseif($input === 'textarea')
                                <textarea class="border rounded-lg p-2 w-full"></textarea>
                            @elseif($input === 'switch')
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-blue-500 relative after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all peer-checked:after:translate-x-full"></div>
                                </label>
                            @else
                                <input type="{{ $input }}" class="border rounded p-2 w-full">
                            @endif
                        </div>

                        <!-- خيارات سريعة -->
                        <div class="mt-4 flex flex-wrap gap-6 text-sm text-gray-600">
                            <div>
                                <label class="block font-medium text-gray-700 mb-1">Input Type</label>
                                <select onchange="updateInputType('{{ $table }}', '{{ $f['name'] }}', this.value)"
                                        class="border rounded-md px-2 py-1 text-sm">
                                    @foreach(['text','email','number','textarea','date','select','switch','color'] as $type)
                                        <option value="{{ $type }}" {{ $input === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 mb-1">Style</label>
                                <select onchange="updateInputStyle('{{ $table }}', '{{ $f['name'] }}', this.value)"
                                        class="border rounded-md px-2 py-1 text-sm">
                                    @foreach(['classic','softui','flat','glass','material'] as $sty)
                                        <option value="{{ $sty }}" {{ $style === $sty ? 'selected' : '' }}>{{ ucfirst($sty) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- 🔹 زر توليد -->
            <div class="text-right pt-4">
                <button onclick="generateForm('{{ $table }}')"
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 shadow">
                    ⚙️ Generate Form
                </button>
            </div>

            <!-- 🔹 المعاينة الفورية -->

            <div id="livePreviewContainer" class="hidden mt-10">
                <h3 class="text-xl font-bold text-gray-700 mb-4">🧩 Live Form Preview</h3>
                <div id="livePreview" class="border rounded-xl p-6 bg-white shadow space-y-4"></div>
            </div>

        </div>

        <!-- 🎨 الشريط الجانبي -->
        <div id="sidebar" class="w-80 bg-white border-l border-gray-200 shadow-xl p-6 space-y-6 hidden fixed right-0 top-0 h-full overflow-y-auto z-50">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-700">🎛 Field Settings</h3>
                <button onclick="closeSidebar()" class="text-red-600 font-bold text-lg">✖</button>
            </div>
            <p id="activeFieldName" class="text-sm text-gray-500 mb-4">Select a field to edit</p>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Border color</label>
                <input type="color" id="borderColor" class="w-full h-8 cursor-pointer border rounded" onchange="updateProperty('borderColor', this.value)">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mt-4 mb-1">Background color</label>
                <input type="color" id="bgColor" class="w-full h-8 cursor-pointer border rounded" onchange="updateProperty('background', this.value)">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mt-4 mb-1">Focus color</label>
                <input type="color" id="focusColor" class="w-full h-8 cursor-pointer border rounded" onchange="updateProperty('focusColor', this.value)">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mt-4 mb-1">Corner radius</label>
                <input type="range" min="0" max="50" value="8" id="radius" class="w-full" onchange="updateProperty('borderRadius', this.value + 'px')">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mt-4 mb-1">Shadow</label>
                <select id="shadow" class="border rounded p-1 w-full" onchange="updateProperty('boxShadow', this.value)">
                    <option value="none">None</option>
                    <option value="0 2px 5px rgba(0,0,0,0.1)">Soft</option>
                    <option value="0 4px 10px rgba(0,0,0,0.2)">Medium</option>
                    <option value="0 6px 16px rgba(0,0,0,0.3)">Strong</option>
                </select>
            </div>
        </div>
    </div>

    <script>
        let selectedField = null;

        function selectField(el) {
            document.querySelectorAll('.field-block').forEach(e => e.classList.remove('ring-2', 'ring-blue-400'));
            el.classList.add('ring-2', 'ring-blue-400');
            selectedField = el.dataset.name;
            document.getElementById('activeFieldName').textContent = "Editing: " + selectedField;
            document.getElementById('sidebar').classList.remove('hidden');
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.add('hidden');
            selectedField = null;
        }

        async function updateInputType(table, field, inputType) {
            await fetch(`/builder/preview/update`, {
                method: "POST",
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
                body: JSON.stringify({ table, field, input: inputType })
            });
            const preview = document.getElementById(`preview_${field}`);
            if (preview) preview.innerHTML = `<input type='${inputType}' class='border rounded p-2 w-full'>`;
        }

        async function updateInputStyle(table, field, style) {
            await fetch(`/builder/preview/update-style`, {
                method: "POST",
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
                body: JSON.stringify({ table, field, style })
            });
            const preview = document.getElementById(`preview_${field}`);
            if (preview) preview.className = style;
        }

        async function updateProperty(prop, value) {
            if (!selectedField) return;

            const table = '{{ $table }}';
            const field = selectedField;

            // تحديث محلي مباشر
            const container = document.getElementById(`preview_${field}`);
            const inputEl = container.querySelector('input, textarea, select');
            if (!inputEl) return;

            switch (prop) {
                case 'borderColor': inputEl.style.borderColor = value; break;
                case 'background': inputEl.style.backgroundColor = value; break;
                case 'borderRadius': inputEl.style.borderRadius = value; break;
                case 'boxShadow': inputEl.style.boxShadow = value; break;
            }

            // إرسال التعديل إلى السيرفر
            await fetch("{{ url('/builder/preview/update-ui') }}", {
                method: "POST",
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
                body: JSON.stringify({ table, field, prop, value })
            });

            // 🟢 تحديث المعاينة التحتية مباشرة
            if (typeof updateLivePreview === 'function') {
                updateLivePreview();
            }
        }


        async function generateForm(table) {
            const res = await fetch(`/builder/preview/generate/${table}`);
            const data = await res.json();

            const previewBox = document.getElementById('livePreviewContainer');
            const livePreview = document.getElementById('livePreview');

            previewBox.classList.remove('hidden');

            if (data.status === 'ok') {
                livePreview.innerHTML = data.html;
            } else {
                livePreview.innerHTML = `<div class="text-red-600">⚠️ ${data.error || 'Generation failed.'}</div>`;
            }
        }
        async function updateLivePreview() {
            const table = '{{ $table ?? '' }}';
            if (!table) return;

            const res = await fetch(`/builder/preview/generate/${table}`);
            const data = await res.json();

            const liveContainer = document.getElementById('livePreview');
            const liveBox = document.getElementById('livePreviewContainer');
            if (liveContainer && data.status === 'ok') {
                liveBox.classList.remove('hidden');
                liveContainer.innerHTML = data.html;
            }
        }

    </script>
@endsection
