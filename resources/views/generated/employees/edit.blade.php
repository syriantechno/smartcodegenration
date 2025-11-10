@extends('layouts.app')
@section('content')
<div class='p-6 max-w-3xl mx-auto bg-white rounded-xl shadow-md space-y-6'>
<h2 class='text-2xl font-bold mb-4'>Create Employees</h2>
<form method='POST' action='{{ route("employees.store") }}' class='space-y-4'>
@csrf
<div class='flex flex-col'>
<label for='id' class='font-medium text-gray-700 mb-1'>Id</label>
<input type='number' name='id' id='id' style='' class='border p-2 w-full focus:ring-2 focus:ring-blue-300'>
</div>
<div class='flex flex-col'>
<label for='name' class='font-medium text-gray-700 mb-1'>Name</label>
<input type='text' name='name' id='name' style='' class='border p-2 w-full focus:ring-2 focus:ring-blue-300'>
</div>
<div class='flex flex-col'>
<label for='email' class='font-medium text-gray-700 mb-1'>Email</label>
<input type='email' name='email' id='email' style='' class='border p-2 w-full focus:ring-2 focus:ring-blue-300'>
</div>
<div class='flex flex-col'>
<label for='phone' class='font-medium text-gray-700 mb-1'>Phone</label>
<input type='text' name='phone' id='phone' style='' class='border p-2 w-full focus:ring-2 focus:ring-blue-300'>
</div>
<div class='flex flex-col'>
<label for='department_id' class='font-medium text-gray-700 mb-1'>Department</label>
<?php $options = App\Models\Department::all(); ?>
<select name='department_id' id='department_id' style='' class='border p-2 w-full'>
<option value=''>Select...</option>
@foreach($options as $opt)<option value='{{ $opt->id }}'>{{ $opt->name }}</option>@endforeach
</select>
</div>
<div class='flex flex-col'>
<label for='posation_id' class='font-medium text-gray-700 mb-1'>Posation</label>
<?php $options = App\Models\Posation::all(); ?>
<select name='posation_id' id='posation_id' style='' class='border p-2 w-full'>
<option value=''>Select...</option>
@foreach($options as $opt)<option value='{{ $opt->id }}'>{{ $opt->name }}</option>@endforeach
</select>
</div>
<div class='flex flex-col'>
<label for='test33_id' class='font-medium text-gray-700 mb-1'>Test33</label>
<?php $options = App\Models\Test33::all(); ?>
<select name='test33_id' id='test33_id' style='' class='border p-2 w-full'>
<option value=''>Select...</option>
@foreach($options as $opt)<option value='{{ $opt->id }}'>{{ $opt->name }}</option>@endforeach
</select>
</div>
<button type='submit' class='px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>💾 Save</button>
</form>
</div>
@endsection