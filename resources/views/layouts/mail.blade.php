<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        @media only screen and (max-width: 640px) {
            .mail-shell {
                width: 100% !important;
            }

            .mail-frame {
                padding: 20px 12px !important;
            }

            .mail-panel,
            .mail-footer {
                padding: 24px !important;
            }

            .mail-title {
                font-size: 28px !important;
                line-height: 34px !important;
            }
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: #334155;
            font-family: Sen, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: #047857;
        }

        .mail-frame {
            width: 100%;
            padding: 32px 16px;
            background-color: #f1f5f9;
        }

        .mail-shell {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
        }

        .mail-brand {
            padding: 0 4px 18px;
        }

        .mail-logo-mark {
            display: inline-block;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background-color: #10b981;
            color: #ffffff;
            font-size: 17px;
            font-weight: 800;
            line-height: 44px;
            text-align: center;
            vertical-align: middle;
        }

        .mail-brand-copy {
            display: inline-block;
            margin-left: 12px;
            vertical-align: middle;
        }

        .mail-brand-name {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
            line-height: 24px;
        }

        .mail-brand-subtitle {
            margin: 2px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            line-height: 18px;
        }

        .mail-panel {
            overflow: hidden;
            padding: 32px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background-color: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        .mail-eyebrow {
            display: inline-block;
            margin: 0 0 14px;
            padding: 4px 10px;
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 999px;
            background-color: #d1fae5;
            color: #047857;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            line-height: 18px;
            text-transform: uppercase;
        }

        .mail-title {
            margin: 0;
            color: #0f172a;
            font-size: 34px;
            font-weight: 800;
            line-height: 40px;
        }

        .mail-content {
            margin-top: 24px;
        }

        .mail-content p {
            margin: 0 0 18px;
            color: #475569;
            font-size: 16px;
            line-height: 26px;
        }

        .mail-content p:last-child {
            margin-bottom: 0;
        }

        .mail-button {
            display: inline-block;
            margin-top: 4px;
            padding: 12px 18px;
            border-radius: 8px;
            background-color: #10b981;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: 700;
            line-height: 20px;
            text-decoration: none;
        }

        .mail-button:hover {
            background-color: #059669;
        }

        .mail-footer {
            padding: 22px 32px 0;
            text-align: center;
        }

        .mail-footer p {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 13px;
            line-height: 20px;
        }

        .mail-legal-links a {
            display: inline-block;
            margin: 0 6px;
            color: #475569;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="mail-frame">
        <div class="mail-shell">
            <div class="mail-brand">
                <span class="mail-logo-mark">WG</span>
                <span class="mail-brand-copy">
                    <p class="mail-brand-name">{{ __('app.name') }}</p>
                    <p class="mail-brand-subtitle">{{ __('mail.general.brand_subtitle') }}</p>
                </span>
            </div>

            <div class="mail-panel">
                @hasSection('eyebrow')
                    <p class="mail-eyebrow">@yield('eyebrow')</p>
                @endif

                <h1 class="mail-title">@yield('title')</h1>

                <div class="mail-content">
                    @yield('content')
                </div>
            </div>

            <div class="mail-footer">
                <p>&copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('legal.footer.content') }}</p>
                <p class="mail-legal-links">
                    <a href="{{ route('monitoring-locations') }}">{{ __('monitoring_locations.footer_link') }}</a>
                    <a href="{{ route('imprint') }}">{{ __('imprint.footer_link') }}</a>
                    <a href="{{ route('terms-of-use') }}">{{ __('legal.terms_of_use.footer_link') }}</a>
                    <a href="{{ route('gdpr') }}">{{ __('gdpr.footer_link') }}</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
