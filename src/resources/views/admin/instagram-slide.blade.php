<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=1080">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { width: 1080px; height: 1080px; overflow: hidden; background: #f5f0fc; }
strong { font-weight: 700; }
em { font-style: italic; }

.slide {
    width: 1080px;
    height: 1080px;
    overflow: hidden;
    background: #f5f0fc;
    padding: 40px;
    font-family: 'Inter', Arial, sans-serif;
}

/* Header */
.slide-header { display: table; width: 1000px; margin-bottom: 10px; }
.slide-brand  { display: table-cell; vertical-align: middle; font-family: 'Cinzel', serif; font-size: 27px; color: #6a329f; letter-spacing: 0.09em; font-weight: 600; }
.slide-date   { display: table-cell; vertical-align: middle; text-align: right; font-size: 25px; color: #9a88b8; letter-spacing: 0.03em; }

/* Divider */
.slide-divider { height: 1px; background: rgba(106,50,159,0.35); margin-bottom: 26px; }

/* Cards row */
.slide-cards { overflow: hidden; height: 878px; }

.sign-card {
    float: left;
    width: 484px;
    height: 878px;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid rgba(106,50,159,0.13);
    overflow: hidden;
    padding: 26px;
}
.sign-card-right { float: right; }

/* Card top */
.card-top  { display: table; width: 432px; margin-bottom: 12px; }
.card-meta { display: table-cell; vertical-align: top; }
.card-icon { display: table-cell; width: 148px; vertical-align: top; text-align: right; }

.icon-wrap {
    display: inline-block;
    background: rgba(106,50,159,0.15);
    border: 1.5px solid rgba(106,50,159,0.35);
    border-radius: 14px;
    padding: 4px;
}

.sign-name         { font-family: 'Cinzel', serif; font-size: 40px; font-weight: 700; color: #1a0830; letter-spacing: 0.04em; margin-bottom: 8px; }
.sign-dates        { font-size: 21px; color: #9a88b8; margin-bottom: 12px; }
.sign-element-row  { display: block; }
.sign-glyph        { font-size: 34px; line-height: 1; font-variant-emoji: text; }
.element-badge     { font-size: 15px; font-weight: 700; padding: 3px 13px; border-radius: 20px; color: #fff; letter-spacing: 0.04em; margin-left: 8px; display: inline-block; vertical-align: middle; }

/* Divider inside card */
.card-divider { height: 1px; background: rgba(106,50,159,0.10); margin-bottom: 12px; }

/* Horoscope text */
.card-text { font-size: 23px; color: #3a2a50; line-height: 1.70; overflow: hidden; }

/* Footer */
.slide-footer { display: table; width: 1000px; margin-top: 15px; }
.footer-url   { display: table-cell; color: #c0b0d8; font-size: 15px; letter-spacing: 0.04em; }
.footer-num   { display: table-cell; text-align: right; color: #c0b0d8; font-size: 15px; }
</style>
</head>
<body>
<div class="slide">

    <div class="slide-header">
        <div class="slide-brand">STELLAR ✦ OMENS</div>
        <div class="slide-date">{{ $date->format('F j, Y') }}</div>
    </div>

    <div class="slide-divider"></div>

    <div class="slide-cards">
        @foreach($pair as $slug => $sign)
        @php
            $elementColor = match($sign['element']) {
                'Fire'  => '#b43218',
                'Earth' => '#3c6428',
                'Air'   => '#28598c',
                'Water' => '#1e3782',
                default => '#50327a',
            };
            $elementBg = match($sign['element']) {
                'Fire'  => 'rgba(180,50,30,0.82)',
                'Earth' => 'rgba(60,100,40,0.82)',
                'Air'   => 'rgba(40,90,140,0.82)',
                'Water' => 'rgba(30,55,130,0.82)',
                default => 'rgba(80,50,120,0.82)',
            };
            $horoText = strip_tags($horoscopes[$slug] ?? '', '<strong><em>');
        @endphp

        <div class="sign-card {{ $loop->first ? '' : 'sign-card-right' }}">

            <div class="card-top">
                <div class="card-meta">
                    <div class="sign-name">{{ ucfirst($slug) }}</div>
                    <div class="sign-dates">{{ $sign['dates'] }}</div>
                    <div class="sign-element-row">
                        <span class="sign-glyph" style="color:{{ $elementColor }}">{{ $sign['glyph'] }}&#xFE0E;</span>
                        <span class="element-badge" style="background:{{ $elementBg }}">{{ $sign['element'] }}</span>
                    </div>
                </div>
                <div class="card-icon">
                    <div class="icon-wrap">
                        @include('partials.zodiac-picture', ['sign' => $slug, 'size' => 130])
                    </div>
                </div>
            </div>

            <div class="card-divider"></div>

            <div class="card-text">{!! $horoText !!}</div>

        </div>
        @endforeach

        <div style="clear:both;"></div>
    </div>

    <div class="slide-footer">
        <div class="footer-url">stellaromens.com</div>
        <div class="footer-num">{{ $slideNum }} / 6</div>
    </div>

</div>
</body>
</html>
