@extends('layouts.app')
@section('content')
<div class="p-6 max-w-6xl mx-auto">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">📋 Employees List</h2>
    <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">➕ Add Employees</a>
    <div class="mt-4 overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full border border-gray-200 divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100">
                @php $cols = count($data) ? array_keys($data->first()->getAttributes()) : []; @endphp
                <tr>
                    @foreach($cols as $col)
                        <th class="px-4 py-2 text-left text-gray-600">{{ str_replace('_', ' ', $col) }}</th>
                    @endforeach
                    <th class="px-4 py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr class="border-t hover:bg-gray-50">
                    @foreach($row->getAttributes() as $val)
                        <td class="px-4 py-2">{{ $val }}</td>
                    @endforeach
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('employees.edit', $row->id) }}" class="text-blue-600">✏️</a>
                        <form method="POST" action="{{ route('employees.destroy', $row->id) }}" class="inline">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Are you sure?')" class="text-red-600 ml-2">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection