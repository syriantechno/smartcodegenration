@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto mt-10">
        <h1 class="text-2xl font-bold mb-6 text-slate-700">🔗 Relations Builder</h1>

        <!-- Relation Creation Form -->
        <form id="relation-form" class="bg-white shadow-md rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600">From Table</label>
                    <select name="table_a" id="table_a" class="w-full border-slate-300 rounded-md">
                        @foreach($files as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600">Relation Type</label>
                    <select name="relation_type" id="relation_type" class="w-full border-slate-300 rounded-md">
                        <option value="hasOne">hasOne</option>
                        <option value="hasMany">hasMany</option>
                        <option value="belongsTo">belongsTo</option>
                        <option value="belongsToMany">belongsToMany</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600">To Table</label>
                    <select name="table_b" id="table_b" class="w-full border-slate-300 rounded-md">
                        @foreach($files as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="text-right mt-6">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-md">
                    Save Relation
                </button>
            </div>
        </form>

        <!-- Relations List -->
        <div id="relations-list" class="mt-8 bg-white rounded-lg shadow p-5">
            <h2 class="text-lg font-semibold mb-3 text-slate-700">📁 Existing Relations</h2>
            <ul id="relations-ul" class="list-disc pl-5 text-slate-600">
                @forelse($relations as $i => $r)
                    <li class="flex items-center justify-between py-1">
                        <span>{{ $r['table_a'] }} → {{ $r['relation_type'] }} → {{ $r['table_b'] }}</span>
                        <button
                            onclick="injectRelation({{ $i }})"
                            class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">
                            Inject to DB
                        </button>
                    </li>
                @empty
                    <li>No relations yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        async function injectRelation(index) {
            const res = await fetch(`/builder/relations/inject/${index}`);
            const data = await res.json();
            alert(data.message || data.error);
        }

        // 🧠 Save Relation + Auto Append Without Reload
        document.getElementById('relation-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            const res = await fetch('/builder/relations/save', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            const data = await res.json();
            if (data.status === 'ok') {
                alert('✅ Relation saved! Total: ' + data.count);

                // append new relation instantly
                const ul = document.getElementById('relations-ul');
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between py-1';
                li.innerHTML = `
            <span>${formData.get('table_a')} → ${formData.get('relation_type')} → ${formData.get('table_b')}</span>
            <button onclick="injectRelation(${data.count - 1})"
                class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">
                Inject to DB
            </button>
        `;
                if (ul.querySelector('li') && ul.querySelector('li').innerText === 'No relations yet.') {
                    ul.innerHTML = '';
                }
                ul.appendChild(li);

                e.target.reset();
            } else {
                alert('❌ Error: could not save relation.');
            }
        });
    </script>
@endsection
