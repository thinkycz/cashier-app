<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" dir="ltr">
<head>
    <meta charset="utf-8"/>

    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>

    <title>@yield('title', 'Document')</title>

    <meta
            name="viewport"
            content="width=device-width,initial-scale=1,maximum-scale=2,shrink-to-fit=no"
    />

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.9.6/dist/cdn.min.js"></script>

    <style>
        @media screen {
            body {
                background: #97a6ba;
            }

            .document {
                box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.10);
                min-height: 297mm;
                margin: 6rem auto;
                overflow-x: hidden;
            }
        }

        .document {
            width: 210mm;
            padding: 1rem;
            background-color: white;
        }
    </style>
</head>
<body>
<div x-data class="print:hidden fixed top-0 inset-x-0 bg-gray-200 shadow flex justify-between items-center p-4">
    <h1 class="text-lg font-semibold text-gray-800">{{ __('Preview') }}</h1>
    <button @click.prevent="window.print()"
            class="rounded text-white flex justify-center items-center focus:outline-none bg-gray-700 hover:bg-gray-600 px-2 py-1">
        <svg fill="currentColor" viewBox="0 0 20 20" class="w-4 h-4 text-white">
            <path
                    d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                    clip-rule="evenodd" fill-rule="evenodd"></path>
        </svg>
        <span class="ml-2">{{ __('Print') }}</span>
    </button>
</div>

<div class="document">
    @yield('document')
</div>
</body>
</html>