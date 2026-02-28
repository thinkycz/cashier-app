<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" dir="ltr">

<head>
    <meta charset="utf-8" />

    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <title>@yield('title', 'Document')</title>

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1,maximum-scale=2,shrink-to-fit=no" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.9.6/dist/cdn.min.js"></script>

    <style>
        :root {
            --preview-bg: #e7eef7;
            --preview-card-bg: #ffffff;
            --preview-text: #0f172a;
            --preview-card-border: #cdd8e5;
        }

        body {
            color: var(--preview-text);
        }

        @media screen {
            body {
                margin: 0;
                min-height: 100vh;
                background:
                    radial-gradient(circle at 10% 0%, rgba(20, 184, 166, 0.18) 0%, transparent 40%),
                    radial-gradient(circle at 90% 100%, rgba(6, 182, 212, 0.16) 0%, transparent 42%),
                    var(--preview-bg);
            }

            .preview-shell {
                padding: 5.5rem 1rem 2rem;
            }

            .document {
                box-shadow: 0 20px 40px -24px rgba(15, 23, 42, 0.35), 0 8px 20px -18px rgba(15, 23, 42, 0.3);
                margin: 0 auto;
                padding: 0.45rem;
                overflow-x: hidden;
                border: 1px solid var(--preview-card-border);
                border-radius: 0;
            }
        }

        .document {
            width: 210mm;
            min-height: 297mm;
            background-color: var(--preview-card-bg);
        }

        @if($embedded) @media screen {
            .preview-shell {
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
        }

        @endif @media print {
            body {
                background: #ffffff !important;
            }

            .preview-shell {
                padding: 0 !important;
            }

            .document {
                border-radius: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>
    @php($embedded = $embedded ?? false)
    @if(!$embedded)
    <div x-data class="print:hidden fixed top-0 inset-x-0 z-20 flex items-center justify-between border-b border-teal-200 bg-gradient-to-r from-teal-700 to-cyan-700 px-4 py-3 text-white shadow-lg">
        <h1 class="text-lg font-semibold">{{ __('Preview') }}</h1>
        <button @click.prevent="window.print()"
            class="inline-flex items-center gap-2 rounded-md border border-cyan-100/40 bg-white/15 px-3 py-2 text-white leading-none transition-colors hover:bg-white/25 focus:outline-none">
            <svg fill="currentColor" viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-white">
                <path
                    d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                    clip-rule="evenodd" fill-rule="evenodd"></path>
            </svg>
            <span class="text-sm font-semibold leading-none">{{ __('Print') }}</span>
        </button>
    </div>
    @endif

    <div class="preview-shell">
        <div class="document">
            @yield('document')
        </div>
    </div>
</body>

</html>