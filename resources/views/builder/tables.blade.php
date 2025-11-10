@extends('layouts.builder')

@section('content')
    <div class="max-w-5xl mx-auto space-y-10">

        <!-- 🧱 العنوان الرئيسي -->
        <div class="border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                🧱 Laravel Table Builder
            </h1>
            <p class="text-gray-500 mt-1">Create and manage database tables easily.</p>
        </div>

        <!-- 🔹 إنشاء جدول جديد -->
        <div class="bg-white border rounded-xl shadow p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Create New Table</h2>

            <form id="builder-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Table name</label>
                    <input type="text" id="table"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                           placeholder="employees">
                </div>

                <!-- الحقول -->
                <div id="fields-area" class="space-y-3"></div>

                <div class="flex justify-between items-center pt-4 border-t">
                    <button type="button" onclick="addField()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow">
                        ➕ Add Field
                    </button>
                    <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg shadow">
                        💾 Save Table
                    </button>
                </div>
            </form>
        </div>

        <!-- 🔹 الجداول المحفوظة -->
        <div class="bg-white border rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">📦 Saved Tables</h2>

            @if(empty($savedTables))
                <div class="text-center text-gray-400 py-10 border border-dashed rounded-lg bg-gray-50">
                    No saved tables yet.
                </div>
            @else
                <div class="divide-y">
                    @foreach($savedTables as $name)
                        <div class="flex justify-between items-center py-3">
                            <span class="font-medium text-gray-700">{{ $name }}</span>
                            <button onclick="injectTable('{{ $name }}')"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg shadow text-sm">
                                🧩 Inject to DB
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @if(!empty($dbTables))
        <div class="border p-5 rounded-lg bg-white shadow mt-10">
            <h2 class="text-lg font-semibold mb-4">🧭 مستكشف قاعدة البيانات</h2>

            <table class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2 text-left">اسم الجدول</th>
                    <th class="border px-3 py-2 text-center">عدد الأعمدة</th>
                    <th class="border px-3 py-2 text-center">عدد السجلات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($dbTables as $t)
                    @php
                        // ✅ استخرج اسم الجدول من الكائن (مثل النسخة القديمة تمامًا)
                        $tableName = array_values((array)$t)[0];

                        // ✅ اجلب الأعمدة وعدد السجلات
                        $cols = \DB::select("SHOW COLUMNS FROM `$tableName`");
                        $count = \DB::table($tableName)->count();
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-2 font-medium text-gray-800">{{ $tableName }}</td>
                        <td class="border px-3 py-2 text-center text-gray-600">{{ count($cols) }}</td>
                        <td class="border px-3 py-2 text-center text-gray-600">{{ $count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif





    <!-- 🔹 سكربت التفاعلات -->
    <script>
        let fieldIndex = 0;

        function addField() {
            const container = document.getElementById('fields-area');
            const div = document.createElement('div');
            div.className = 'border p-3 rounded-lg bg-gray-50 flex items-center gap-2';
            div.innerHTML = `
            <input type="text" name="fields[${fieldIndex}][name]" placeholder="field name"
                class="border rounded-lg px-3 py-1.5 w-1/3 focus:ring-2 focus:ring-blue-300 focus:outline-none">

            <select name="fields[${fieldIndex}][type]"
                class="border rounded-lg px-2 py-1.5 w-1/3 focus:ring-2 focus:ring-blue-300 focus:outline-none">
                <option value="string">String</option>
                <option value="integer">Integer</option>
                <option value="decimal">Decimal</option>
                <option value="boolean">Boolean</option>
                <option value="date">Date</option>
                <option value="text">Text</option>
            </select>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="fields[${fieldIndex}][required]" class="rounded border-gray-400">
                Required
            </label>

            <button type="button" onclick="this.parentElement.remove()"
                    class="text-red-500 font-bold hover:text-red-700 text-lg">✕</button>
        `;
            container.appendChild(div);
            fieldIndex++;
        }

        document.getElementById('builder-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const table = document.getElementById('table').value;
            if (!table.trim()) return alert('⚠️ Enter table name first.');

            const fields = [];
            document.querySelectorAll('#fields-area > div').forEach(div => {
                const name = div.querySelector('input[name*="[name]"]').value;
                const type = div.querySelector('select').value;
                const required = div.querySelector('input[type="checkbox"]').checked;
                if (name.trim()) fields.push({ name, type, required });
            });

            const res = await fetch('{{ url("/builder/tables/save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ table, fields })
            });
            const data = await res.json();
            alert(data.message || data.error);
            if (data.status === 'ok') location.reload();
        });

        async function injectTable(table) {
            if (!confirm(`Inject table '${table}' into the database?`)) return;
            const res = await fetch(`/builder/inject/${table}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await res.json();
            alert(data.message || data.error);
        }
    </script>
@endsection
