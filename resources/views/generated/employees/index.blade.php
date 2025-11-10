@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-xl font-semibold mb-4">Employee Records</h1>
    <form method="POST" action="">
        @csrf
        <div class="grid grid-cols-2 gap-4 mb-4">

            <input type="text" name="name" placeholder="name" class="form-control" />
            <input type="text" name="email" placeholder="email" class="form-control" />

            <select name="department_id" class="form-control">
                <option value="">-- اختر القسم --</option>
                @foreach($departments as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

        </div>
        <button class="btn btn-primary">Save</button>
    </form>


    <table class="table mt-6">
        <thead><tr><th>ID</th><th>id</th><th>name</th><th>email</th><th>department_id</th>        </tr></thead>
        <tbody>
            @foreach($items as $item)
            <tr><td>{{ $item->id }}</td><td>{{ $item->id }}</td><td>{{ $item->name }}</td><td>{{ $item->email }}</td><td>{{ $item->department_id }}</td>            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
