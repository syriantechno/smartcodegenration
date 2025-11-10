@extends('layouts.builder')

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- 🧩 Title -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-slate-700 flex items-center gap-2">
                🗃️ إدارة الجداول
            </h1>
            <a href="{{ url('/builder/relations') }}"
               class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                الذهاب إلى العلاقات →
            </a>
        </div>

        <!-- 🧠 نموذج إنشاء جدول جديد -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-10 border border-gray-100">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">➕ إنشاء جدول جديد</h2>
            <div class="p-6">
                <form id="builder-form" class="space-y-6">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="table" class="block text-sm font-medium text-gray-700">
                                Table Name
                                <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-info-circle ml-1"></i>
                                Use English names (a-z, 0-9, _)
                            </span>
                        </div>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 4a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V5a1 1 0 00-1-1H5zm0-2h10a3 3 0 013 3v10a3 3 0 01-3 3H5a3 3 0 01-3-3V5a3 3 0 013-3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="text" id="table" name="table"
                                   class="focus:ring-2 focus:ring-blue-300 focus:border-blue-300 block w-full pr-10 sm:text-sm border-gray-200 rounded-md p-2.5 border-2"
                                   placeholder="e.g., users, products"
                                   pattern="[a-zA-Z_][a-zA-Z0-9_]*"
                                   title="Must start with a letter or underscore and contain only letters, numbers, and underscores"
                                   required>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Use descriptive names like: users, products, customers
                        </p>
                    </div>

                <!-- الحقول -->
                <div id="fields-area" class="space-y-3"></div>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-200 mt-6">
                        <div class="flex space-x-3 space-x-reverse">
                            <button type="button" onclick="addField()"
                                    class="btn btn-primary inline-flex items-center">
                                <svg class="h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                إضافة حقل
                            </button>
                            <button type="button" onclick="addSampleFields()"
                                    class="btn btn-secondary inline-flex items-center">
                                <svg class="h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                حقول نموذجية
                            </button>
                        </div>
                        <button type="submit"
                                class="btn btn-success inline-flex items-center">
                            <svg class="h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            حفظ الجدول
                        </button>
                    </div>
            </form>
        </div>

                </form>
            </div>
        </div>

        <!-- 📋 قائمة الجداول المحفوظة -->
        <div class="bg-white shadow-lg rounded-t-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-700 flex items-center gap-2">
                    📁 الجداول المحفوظة
                </h2>
            </div>
                <div class="flex items-center space-x-2">
                    <button onclick="refreshTables()" class="text-gray-500 hover:text-blue-600 p-1 rounded-full hover:bg-gray-100 transition-colors" title="Refresh">
                        <i class="fas fa-sync-alt text-sm"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                @if(empty($savedTables))
                    <div class="text-center py-12 px-4">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-50 text-blue-600 mb-4">
                            <i class="fas fa-table text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">لا توجد جداول محفوظة</h3>
                        <p class="mt-2 text-gray-500">ابدأ بإنشاء جدول جديد باستخدام النموذج أعلاه</p>
                        <div class="mt-6">
                            <button type="button" onclick="document.getElementById('table').focus()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus ml-2"></i>
                                إنشاء جدول جديد
                            </button>
                        </div>
                    </div>
                @else
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        اسم الجدول
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        الحقول
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        تاريخ الإنشاء
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        آخر تحديث
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        الإجراءات
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($savedTables as $table)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-md bg-blue-50 text-blue-600">
                                                    <i class="fas fa-table"></i>
                                                </div>
                                                <div class="mr-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $table['name'] }}</div>
                                                    <div class="text-xs text-gray-500">{{ $table['file'] ?? '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ $table['fields'] }} حقول
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="far fa-clock mr-1 text-gray-400"></i>
                                                <span>{{ $table['created_at'] ? \Carbon\Carbon::parse($table['created_at'])->diffForHumans() : 'Unknown' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="far fa-clock mr-1 text-gray-400"></i>
                                                <span>{{ $table['updated_at'] ? \Carbon\Carbon::parse($table['updated_at'])->diffForHumans() : 'Unknown' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-center gap-2">
                                            <button data-table="{{ $table['name'] }}"
                                                    class="inject-btn flex items-center px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 transition-colors hover:shadow-md"
                                                    title="حقن الجدول في قاعدة البيانات">
                                                <i class="fas fa-database ml-1 text-sm"></i>
                                                <span>حقن</span>
                                            </button>
                                            <button class="flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors hover:shadow-sm"
                                                    title="تعديل الجدول">
                                                <i class="far fa-edit"></i>
                                            </button>
                                            <button class="flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors hover:shadow-sm"
                                                    title="حذف الجدول">
                                                <i class="far fa-trash-alt"></i>
                                            </button>
                                        </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @if(!empty($dbTables))
        <div class="bg-white shadow-lg rounded-t-xl border-t border-l border-r border-gray-100 mt-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">🔍 مستكشف قاعدة البيانات</h2>
                <button onclick="location.reload()" class="text-gray-500 hover:text-blue-600 p-1 rounded-full hover:bg-gray-100 transition-colors" title="تحديث">
                    <i class="fas fa-sync-alt text-sm"></i>
                </button>
            </div>

            <div class="overflow-x-auto border-b border-l border-r border-gray-200 rounded-b">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم الجدول</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الأعمدة</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">عدد السجلات</th>
                    </tr>
                    </thead>
                <tbody>
                @foreach($dbTables as $t)
                    @php
                        // ✅ استخرج اسم الجدول من الكائن (مثل النسخة القديمة تمامًا)
                        $tableName = array_values((array)$t)[0];

                        // ✅ اجلب الأعمدة وعدد السجلات
                        $cols = \DB::select("SHOW COLUMNS FROM `$tableName`");
                        $count = \DB::table($tableName)->count();
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 flex items-center justify-center rounded-md bg-blue-50 text-blue-600">
                                    <i class="fas fa-table"></i>
                                </div>
                                <div class="mr-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $tableName }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                {{ count($cols) }} حقول
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                {{ number_format($count) }} سجل
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif





    @push('scripts')
    <script>
        // Initialize field index
        let fieldIndex = 0;

        // Function to add sample fields
        function addSampleFields() {
            // Clear existing fields
            document.getElementById('fields-area').innerHTML = '';
            fieldIndex = 0;

            // Add sample fields
            const sampleFields = [
                {name: 'id', type: 'integer', required: true},
                {name: 'name', type: 'string', required: true},
                {name: 'email', type: 'string', required: true},
                {name: 'created_at', type: 'date', required: false},
                {name: 'updated_at', type: 'date', required: false}
            ];

            sampleFields.forEach(field => {
                addField(field);
            });
        }

        // Function to add a new field row
        function addField(fieldData = {}) {
            const container = document.getElementById('fields-area');
            const div = document.createElement('div');
            div.className = 'field-row border border-gray-200 shadow-sm p-3 rounded-lg bg-white hover:bg-gray-50 flex items-center gap-2 mb-3 transition-all duration-200';
            div.innerHTML = `
                <input type="text" name="fields[${fieldIndex}][name]" placeholder="Field name"
                    class="border border-gray-200 rounded-lg px-3 py-1.5 w-1/3 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 focus:outline-none transition-colors" required>

                <select name="fields[${fieldIndex}][type]"
                    class="border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 focus:outline-none transition-colors">
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="decimal">Decimal</option>
                    <option value="boolean">Boolean</option>
                    <option value="date">Date</option>
                    <option value="text">Text</option>
                </select>

                <label class="flex items-center gap-2 text-sm text-gray-600 ml-2">
                    <input type="checkbox" name="fields[${fieldIndex}][required]" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                    <span>Required</span>
                </label>

                <button type="button" onclick="this.closest('.field-row').remove()"
                        class="text-red-400 hover:text-red-600 font-bold text-lg ml-auto transition-colors duration-200">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
            fieldIndex++;
        }

        // Handle AJAX responses with SweetAlert2
        function handleAjaxResponse(response) {
            if (response && response.swal) {
                return Swal.fire(response.swal);
            }
            return Promise.resolve(response);
        }

        // Show error message
        function showError(message) {
            return Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        }

        // Initialize form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('builder-form');
            if (!form) return;

            // Add first field by default
            if (fieldIndex === 0) {
                addField();
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const fields = [];
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;

                // Get table name
                const tableName = document.getElementById('table').value.trim();
                if (!tableName) {
                    showError('Please enter a table name');
                    return;
                }

                // Add table name to form data
                formData.set('table', tableName);

                // Collect all field data
                document.querySelectorAll('.field-row').forEach(row => {
                    const nameInput = row.querySelector('[name$="[name]"]');
                    const typeInput = row.querySelector('[name$="[type]"]');
                    const requiredInput = row.querySelector('[name$="[required]"]');

                    if (nameInput && typeInput) {
                        fields.push({
                            name: nameInput.value,
                            type: typeInput.value,
                            required: requiredInput ? requiredInput.checked : false
                        });
                    }
                });

                if (fields.length === 0) {
                    showError('Please add at least one field');
                    return;
                }

                // Update button state
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving...';

                // Create data object to send
                const data = {
                    table: tableName,
                    fields: fields
                };

                // Send data to server
                fetch('{{ route("builder.tables.save") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.table) {
                        // Show success message and reload
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Table saved successfully!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                    return handleAjaxResponse(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('An unexpected error occurred. Please try again.');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });

            // Handle inject table button
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('inject-btn') || e.target.closest('.inject-btn')) {
                    const button = e.target.classList.contains('inject-btn') ? e.target : e.target.closest('.inject-btn');
                    const tableName = button.getAttribute('data-table');

                    if (!tableName) {
                        showError('Table name is missing');
                        return;
                    }

                    // Show confirmation dialog
                    Swal.fire({
                        title: 'Inject Table',
                        text: `Are you sure you want to inject table '${tableName}' to database?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, inject it!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const originalText = button.innerHTML;
                            button.disabled = true;
                            button.innerHTML = 'Injecting...';

                            // إنشاء نموذج وإرساله كـ POST
                            const formData = new FormData();
                            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                            fetch(`/builder/inject/${encodeURIComponent(tableName)}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: data.message || 'Table injected successfully!',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Failed to inject table');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showError(error.message || 'An error occurred while injecting the table');
                            })
                            .finally(() => {
                                button.disabled = false;
                                button.innerHTML = originalText;
                            });
                        }
                    });
                }
            });
        });
    </script>
    @endpush
@endsection
