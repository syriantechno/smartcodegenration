@extends('layouts.builder')

@section('content')
<div class="flex">
    <!-- Form Design Content -->
    <div class="flex-1 p-8 bg-gray-50 min-h-screen space-y-8">
        <!-- Alert Messages -->
        <div id="alertMessage" class="hidden fixed top-4 right-4 z-50"></div>
        <div>
            <h2 class="text-3xl font-bold text-slate-800 mb-2">🎨 Form Designer — {{ ucfirst($table) }}</h2>
            <p class="text-gray-500 text-sm">Customize inputs, styles, and preview the final form live.</p>
        </div>

            <!-- Form Fields -->
            <div id="previewArea" class="border p-6 rounded-xl bg-white shadow-sm space-y-6">
                @foreach($fields as $f)
                    @php
                        $label = ucfirst(str_replace('_',' ', $f['label'] ?? $f['name']));
                        $input = $f['input'] ?? 'text';
                        $style = $f['style'] ?? 'classic';
                    @endphp
                    <div class="field-block border border-gray-200 rounded-lg p-4 mb-4 transition-all hover:shadow-md"
                         data-name="{{ $f['name'] }}"
                         onclick="event.stopPropagation(); selectField(this);">

                        <div class="flex justify-between items-center mb-3">
                            <span class="font-medium text-gray-800">{{ $label }}</span>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $input }}</span>
                        </div>

                        <div class="field-preview">
                            @if($input === 'textarea')
                                <textarea class="w-full border rounded p-2 text-sm" rows="2" placeholder="{{ $label }}" readonly></textarea>
                            @elseif($input === 'select')
                                <select class="w-full border rounded p-2 text-sm bg-gray-50">
                                    <option value="">Select {{ $label }}</option>
                                    <option value="2">Option 2</option>
                                </select>
                            @elseif($input === 'switch')
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" disabled>
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            @else
                                <input type="{{ $input }}"
                                       class="w-full border rounded p-2 text-sm"
                                       placeholder="{{ $label }}"
                                       value="{{ $f['value'] ?? '' }}"
                                       @if(isset($f['required']) && $f['required']) required @endif
                                       readonly>
                            @endif

                                <div class="flex-1 min-w-[120px]">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Background Color</label>
                                    <div class="flex items-center">
                                        <input type="color"
                                               value="{{ $f['backgroundColor'] ?? '#ffffff' }}"
                                               oninput="updateProperty('background', this.value)"
                                               class="w-8 h-8 p-1 border rounded cursor-pointer">
                                        <span class="ml-2 text-xs text-gray-600">Custom Background Color</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Quick Options -->
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <div class="flex flex-wrap gap-3">
                                <div class="flex-1 min-w-[120px]">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Field Style</label>
                                    <select onchange="updateFieldStyle('{{ $table }}', '{{ $f['name'] }}-style', this.value)"
                                            class="w-full text-sm border rounded p-2 bg-white">
                                        <option value="classic" {{ $style === 'classic' ? 'selected' : '' }}>Classic Style</option>
                                        <option value="modern" {{ $style === 'modern' ? 'selected' : '' }}>Modern Style</option>
                                        <option value="minimal" {{ $style === 'minimal' ? 'selected' : '' }}>Minimal Style</option>
                                        <option value="rounded" {{ $style === 'rounded' ? 'selected' : '' }}>Rounded Style</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

            <!-- Live Preview -->
            <div id="livePreviewContainer" class="mt-8 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🧩 معاينة النموذج المباشرة</h3>
                <div class="space-y-4 p-4 bg-gray-50 rounded-lg">
                    @foreach($fields as $f)
                        @php
                            $label = ucfirst(str_replace('_',' ', $f['label'] ?? $f['name']));
                            $input = $f['input'] ?? 'text';
                        @endphp
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                            @if($input === 'textarea')
                                <textarea class="w-full border rounded p-2 text-sm" rows="3" placeholder="{{ $label }}"></textarea>
                            @elseif($input === 'select')
                                <select class="w-full border rounded p-2 text-sm bg-white">
                                    <option value="">Select {{ $label }}</option>
                                    <option value="1">Option 1</option>
                                    <option value="2">Option 2</option>
                                </select>
                            @elseif($input === 'switch')
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            @else
                                <input type="{{ $input }}" 
                                       class="w-full border rounded p-2 text-sm" 
                                       placeholder="{{ $label }}"
                                       @if(isset($f['required']) && $f['required']) required @endif>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Generate Form Button -->
            <div class="text-right pt-6">
                <button onclick="generateForm('{{ $table }}')"
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 shadow">
                    Generate Form
                </button>
            </div>

            <!-- Field Settings -->
            <div id="fieldSettings" class="fixed top-0 right-0 h-full w-80 bg-white shadow-lg z-50 transform translate-x-full transition-transform duration-300 p-6 overflow-y-auto">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h3 class="text-lg font-bold text-gray-800">Field Settings</h3>
                    <button onclick="closeSidebar()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="fieldSettingsContent">
                    <p class="text-gray-500 text-sm">Select a field to view its settings</p>
                </div>
            </div>

        <!-- Sidebar -->
        <div id="sidebar" class="w-80 bg-white border-l border-gray-200 shadow-xl p-6 fixed right-0 top-0 h-full overflow-y-auto z-50 transform translate-x-full transition-transform duration-300">
            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                <h3 class="text-lg font-bold text-gray-700">Field Settings</h3>
                <button type="button" onclick="window.closeSidebar && window.closeSidebar()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <p id="activeFieldName" class="text-sm text-gray-500 mb-4">اختر حقلًا للتعديل</p>

            <!-- Preview Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h4 class="text-sm font-medium text-gray-700 mb-3">🧩 Live Form Preview</h4>
                <div id="fieldPreview" class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                    @foreach($fields as $f)
                        @php
                            $label = ucfirst(str_replace('_',' ', $f['label'] ?? $f['name']));
                            $input = $f['input'] ?? 'text';
                            $style = $f['style'] ?? 'classic';
                        @endphp
                        <div class="mb-4 field-preview" data-field-name="{{ $f['name'] }}">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                            @if($input === 'textarea')
                                <textarea class="w-full border rounded p-2 text-sm" rows="3" placeholder="{{ $label }}" readonly></textarea>
                            @elseif($input === 'select')
                                <select class="w-full border rounded p-2 text-sm bg-white">
                                    <option value="">Select {{ $label }}</option>
                                    <option value="1">Option 1</option>
                                    <option value="2">Option 2</option>
                                </select>
                            @elseif($input === 'switch')
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            @else
                                <input type="{{ $input }}"
                                       class="w-full border rounded p-2 text-sm"
                                       placeholder="{{ $label }}"
                                       @if(isset($f['required']) && $f['required']) required @endif>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">لون الحدود</label>
                    <input type="color" id="borderColor" class="w-full h-10 cursor-pointer border rounded"
                           onchange="updateProperty('borderColor', this.value)" value="#e5e7eb">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">لون الخلفية</label>
                    <input type="color" id="bgColor" class="w-full h-10 cursor-pointer border rounded"
                           onchange="updateProperty('background', this.value)" value="#ffffff">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">لون التركيز</label>
                    <input type="color" id="focusColor" class="w-full h-10 cursor-pointer border rounded"
                           onchange="updateProperty('focusColor', this.value)" value="#3b82f6">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">
                        نصف القطر: <span id="radiusValue">8</span> بكسل
                    </label>
                    <input type="range" min="0" max="20" value="8" id="radius" class="w-full"
                           oninput="document.getElementById('radiusValue').textContent = this.value; updateProperty('borderRadius', this.value)">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">الظل</label>
                    <select id="shadow" class="w-full border rounded p-2 text-sm"
                            onchange="updateProperty('boxShadow', this.value)">
                        <option value="none">بدون ظل</option>
                        <option value="0 2px 5px rgba(0,0,0,0.1)">خفيف</option>
                        <option value="0 4px 10px rgba(0,0,0,0.15)">متوسط</option>
                        <option value="0 6px 16px rgba(0,0,0,0.2)">قوي</option>
                    </select>
                </div>

                <div class="pt-4 border-t">
                    <button onclick="closeSidebar()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        حفظ التغييرات
                    </button>
                </div>
            </div>
        </div>

            <!-- إضافة حقل مخفي لتخزين اسم الجدول -->
            <input type="hidden" name="table" value="{{ $table }}">
        </div>
    </div>



@push('scripts')
<script>
    // تعريف الدوال في النطاق العام
    window.selectField = function(element) {
        // إيقاف الانتشار لتجنب إغلاق القائمة المنسدلة عند النقر عليها
        event.stopPropagation();

        // إزالة التحديد من الحقل السابق
        document.querySelectorAll('.field-block').forEach(el => {
            el.classList.remove('ring-2', 'ring-blue-500');
        });

        // تعيين الحقل الجديد
        window.selectedField = element;
        element.classList.add('ring-2', 'ring-blue-500');

        // إظهار الشريط الجانبي
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.remove('translate-x-full');
            sidebar.classList.add('translate-x-0');
        }

        // تحديث الشريط الجانبي
        updateSidebar(element);

        // إظهار رسالة تنبيه
        showAlert('تم تحديد الحقل بنجاح', 'success');
    };

    // تعريف دالة updateSidebar
    window.updateSidebar = function(fieldElement) {
        // التحقق من وجود العنصر أولاً
        const fieldSettings = document.getElementById('fieldSettings');
        if (!fieldSettings) {
            console.error('عنصر fieldSettings غير موجود في الصفحة');
            return;
        }

        // التحقق من وجود العنصر المطلوب
        if (!fieldElement) {
            console.error('لم يتم تحديد عنصر حقل صالح');
            return;
        }

        // الحصول على بيانات الحقل
        const fieldName = fieldElement.dataset?.name || 'غير معروف';
        const labelElement = fieldElement.querySelector('.font-medium');
        const fieldLabel = labelElement ? labelElement.textContent : 'عنوان غير معروف';
        const inputElement = fieldElement.querySelector('.field-preview input, .field-preview textarea, .field-preview select');
        const fieldType = inputElement?.type || 'text';

        // إنشاء محتوى الشريط الجانبي
        const sidebarContent = `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم الحقل</label>
                    <input type="text" value="${fieldName}" class="w-full border rounded p-2 text-sm" disabled>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عنوان الحقل</label>
                    <input type="text" value="${fieldLabel.replace(/"/g, '&quot;')}"
                           onchange="window.updateFieldLabel('${fieldName}', this.value)"
                           class="w-full border rounded p-2 text-sm">
                </div>

                <div class="pt-4 border-t">
                    <button onclick="window.removeField('${fieldName}')"
                            class="w-full bg-red-100 text-red-700 hover:bg-red-200 py-2 px-4 rounded text-sm font-medium transition-colors">
                        🗑️ حذف الحقل
                    </button>
                </div>
            </div>
        `;

        // تحديث محتوى الشريط الجانبي
        fieldSettings.innerHTML = sidebarContent;
    };

    // تعريف دالة showAlert
    window.showAlert = function(message, type = 'info') {
        const alertDiv = document.getElementById('alertMessage');
        if (!alertDiv) return;

        const alertTypes = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };

        alertDiv.className = `border px-4 py-3 rounded relative ${alertTypes[type] || alertTypes.info} shadow-lg`;
        alertDiv.innerHTML = `
            <span class="block sm:inline">${message}</span>
            <span class="absolute top-0 bottom-0 left-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>إغلاق</title>
                    <path d="M14.348 5.652a1 1 0 00-1.414 0L10 8.586 6.066 4.652a1 1 0 10-1.414 1.414L8.586 10l-3.934 3.934a1 1 0 101.414 1.414L10 11.414l3.934 3.934a1 1 0 101.414-1.414L11.414 10l3.934-3.934a1 1 0 000-1.414z"/>
                </svg>
            </span>
        `;

        alertDiv.classList.remove('hidden');

        // إخفاء الرسالة بعد 5 ثواني
        setTimeout(() => {
            alertDiv.classList.add('hidden');
        }, 5000);
    };

    // تعريف دالة updateFieldLabel
    window.updateFieldLabel = function(fieldName, newLabel) {
        // تحديث النص في العنصر
        if (window.selectedField) {
            const labelElement = window.selectedField.querySelector('.font-medium');
            if (labelElement) {
                labelElement.textContent = newLabel;
            }

            // تحديث العناصر الأخرى التي تعتمد على التسمية
            const inputElements = window.selectedField.querySelectorAll('input[type="text"], textarea, select');
            inputElements.forEach(el => {
                el.placeholder = newLabel;
            });

            showAlert('تم تحديث عنوان الحقل بنجاح', 'success');
        }
    };

    // تعريف دالة removeField
    window.removeField = function(fieldName) {
        if (confirm('هل أنت متأكد من حذف هذا الحقل؟')) {
            // إرسال طلب AJAX لحذف الحقل
            fetch(`/builder/remove-field/{{ $table }}/${fieldName}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إزالة العنصر من الواجهة
                    if (window.selectedField) {
                        window.selectedField.remove();
                        window.selectedField = null;
                        document.getElementById('fieldSettings').innerHTML =
                            '<p class="text-gray-500 text-sm">اختر حقلًا لبدء التعديل</p>';
                    }
                    showAlert('تم حذف الحقل بنجاح', 'success');
                } else {
                    showAlert('حدث خطأ أثناء حذف الحقل', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('حدث خطأ في الاتصال بالخادم', 'error');
            });
        }
    };
</script>
@endpush

@vite(['resources/js/form-designer-new.js'])

@push('scripts')
    <script src="{{ asset('js/live-preview.js') }}"></script>
@endpush

@endsection
