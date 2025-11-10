<aside class="w-64 bg-white border-r shadow-lg h-screen sticky top-0 flex flex-col">
    <!-- 🔹 العنوان -->
    <div class="px-6 py-5 border-b bg-blue-600 text-white font-bold text-lg flex items-center gap-2">
        ⚙️ AutoCrudSmart
    </div>

    <!-- 🔹 القائمة -->
    <nav class="flex-1 p-4 space-y-1 text-gray-700 text-sm">

        <a href="{{ route('builder.dashboard') }}"
           class="flex items-center gap-2 p-2 rounded transition-all hover:bg-blue-50 {{ request()->routeIs('builder.dashboard') ? 'bg-blue-100 font-semibold text-blue-700' : '' }}">
            🏠 <span>Dashboard</span>
        </a>

        <a href="{{ route('builder.tables') }}"
           class="flex items-center gap-2 p-2 rounded transition-all hover:bg-blue-50 {{ request()->routeIs('builder.tables') ? 'bg-blue-100 font-semibold text-blue-700' : '' }}">
            🧱 <span>Tables Manager</span>
        </a>

        <a href="{{ route('builder.relations') }}"
           class="flex items-center gap-2 p-2 rounded transition-all hover:bg-blue-50 {{ request()->routeIs('builder.relations') ? 'bg-blue-100 font-semibold text-blue-700' : '' }}">
            🔗 <span>Relations Manager</span>
        </a>

        <a href="{{ url('/builder/form-master') }}"
           class="flex items-center gap-2 p-2 rounded hover:bg-blue-50 {{ request()->is('builder/form-master') ? 'bg-blue-100 font-semibold text-blue-700' : '' }}">
            🎨 <span>Form Designer</span>
        </a>



        <a href="{{ route('builder.crud') }}"
           class="flex items-center gap-2 p-2 rounded transition-all hover:bg-blue-50 {{ request()->routeIs('builder.crud') ? 'bg-blue-100 font-semibold text-blue-700' : '' }}">
            ⚙️ <span>CRUD Generator</span>
        </a>

        <a href="{{ route('builder.output') }}"
           class="flex items-center gap-2 p-2 rounded transition-all hover:bg-blue-50 {{ request()->routeIs('builder.output') ? 'bg-blue-100 font-semibold text-blue-700' : '' }}">
            📂 <span>Generated Output</span>
        </a>
    </nav>

    <!-- 🔹 الفوتر -->
    <div class="p-4 border-t text-xs text-gray-500 text-center">
        AutoCrudSmart v1.0<br>
        <span class="text-gray-400">by Syriantechno</span>
    </div>
</aside>
