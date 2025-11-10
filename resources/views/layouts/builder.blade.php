<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AutoCrudSmart Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex bg-gray-100 text-gray-800">
<!-- 🧭 Sidebar -->
@include('builder.sidebar')

<!-- 🧩 Main Content -->
<main class="flex-1 min-h-screen p-8 overflow-y-auto">
    @yield('content')
</main>
</body>
</html>
