@extends('layouts.builder')

@section('content')
    <div class="max-w-5xl mx-auto">

        <!-- 🧩 Title -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-slate-700 flex items-center gap-2">
                🔗 Relations Builder
            </h1>
            <a href="{{ url('/builder/tables') }}"
               class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                ← Back to Tables
            </a>
        </div>

        <!-- 🧠 Relation Form -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-10 border border-gray-100">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">➕ Create New Relation</h2>
            <form id="relation-form" class="grid md:grid-cols-4 gap-5 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">From Table</label>
                    <select name="table_a" id="table_a"
                            class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($files as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Relation Type</label>
                    <select name="relation_type" id="relation_type"
                            class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="hasOne">hasOne</option>
                        <option value="hasMany">hasMany</option>
                        <option value="belongsTo">belongsTo</option>
                        <option value="belongsToMany">belongsToMany</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">To Table</label>
                    <select name="table_b" id="table_b"
                            class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($files as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-right">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow-md">
                        💾 Save
                    </button>
                </div>
            </form>
        </div>

        <!-- 📋 Relations Table -->
        <div class="bg-white shadow-lg rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-700 flex items-center gap-2">
                    📁 Existing Relations
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">#</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">From Table</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">To Table</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="relations-body" class="divide-y divide-gray-100">
                    @forelse($relations as $i => $r)
                        <tr class="hover:bg-gray-50 transition-all">
                            <td class="px-6 py-3 text-gray-500 text-sm">{{ $i + 1 }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $r['table_a'] }}</td>
                            <td class="px-6 py-3 text-blue-600 font-semibold">{{ $r['relation_type'] }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $r['table_b'] }}</td>
                            <td class="px-6 py-3 text-right">
                                <button
                                    onclick="injectRelation({{ $i }})"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded-md shadow-sm">
                                    ⚙️ Inject
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-400 py-6">No relations found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function injectRelation(index) {
            try {
                const res = await fetch(`/builder/relations/inject/${index}`);
                const data = await res.json();
                
                if (data.status === 'ok') {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Operation completed successfully',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3b82f6',
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.error || 'An error occurred',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to process your request',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
                console.error('Error:', error);
            }
        }

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
                const tbody = document.getElementById('relations-body');
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 transition-all';
                tr.innerHTML = `
                    <td class='px-6 py-3 text-gray-500 text-sm'>${data.count}</td>
                    <td class='px-6 py-3 font-medium text-gray-800'>${formData.get('table_a')}</td>
                    <td class='px-6 py-3 text-blue-600 font-semibold'>${formData.get('relation_type')}</td>
                    <td class='px-6 py-3 font-medium text-gray-800'>${formData.get('table_b')}</td>
                    <td class='px-6 py-3 text-right'>
                        <button onclick='injectRelation(${data.count - 1})'
                            class='bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded-md shadow-sm'>
                            ⚙️ Inject
                        </button>
                    </td>`;
                tbody.appendChild(tr);
                e.target.reset();
                
                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Relation saved successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3b82f6',
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.error || 'Failed to save relation',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    </script>
@endsection
