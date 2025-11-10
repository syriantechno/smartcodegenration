@extends('layouts.app')

@section('content')
    <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Form Preview & Designer</h2>

        <form method="GET" action="">
            <select name="table" onchange="this.form.submit()" class="form-select mb-4">
                <option value="">-- Select Table --</option>
                @foreach($tables as $t)
                    <option value="{{ $t }}" {{ $selected === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </form>

        @if($selected)
            <div id="preview" class="border p-4 rounded bg-gray-50">
                @foreach($fields as $f)
                    @php
                        $name = $f['name'];
                        $label = ucfirst($f['label'] ?? $name);
                        $input = $f['input'] ?? 'text';
                    @endphp
                    <div class="mb-4">
                        <label class="block font-medium">{{ $label }}</label>

                        @if($input === 'select')
                            <select class="form-control border-gray-400 bg-white">
                                <option>Sample Option</option>
                            </select>
                        @elseif($input === 'textarea')
                            <textarea class="form-control border-gray-400 bg-white"></textarea>
                        @elseif($input === 'switch')
                            <input type="checkbox">
                        @else
                            <input type="{{ $input }}" class="form-control border-gray-400 bg-white">
                        @endif

                        <div class="mt-2 text-sm text-gray-500">
                            نوع الإدخال الحالي:
                            <select onchange="updateFieldType('{{ $selected }}','{{ $name }}', this.value)">
                                @foreach(['text','email','number','textarea','date','select','switch'] as $type)
                                    <option value="{{ $type }}" {{ $input === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- 🔹 زر التوليد -->
            <div class="mt-6 text-right space-x-2">
                <button onclick="generateForm('{{ $selected }}')" class="btn btn-primary px-4 py-2">
                    🔄 Generate Form
                </button>
                <button onclick="generateController('{{ $selected }}')" class="btn btn-outline-primary px-4 py-2">
                    ⚙️ Generate Controller
                </button>
                <button onclick="generateModel('{{ $selected }}')" class="btn btn-outline-secondary px-4 py-2">
                    🧩 Generate Model
                </button>
                <button onclick="generateIndex('{{ $selected }}')" class="btn btn-outline-success px-4 py-2">
                    📋 Generate Index View
                </button>

            </div>

        @endif
    </div>

    <script>
        async function updateFieldType(table, field, input) {
            const res = await fetch("{{ url('/builder/preview/update') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ table, field, input })
            });
            const data = await res.json();
            if (data.status === 'ok') {
                console.log(`✅ ${field} updated to ${input}`);
            } else {
                console.error(data);
            }
        }

        /** 🔹 توليد الفورم مباشرة */
        async function generateForm(table) {
            const res = await fetch("{{ url('/builder/generate-form') }}/" + table);
            const data = await res.json();
            alert(data.status === 'ok' ? data.message : data.error);
        }

        /** 🔹 توليد الكنترولر */
        async function generateController(table) {
            const res = await fetch("{{ url('/builder/generate-controller') }}/" + table);
            const data = await res.json();
            alert(data.status === 'ok'
                ? `✅ ${data.controller} generated successfully`
                : `❌ ${data.error}`);
        }
        /** 🔹 توليد الموديل */
        async function generateModel(table) {
            const res = await fetch("{{ url('/builder/generate-model') }}/" + table);
            const data = await res.json();
            alert(data.status === 'ok'
                ? `✅ Model ${data.model} generated successfully`
                : `❌ ${data.error}`);
        }
        /** 🔹 توليد صفحة index */
        async function generateIndex(table) {
            const res = await fetch("{{ url('/builder/generate-index') }}/" + table);
            const data = await res.json();
            alert(data.status === 'ok'
                ? data.message
                : `❌ ${data.error}`);
        }


    </script>
@endsection
