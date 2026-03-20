{{--
    PDF Page Footer Partial — used by all PDF views
    Usage: @include('partials.pdf-footer')
--}}
<div style="margin-top:20px;border-top:1px solid #e0d8f0;padding-top:6px;font-size:8pt;color:#aaa;text-align:center">
    Page generated {{ now()->format('M j, Y') }} · Stellar Omens · <a href="{{ config('app.url') }}" style="color:#6a329f;text-decoration:none">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>
</div>
