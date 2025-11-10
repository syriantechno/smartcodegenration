@extends('layouts.builder')

@section('content')
    <div class="p-6 max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">🏢 Departments List</h2>

        <div class="flex justify-between items-center mb-4">
            <a href="{{ route('departments.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                ➕ Add Department
            </a>
            <form method="GET" action="{{ route('departments.index') }}" class="flex gap-2">
                <input type="text" name="search" placeholder="Search..."
                       class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <button type="submit"
                        class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200 border">
                    🔍
                </button>
            </form>
        </div>

        <div class="overflow-x-auto bg-white rounded-xl shadow">
            <table class="min-w-full border border-gray-200 divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                <tr>
                    @foreach(array_keys($data->first()->getAttributes() ?? []) as $col)
                        <th class="px-4 py-2 text-left text-gray-600 font-semibold capitalize">{{ str_replace('_',' ',$col) }}</th>
                    @endforeach
                    <th class="px-4 py-2 text-center text-gray-600 font-semibold">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($data as $row)
                    <tr class="hover:bg-gray-50 transition">
                        @foreach($row->getAttributes() as $value)
                            <td class="px-4 py-2 border-t text-gray-700">{{ $value }}</td>
                        @endforeach
                        <td class="px-4 py-2 text-center border-t">
                            <a href="{{ route('departments.edit', $row->id) }}"
                               class="text-blue-600 hover:underline">✏️</a>
                            <form method="POST" action="{{ route('departments.destroy', $row->id) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:underline ml-2"
                                        onclick="return confirm('Are you sure?')">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="100%" class="px-4 py-4 text-center text-gray-500">No data available</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
