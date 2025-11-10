<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-700">

<!-- Navbar -->
<nav class="bg-indigo-600 text-white px-6 py-3 shadow-md">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="/" class="text-lg font-semibold tracking-wide">🧱 LaraBuilder</a>
        <div class="space-x-4">
            <a href="/builder/tables" class="hover:underline">Tables</a>
            <a href="#" class="hover:underline opacity-70">Relations</a>
            <a href="#" class="hover:underline opacity-70">Forms</a>
        </div>
    </div>
</nav>

<!-- Main content -->
<main class="max-w-7xl mx-auto py-8 px-4">
    @yield('content')
</main>

<!-- Footer -->
<footer class="text-center py-4 text-sm text-slate-500 border-t mt-10">
    LaraBuilder © {{ date('Y') }} — Prototype
</footer>

</body>
</html>
