{{--
    PDF Page Header Partial — used by all PDF views
    Usage: @include('partials.pdf-header', ['pageTitle' => 'Daily Horoscope'])
--}}
<div style="display:table;width:100%;border-bottom:2px solid #6a329f;padding-bottom:8px;margin-bottom:16px">
    <div style="display:table-cell;vertical-align:middle">
        <div style="font-size:18pt;font-weight:800;color:#6a329f;letter-spacing:0.03em;line-height:1.1">Stellar ✦ Omens</div>
        <div style="font-size:10pt;color:#8a70b8;letter-spacing:0.04em;font-style:italic;margin-top:2px">Your stars, decoded</div>
        <div style="font-size:9pt;color:#a090c0;letter-spacing:0.06em;margin-top:2px"><a href="{{ config('app.url') }}" style="color:#a090c0;text-decoration:none">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a></div>
    </div>
    <div style="display:table-cell;vertical-align:middle;text-align:right">
        <div style="font-size:9pt;color:#bbb">{{ $pageTitle ?? '' }} · Generated {{ now()->format('M j, Y') }}</div>
    </div>
</div>
