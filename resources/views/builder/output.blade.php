@extends('layouts.builder')

@section('content')
    <div class="flex-1 p-8 bg-gray-50 min-h-screen">
        <h1 class="text-2xl font-bold mb-6 text-slate-800">📂 Generated Output</h1>

        @if(empty($views))
            <div class="bg-white border rounded-lg shadow p-6 text-center text-slate-500">
                لم يتم توليد أي ملفات بعد.
            </div>
        @else
            <div class="bg-white border rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-slate-700 mb-4">الملفات التي تم توليدها</h2>
                <ul class="divide-y">
                    @foreach($views as $v)
                        <li class="py-3 flex justify-between items-center">
                            <span class="font-medium text-slate-700">{{ $v }}</span>
                            <button onclick="viewFile('{{ $v }}')"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                👁️ عرض الملف
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- 🔹 Modal عرض المحتوى -->
    <div id="fileModal"
         class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
        <div class="bg-white w-3/4 max-h-[80vh] rounded-lg shadow-lg overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 id="modalTitle" class="font-semibold text-slate-800">📄 File Viewer</h3>
                <button onclick="closeModal()" class="text-slate-500 hover:text-red-600 text-xl">&times;</button>
            </div>
            <pre id="fileContent"
                 class="p-4 overflow-y-auto bg-gray-900 text-green-300 text-xs font-mono flex-1 whitespace-pre-wrap"></pre>
        </div>
    </div>

    <script>
        async function viewFile(filename) {
            try {
                const res = await fetch(`/builder/output/view/${filename}`);
                const data = await res.json();
                if (data.error) {
                    alert('⚠️ ' + data.error);
                    return;
                }
                document.getElementById('modalTitle').textContent = '📄 ' + filename;
                document.getElementById('fileContent').textContent = data.content;
                document.getElementById('fileModal').classList.remove('hidden');
                document.getElementById('fileModal').classList.add('flex');
            } catch (e) {
                alert('❌ Error loading file content');
            }
        }

        function closeModal() {
            const modal = document.getElementById('fileModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
@endsection
