@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
        <h1 class="text-xl font-bold text-slate-700 mb-4">🧱 Dynamic Form Builder</h1>

        <form id="select-table" class="mb-6">
            <label class="block mb-2 text-slate-600 font-semibold">Select Table</label>
            <select name="table" class="border rounded-md px-3 py-2 w-full" onchange="this.form.submit()">
                <option value="">-- choose table --</option>
                @foreach($tables as $t)
                    <option value="{{ $t }}" @selected($selected===$t)>{{ $t }}</option>
                @endforeach
            </select>
        </form>

        @if($selected)
            <form id="dynamic-form" class="space-y-4">
                @csrf
                <input type="hidden" name="table" value="{{ $selected }}">

                @foreach($fields as $f)
                    @php $name=$f['name']; $type=$f['type']; @endphp
                    <div>
                        <label class="block mb-1 text-slate-600 font-medium">{{ ucfirst($name) }}</label>

                        @if(isset($lookups[$name]))
                            <select name="{{ $name }}" class="border rounded-md px-3 py-2 w-full">
                                <option value="">-- select --</option>
                                @foreach($lookups[$name] as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif($type==='text' || $type==='string')
                            <input type="text" name="{{ $name }}" class="border rounded-md px-3 py-2 w-full">
                        @elseif($type==='integer')
                            <input type="number" name="{{ $name }}" class="border rounded-md px-3 py-2 w-full">
                        @elseif($type==='date')
                            <input type="date" name="{{ $name }}" class="border rounded-md px-3 py-2 w-full">
                        @else
                            <input type="text" name="{{ $name }}" class="border rounded-md px-3 py-2 w-full">
                        @endif
                    </div>
                @endforeach

                <div class="text-right">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        @endif
    </div>

    <script>
        document.getElementById('dynamic-form')?.addEventListener('submit', async e=>{
            e.preventDefault();
            const res = await fetch('{{ route("builder.form.store") }}', {
                method:'POST',
                body:new FormData(e.target)
            });
            const data = await res.json();
            alert(data.message || data.error);
        });
    </script>
@endsection
