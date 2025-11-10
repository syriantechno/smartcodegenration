@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto mt-10">

        <h1 class="text-2xl font-bold mb-6">🧱 Laravel Table Builder</h1>

        <div class="border p-5 rounded-lg mb-10 bg-white shadow">
            <h2 class="text-lg font-semibold mb-4">إنشاء جدول جديد</h2>

            <form id="builder-form">
                <div class="mb-3">
                    <label class="block mb-1 font-medium">اسم الجدول</label>
                    <input type="text" id="table" class="border rounded w-full px-3 py-2" placeholder="employees">
                </div>

                <div id="fields-area" class="space-y-3"></div>

                <button type="button" onclick="addField()" class="bg-blue-600 text-white px-4 py-2 rounded mt-3">
                    ➕ إضافة حقل
                </button>

                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded mt-3 float-right">
                    💾 حفظ الجدول
                </button>
            </form>
        </div>

        <div class="border p-5 rounded-lg bg-white shadow">
            <h2 class="text-lg font-semibold mb-4">📦 الجداول المحفوظة</h2>

            @if(empty($savedTables))
                <div class="text-slate-500 text-center p-5 border rounded bg-slate-50">
                    لا توجد جداول محفوظة بعد.
                </div>
            @else
                @foreach($savedTables as $name)
                    <div class="flex justify-between items-center border-b py-3">
                        <span class="font-medium">{{ $name }}</span>
                        <button onclick="injectTable('{{ $name }}')"
                                class="bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700">
                            🧩 Inject to DB
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
        <hr class="my-10">

        <div class="border p-5 rounded-lg bg-white shadow">
            <h2 class="text-lg font-semibold mb-4">🧩 الجداول الموجودة في قاعدة البيانات</h2>

            @if(empty($dbTables))
                <div class="text-slate-500 text-center p-5 border rounded bg-slate-50">
                    لا توجد جداول في القاعدة حالياً.
                </div>
            @else
                @foreach($dbTables as $tName => $cols)
                    <details class="mb-2 border rounded">
                        <summary class="cursor-pointer px-3 py-2 bg-slate-100 font-semibold">
                            {{ $tName }} <span class="text-xs text-slate-500">({{ count($cols) }} عمود)</span>
                        </summary>
                        <table class="w-full text-sm border-t">
                            <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left border-b">العمود</th>
                                <th class="px-3 py-2 text-left border-b">النوع</th>
                                <th class="px-3 py-2 text-left border-b">إجباري؟</th>
                                <th class="px-3 py-2 text-left border-b">القيمة الافتراضية</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($cols as $c)
                                <tr>
                                    <td class="px-3 py-1 border-b">{{ $c->Field }}</td>
                                    <td class="px-3 py-1 border-b text-slate-600">{{ $c->Type }}</td>
                                    <td class="px-3 py-1 border-b text-center">{{ $c->Null === 'NO' ? '✅' : '' }}</td>
                                    <td class="px-3 py-1 border-b text-slate-500">{{ $c->Default ?? '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </details>
                @endforeach
            @endif
        </div>

    </div>

    <script>
        let fieldIndex = 0;

        function addField() {
            const container = document.getElementById('fields-area');
            const div = document.createElement('div');
            div.className = 'border p-3 rounded flex items-center gap-2';
            div.innerHTML = `
        <input type="text" name="fields[${fieldIndex}][name]" placeholder="اسم الحقل" class="border rounded px-3 py-1 w-1/3">
        <select name="fields[${fieldIndex}][type]" class="border rounded px-2 py-1 w-1/3">
            <option value="string">String</option>
            <option value="integer">Integer</option>
            <option value="decimal">Decimal</option>
            <option value="boolean">Boolean</option>
            <option value="date">Date</option>
            <option value="text">Text</option>
        </select>
        <label class="flex items-center gap-1">
            <input type="checkbox" name="fields[${fieldIndex}][required]"> Required
        </label>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 font-bold">✕</button>
    `;
            container.appendChild(div);
            fieldIndex++;
        }

        document.getElementById('builder-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const table = document.getElementById('table').value;
            if (!table) return alert('⚠️ أدخل اسم الجدول أولاً.');

            const fields = [];
            document.querySelectorAll('#fields-area > div').forEach(div => {
                const name = div.querySelector('input[name*="[name]"]').value;
                const type = div.querySelector('select').value;
                const required = div.querySelector('input[type="checkbox"]').checked;
                if (name) fields.push({ name, type, required });
            });

            const res = await fetch('{{ url("/builder/save") }}', {
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
            if (!confirm(`هل تريد حقن الجدول '${table}' في قاعدة البيانات؟`)) return;
            const res = await fetch(`/builder/inject/${table}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await res.json();
            alert(data.message || data.error);
        }
    </script>
@endsection
