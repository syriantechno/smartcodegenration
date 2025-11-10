@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-xl font-semibold mb-4">Department Records</h1>
    <form method="POST" action="">
        @csrf
        <div class="grid grid-cols-2 gap-4 mb-4">
            <input type='text' name='id' placeholder='id' class='form-control' />
            <input type='text' name='name' placeholder='name' class='form-control' />        </div>
        <button class="btn btn-primary">Save</button>
    </form>

    <table class="table mt-6">
        <thead><tr><th>ID</th><th>id</th><th>name</th>        </tr></thead>
        <tbody>
            @foreach($items as $item)
            <tr><td>{{ $item->id }}</td><td>{{ $item->id }}</td><td>{{ $item->name }}</td>            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection