@extends('layouts.builder')

@section('content')
    <div class="p-8 space-y-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">🎨 Form Master Builder</h2>
        <p class="text-gray-600 mb-6">Select a table to preview, customize, and generate full CRUD.</p>

        <!-- 🔹 اختيار الجدول -->
        <div class="flex items-center gap-3 mb-6">
            <label class="font-semibold text-gray-700">Select Table:</label>
            <select id="tableSelect" class="border rounded-lg p-2 w-64">
                <option value="">-- Select --</option>
                @foreach($tableNames as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>

            <button id="openDesigner"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 hidden">
                🧩 Open Designer
            </button>

            <button id="reloadPreview"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 hidden">
                🔄 Refresh Preview
            </button>

            <button id="generateCrud"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hidden">
                ⚙️ Generate CRUD
            </button>
        </div>

        <!-- 🔹 المعاينة -->
        <div id="livePreviewContainer" class="hidden border p-6 rounded-xl bg-white shadow">
            <h3 class="text-lg font-semibold mb-3">👁 Live Form Preview</h3>
            <div id="livePreviewBox" class="space-y-4"></div>
        </div>
    </div>

    <script>
        const tableSelect = document.getElementById('tableSelect');
        const openDesigner = document.getElementById('openDesigner');
        const reloadBtn = document.getElementById('reloadPreview');
        const generateBtn = document.getElementById('generateCrud');
        const liveContainer = document.getElementById('livePreviewContainer');
        const liveBox = document.getElementById('livePreviewBox');

        async function loadPreview(table) {
            try {
                liveContainer.classList.add('hidden');
                // استخدام المسار الصحيح مع البادئة /builder/form-master/
                const res = await fetch(`/builder/form-master/preview/${table}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });
                
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await res.json();

                if (data.status === 'ok') {
                    liveContainer.classList.remove('hidden');
                    liveBox.innerHTML = data.html;
                    openDesigner.href = `/builder/form-designer/${table}`;
                    openDesigner.classList.remove('hidden');
                    reloadBtn.classList.remove('hidden');
                    generateBtn.dataset.table = table;
                    generateBtn.classList.remove('hidden');
                } else {
                    throw new Error(data.error || 'Error generating preview.');
                }
            } catch (error) {
                console.error('Error loading preview:', error);
                alert('حدث خطأ أثناء تحميل المعاينة. يرجى التأكد من وجود الجدول المحدد.');
            }
        }

        tableSelect.addEventListener('change', function() {
            const table = this.value;
            if (!table) return;
            loadPreview(table);
        });

        reloadBtn.addEventListener('click', () => {
            const table = tableSelect.value;
            if (!table) return alert("⚠️ Select a table first!");
            loadPreview(table);
        });

        openDesigner.addEventListener('click', () => {
            const table = tableSelect.value;
            if (!table) return alert("⚠️ Select a table first!");
            window.location.href = `/builder/form-designer/${table}`;
        });

        // 🔹 توليد CRUD كامل
        generateBtn.addEventListener('click', async () => {
            const table = tableSelect.value;
            if (!table) return alert("⚠️ الرجاء اختيار جدول أولاً!");
            
            try {
                const res = await fetch(`/builder/crud/generate/${table}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await res.json();

                if (data.status === 'ok') {
                    liveBox.innerHTML = `
        <div class="p-4 bg-green-50 border border-green-300 rounded-lg shadow-inner">
            <div class="text-green-700 font-bold text-lg mb-2">✅ ${data.message || 'تم إنشاء CRUD بنجاح'}</div>
            <div class="text-gray-700 text-sm leading-relaxed">
                ${data.model ? `<p><strong>النموذج:</strong> ${data.model}</p>` : ''}
                ${data.controller ? `<p><strong>المتحكم:</strong> ${data.controller}</p>` : ''}
                ${data.migration ? `<p><strong>الترحيل:</strong> ${data.migration}</p>` : ''}
                ${data.views && data.views.length ? `<p><strong>واجهات المستخدم:</strong> ${data.views.join('<br>')}</p>` : ''}
            </div>
            <div class="mt-3">
                <button onclick="loadPreview('${table}')"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    👁 عرض معاينة النموذج مرة أخرى
                </button>
            </div>
        </div>`;
                } else {
                    throw new Error(data.error || '❌ فشل إنشاء CRUD');
                }
            } catch (error) {
                console.error('Error generating CRUD:', error);
                alert(`حدث خطأ أثناء إنشاء CRUD: ${error.message}`);
            }
        });
    </script>
@endsection
