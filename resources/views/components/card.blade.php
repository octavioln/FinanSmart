@props(['type'=> 'info', 'logo' => '<svg class="w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M32 152C32 94.6 78.6 48 136 48h48c57.4 0 104 46.6 104 104v6.3c0 28.6-13.9 55.4-37.3 71.9l-64.9 45.8c-21.2 15-33.9 39.4-33.9 65.4V368c0 4.4 3.6 8 8 8s8-3.6 8-8V341.3c0-20.8 10.1-40.3 27.1-52.3L260 243.2c27.6-19.5 44-51.2 44-85V152c0-66.3-53.7-120-120-120H136C69.7 32 16 85.7 16 152v8c0 4.4 3.6 8 8 8s8-3.6 8-8v-8zM160 464a16 16 0 1 0 0-32 16 16 0 1 0 0 32z"/></svg>'])

@php
    switch ($type)
    {
        case 'warning':
            $clases = "text-orange-500 dark:text-orange-100 bg-orange-100 dark:bg-orange-500";
            break;
        case 'success':
            $clases = "text-green-500 dark:text-green-100 bg-green-100 dark:bg-green-500";
            break;
        case 'danger':
            $clases = "text-red-500 dark:text-red-100 bg-red-100 dark:bg-red-500";
            break;
        case 'info': default:
            $clases = "text-blue-500 dark:text-blue-100 bg-blue-100 dark:bg-blue-500";
            break;
    }
@endphp
<div class="min-w-0 rounded-lg shadow-xl hover:shadow-sm overflow-hidden bg-white">
    <div class="p-4 flex items-center">
        <div class="p-3 rounded-full {{$clases}} mr-4">
            {{$logo}}
        </div>
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600">
                {{$title}}
            </p>
            <p class="text-lg font-semibold text-gray-700">
                {{$total}}
            </p>
        </div>
    </div>
</div>
