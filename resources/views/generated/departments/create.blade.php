@extends('layouts.app')
@section('content')
<div class='p-6 max-w-3xl mx-auto bg-white rounded-xl shadow-md space-y-6'>
<h2 class='text-2xl font-bold mb-4'>Create Departments</h2>
<form method='POST' action='{{ route("departments.store") }}' class='space-y-4'>
@csrf
<div class='flex flex-col'>
<label for='id' class='font-medium text-gray-700 mb-1'>Id</label>
<input type='number' name='id' id='id' style='borderColor:#a41ce3;focusColor:#18b8cd;boxShadow:0 6px 16px rgba(0,0,0,0.3);background:#ffffff;borderRadius:18px;' class='border p-2 w-full focus:ring-2 focus:ring-blue-300 focus:outline-none'>
</div>
<div class='flex flex-col'>
<label for='name' class='font-medium text-gray-700 mb-1'>Name</label>
<input type='text' name='name' id='name' style='borderColor:#6cd5ea;borderRadius:3px;boxShadow:0 4px 10px rgba(0,0,0,0.2);focusColor:#8d1bda;' class='border p-2 w-full focus:ring-2 focus:ring-blue-300 focus:outline-none'>
</div>
<div class='flex flex-col'>
<label for='manager' class='font-medium text-gray-700 mb-1'>Manager</label>
<input type='text' name='manager' id='manager' style='boxShadow:0 6px 16px rgba(0,0,0,0.3);focusColor:#16c542;borderRadius:50px;borderColor:#227ef7;background:#ffffff;' class='border p-2 w-full focus:ring-2 focus:ring-blue-300 focus:outline-none'>
</div>
<button type='submit' class='px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 shadow'>💾 Save</button>
</form>
</div>
@endsection