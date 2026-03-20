<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Daily Horoscope — {{ $profile->name }} — {{ $carbon->format('M j, Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10pt;
        color: #1a1a2e;
        line-height: 1.55;
        background: #fff;
    }

    .page-header {
        border-bottom: 2px solid #6a329f;
        padding-bottom: 8px;
        margin-bottom: 16px;
        display: table;
        width: 100%;
    }
    .page-header-left  { display: table-cell; vertical-align: middle; }
    .page-header-right { display: table-cell; vertical-align: middle; text-align: right; }
    .brand      { font-size: 18pt; font-weight: 800; color: #6a329f; letter-spacing: 0.03em; line-height: 1.1; }
    .brand-sub  { font-size: 10pt; color: #8a70b8; letter-spacing: 0.04em; font-style: italic; margin-top: 2px; }
    .brand-url  { font-size: 9pt; color: #a090c0; letter-spacing: 0.06em; margin-top: 2px; }
    .header-meta { font-size: 9pt; color: #bbb; }

    .profile-block { text-align: center; margin: 0 0 14px; }
    .profile-type  { font-size: 8pt; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #8a70b8; margin-bottom: 5px; }
    .profile-name  { font-size: 16pt; font-weight: 700; color: #1a1a2e; letter-spacing: 0.02em; }
    .profile-sub   { font-size: 9pt; color: #666; margin-top: 3px; }
    .profile-date  { font-size: 11pt; color: #6a329f; font-weight: 600; margin-top: 5px; }

    .section { margin-bottom: 16px; }
    .section-title {
        font-size: 8.5pt; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        color: #6a329f;
        border-bottom: 1px solid #e0d8f0;
        padding-bottom: 3px; margin-bottom: 8px;
    }
    .section-title-gold {
        font-size: 8.5pt; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        color: #b89000;
        border-bottom: 1px solid #f0e8b0;
        padding-bottom: 3px; margin-bottom: 8px;
    }

    .entry { margin-bottom: 10px; page-break-inside: avoid; }
    .chip  {
        font-size: 9.5pt; font-weight: 700; color: #6a329f;
        margin-bottom: 3px;
    }
    .prose { font-size: 10pt; color: #333; line-height: 1.55; }
    .prose strong { font-weight: 700; color: #1a1a2e; }
    .prose em     { font-style: italic; color: #6a329f; }

    .lunar-meta { font-size: 9.5pt; color: #555; margin-bottom: 6px; }

    .area-row {
        display: table; width: 100%;
        padding: 3px 0;
        border-bottom: 1px solid #f0ecf8;
        font-size: 9.5pt;
    }
    .area-row:last-child { border-bottom: none; }
    .area-name  { display: table-cell; }
    .area-stars { display: table-cell; text-align: right; color: #6a329f; }

    .day-meta { font-size: 9pt; color: #555; margin-top: 14px; }
    .day-meta-row { margin-bottom: 3px; }

    .muted { color: #888; }
    .accent { color: #6a329f; }

    .divider { border: none; border-top: 1px solid #e0d8f0; margin: 12px 0; }
</style>
</head>
<body>

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $bodyGlyphs = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'];
    $bodyNames  = ['Sun','Moon','Mercury','Venus','Mars','Jupiter','Saturn','Uranus','Neptune','Pluto','Chiron','North Node','Lilith'];
    $moonPhaseNames = [
        'new_moon' => 'New Moon', 'waxing_crescent' => 'Waxing Crescent',
        'first_quarter' => 'First Quarter', 'waxing_gibbous' => 'Waxing Gibbous',
        'full_moon' => 'Full Moon', 'waning_gibbous' => 'Waning Gibbous',
        'last_quarter' => 'Last Quarter', 'waning_crescent' => 'Waning Crescent',
    ];
    $moonSignGlyph = $signGlyphs[$dto->moon->signIndex] ?? '';
    $rulerGlyph    = $bodyGlyphs[$dto->dayRuler->body] ?? '';
    $areaEmojis = [
        'love' => '❤', 'home' => '⌂', 'creativity' => '✦',
        'spirituality' => '✦', 'health' => '♡', 'finance' => '$',
        'travel' => '✈', 'career' => '▲', 'personal_growth' => '✿',
        'communication' => '✉', 'contracts' => '✒',
    ];
@endphp

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="page-header-left">
        <div class="brand">Stellar ✦ Omens</div>
        <div class="brand-sub">Your stars, decoded</div>
        <div class="brand-url">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</div>
    </div>
    <div class="page-header-right">
        <div class="header-meta">Daily Horoscope · Generated {{ now()->format('M j, Y') }}</div>
    </div>
</div>

{{-- ── Profile + date ───────────────────────────────────────────────────── --}}
<div class="profile-block">
    <div class="profile-type">{{ __('ui.daily.page_title') }}</div>
    <div class="profile-name">{{ $profile->name }}</div>
    <div class="profile-sub">
        @if($profile->birth_date)<span>{{ $profile->birth_date->format('F j, Y') }}</span>@endif
        @if($profile->birth_time)<span style="margin-left:8px">{{ substr($profile->birth_time, 0, 5) }}</span>@endif
        @if($profile->birthCity)<span style="margin-left:8px">{{ $profile->birthCity->name }}</span>@endif
    </div>
    <div class="profile-date">{{ $carbon->format('l, j F Y') }}</div>
</div>

{{-- ── Bi-wheel (natal inner + transits outer) ─────────────────────────── --}}
@if(!empty($wheelNatalPlanets))
@php
    $bwAsc = $wheelAsc !== null ? (float)$wheelAsc : 'null';
@endphp
<div style="text-align:center;margin-bottom:14px">
    <svg id="biwheel-pdf" viewBox="-28 -28 376 376" width="480" height="480"
         style="display:inline-block" aria-label="Natal chart with transits"></svg>
</div>
<script>
window.onload = function() {
    var ascLon = {{ $bwAsc }};
    var PL = @json($wheelNatalPlanets);
    var HS = @json($wheelHouses);
    var AS = @json($wheelAspects);
    var TR = @json($wheelTransits);
    var NS='http://www.w3.org/2000/svg';
    var CX=160,CY=160,RO=154,RZ=136,R4IN=120,R3IN=72,RPG=112,RH=67,RC=62,RTG=168;
    var ascEff=(ascLon!==null)?ascLon:0;
    var C={bg:'#f5f0fc',card:'#ffffff',raised:'#ede8f5',border:'#c8c0d8',muted:'#6b6880'};
    var ACCENT='#6a329f';
    var svg=document.getElementById('biwheel-pdf');
    if(!svg) return;
    svg.innerHTML='';

    function pad2(n){return n<10?'0'+n:''+n;}
    function l2a(lon){return((180-(lon-ascEff))%360+360)%360;}
    function pol(deg,r){var a=deg*Math.PI/180;return[parseFloat((CX+r*Math.cos(a)).toFixed(2)),parseFloat((CY+r*Math.sin(a)).toFixed(2))];}
    function mk(tag,attrs){var e=document.createElementNS(NS,tag);var keys=Object.keys(attrs);for(var i=0;i<keys.length;i++){e.setAttribute(keys[i],String(attrs[keys[i]]));}svg.appendChild(e);return e;}
    function tx(x,y,t,attrs){var e=document.createElementNS(NS,'text');e.setAttribute('x',x);e.setAttribute('y',y);e.textContent=t;var keys=Object.keys(attrs);for(var i=0;i<keys.length;i++){e.setAttribute(keys[i],String(attrs[keys[i]]));}svg.appendChild(e);}
    function sector(rI,rO,aSt,aEn,fill,stroke){var span=((aSt-aEn)+360)%360,lg=span>180?1:0;var p1=pol(aSt,rO),p2=pol(aEn,rO),p3=pol(aEn,rI),p4=pol(aSt,rI);mk('path',{d:'M'+p1[0]+' '+p1[1]+'A'+rO+' '+rO+' 0 '+lg+' 0 '+p2[0]+' '+p2[1]+'L'+p3[0]+' '+p3[1]+'A'+rI+' '+rI+' 0 '+lg+' 1 '+p4[0]+' '+p4[1]+'Z',fill:fill,stroke:stroke,'stroke-width':'0.25'});}

    var SIGN_ELEM=[0,1,2,3,0,1,2,3,0,1,2,3];
    var ELEM_C=['#c43030','#287838','#b08010','#1d5fa8'];
    var SIGN_G=['\u2648','\u2649','\u264a','\u264b','\u264c','\u264d','\u264e','\u264f','\u2650','\u2651','\u2652','\u2653'];
    for(var i=0;i<SIGN_G.length;i++) SIGN_G[i]+='\ufe0e';
    var HOUSE_N=['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    var ANGULAR=[0,3,6,9];
    var BODY_G=['\u2609','\u263d','\u263f','\u2640','\u2642','\u2643','\u2644','\u26e2','\u2646','\u2647','\u26b7','\u260a','\u26b8'];
    for(var i=0;i<BODY_G.length;i++) BODY_G[i]+='\ufe0e';
    var BODY_C=['#c49a18','#5588aa','#3a7a68','#a84e80','#c03828','#9a6218','#4a6898','#2888a8','#3858a8','#6838a0','#508080','#4068a0','#885060'];
    var ASP_C={conjunction:{color:'#2060c0',w:'1.0',op:'0.80'},opposition:{color:'#c02020',w:'1.0',op:'0.80'},trine:{color:'#2060c0',w:'0.8',op:'0.75'},square:{color:'#c02020',w:'0.8',op:'0.75'},sextile:{color:'#2060c0',w:'0.7',op:'0.75'},quincunx:{color:'#208040',w:'0.6',op:'0.60'},semi_sextile:{color:'#2060c0',w:'0.5',op:'0.40'}};

    // Zodiac ring
    for(var s=0;s<12;s++) sector(RZ,RO,l2a(s*30),l2a((s+1)*30),s%2===0?C.raised:C.card,C.border);
    var RGLYPH=RZ+7;
    for(var s=0;s<12;s++){var gp=pol(l2a(s*30+15),RGLYPH);tx(gp[0],gp[1],SIGN_G[s],{'text-anchor':'middle','dominant-baseline':'central','font-size':'10','fill':ELEM_C[SIGN_ELEM[s]],'font-family':'serif','pointer-events':'none'});}
    mk('circle',{cx:CX,cy:CY,r:RZ,fill:'none',stroke:C.border,'stroke-width':'0.4'});
    mk('circle',{cx:CX,cy:CY,r:RZ,fill:C.card,stroke:'none'});

    // House rings
    if(HS.length===12){
        mk('circle',{cx:CX,cy:CY,r:R4IN,fill:'none',stroke:C.border,'stroke-width':'0.4'});
        mk('circle',{cx:CX,cy:CY,r:R3IN,fill:'none',stroke:C.border,'stroke-width':'0.4'});
        var R4MID=(RZ+R4IN)/2;
        for(var h=0;h<12;h++){
            var a=l2a(HS[h]),isA=ANGULAR.indexOf(h)!==-1;
            var pp1=pol(a,R4IN),pp2=pol(a,RC);
            mk('line',{x1:pp1[0],y1:pp1[1],x2:pp2[0],y2:pp2[1],stroke:isA?ACCENT:C.border,'stroke-width':isA?'1.2':'0.6'});
            var cDeg=Math.floor(HS[h]%30),cMin=Math.round(((HS[h]%30)-cDeg)*60),cSign=Math.floor(HS[h]/30)%12;
            var dp=pol(a,R4MID+2);tx(dp[0],dp[1],cDeg+'\u00b0'+pad2(cMin)+"'",{'text-anchor':'middle','dominant-baseline':'central','font-size':'4.5','fill':isA?ACCENT:C.muted,'font-family':'sans-serif','pointer-events':'none'});
            var sp=pol(a,R4MID-4);tx(sp[0],sp[1],SIGN_G[cSign],{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':isA?ACCENT:ELEM_C[SIGN_ELEM[cSign]],'font-family':'serif','pointer-events':'none'});
        }
        var sortedIdx=[];for(var i=0;i<12;i++) sortedIdx.push(i);
        sortedIdx.sort(function(a,b){return HS[a]-HS[b];});
        for(var i=0;i<12;i++){var hIdx=sortedIdx[i],nIdx=sortedIdx[(i+1)%12];var span=((HS[nIdx]-HS[hIdx])+360)%360,midLon=HS[hIdx]+span/2;var np=pol(l2a(midLon),RH);tx(np[0],np[1],HOUSE_N[hIdx],{'text-anchor':'middle','dominant-baseline':'central','font-size':'7.5','fill':C.muted,'font-family':'sans-serif','font-weight':'normal','pointer-events':'none'});}
    }

    // Natal aspects
    var lonMap={};for(var i=0;i<PL.length;i++) lonMap[PL[i].body]=PL[i].lon;
    for(var i=0;i<AS.length;i++){var asp=AS[i],lonA=lonMap[asp.a],lonB=lonMap[asp.b];if(lonA===undefined||lonB===undefined||asp.type==='mutual_reception') continue;var cfg=ASP_C[asp.type]||{color:'#505060',w:'0.5',op:'0.30'};var ap1=pol(l2a(lonA),RC),ap2=pol(l2a(lonB),RC);mk('line',{x1:ap1[0],y1:ap1[1],x2:ap2[0],y2:ap2[1],stroke:cfg.color,'stroke-width':cfg.w,opacity:cfg.op});}
    mk('circle',{cx:CX,cy:CY,r:RC,fill:'none',stroke:C.border,'stroke-width':'0.4'});

    // ASC/MC axes (before planets)
    if(ascLon!==null){
        function arrowHead(ax,ay,ang,color,sz){var a1=ang+Math.PI*5/6,a2=ang-Math.PI*5/6;mk('polygon',{points:parseFloat(ax).toFixed(1)+','+parseFloat(ay).toFixed(1)+' '+(ax+sz*Math.cos(a1)).toFixed(1)+','+(ay+sz*Math.sin(a1)).toFixed(1)+' '+(ax+sz*Math.cos(a2)).toFixed(1)+','+(ay+sz*Math.sin(a2)).toFixed(1),fill:color,stroke:'none','pointer-events':'none'});}
        function axisLine(lon1,lon2){var a1=l2a(lon1),a2=l2a(lon2);var ox1=pol(a1,R4IN),ox2=pol(a2,R4IN),ix1=pol(a1,R3IN),ix2=pol(a2,R3IN);mk('line',{x1:ix1[0],y1:ix1[1],x2:ox1[0],y2:ox1[1],stroke:C.muted,'stroke-width':'1.2',opacity:'0.9'});arrowHead(ox1[0],ox1[1],Math.atan2(ox1[1]-CY,ox1[0]-CX),C.muted,5);mk('line',{x1:ix2[0],y1:ix2[1],x2:ox2[0],y2:ox2[1],stroke:C.muted,'stroke-width':'1.2',opacity:'0.9'});}
        axisLine(ascLon,ascLon+180);
        if(HS.length===12) axisLine(HS[9],HS[3]);
    }

    // Spread natal planets
    function spreadPlanets(planets,rGlyph){
        var pts=[];for(var i=0;i<planets.length;i++) pts.push({body:planets[i].body,lon:planets[i].lon,rx:planets[i].rx,origA:l2a(planets[i].lon),a:l2a(planets[i].lon),r:rGlyph});
        pts.sort(function(a,b){return a.a-b.a;});
        var MIN_ANG=9;
        for(var iter=0;iter<60;iter++){var moved=false;for(var i=0;i<pts.length;i++){var j=(i+1)%pts.length,diff=((pts[j].a-pts[i].a)+360)%360;if(diff>0&&diff<MIN_ANG){var push=(MIN_ANG-diff)/2;pts[i].a=(pts[i].a-push+360)%360;pts[j].a=(pts[j].a+push)%360;moved=true;}}pts.sort(function(a,b){return a.a-b.a;});if(!moved) break;}
        return pts;
    }
    var natalPts=spreadPlanets(PL,RPG);
    for(var i=0;i<natalPts.length;i++){
        var p=natalPts[i],pp=pol(p.a,p.r),lp=pol(p.origA,RZ);
        mk('line',{x1:lp[0],y1:lp[1],x2:pp[0],y2:pp[1],stroke:C.border,'stroke-width':'0.5','stroke-dasharray':'2,3'});
        tx(pp[0],pp[1],BODY_G[p.body]||'\u2605',{'text-anchor':'middle','dominant-baseline':'central','font-size':'13','fill':BODY_C[p.body]||ACCENT,'font-family':'serif','pointer-events':'none'});
        var dis=p.lon%30,dg=Math.floor(dis),mn=Math.floor((dis-dg)*60),dlp=pol(p.a,p.r-12);
        tx(dlp[0],dlp[1],dg+'\u00b0'+pad2(mn)+"'",{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':C.muted,'font-family':'sans-serif','pointer-events':'none'});
        if(p.rx){tx(pp[0]+8,pp[1]-6,'r',{'text-anchor':'middle','dominant-baseline':'central','font-size':'7','fill':C.muted,'font-family':'serif','font-style':'italic','pointer-events':'none'});}
    }

    // Spread transit planets (free, outside zodiac)
    function spreadFree(planets,rGlyph){
        if(planets.length===0) return [];
        var pts=[];for(var i=0;i<planets.length;i++) pts.push({body:planets[i].body,lon:planets[i].lon,rx:planets[i].rx,origA:l2a(planets[i].lon),a:l2a(planets[i].lon),r:rGlyph});
        pts.sort(function(a,b){return a.origA-b.origA;});
        var N=pts.length,MIN_ANG=7;
        for(var iter=0;iter<80;iter++){var moved=false;for(var i=0;i<N;i++){var j=(i+1)%N;var diff=((pts[j].a-pts[i].a)+360)%360;if(diff>180) diff-=360;if(diff<MIN_ANG){var push=(MIN_ANG-diff)/2;pts[i].a=(pts[i].a-push+360)%360;pts[j].a=(pts[j].a+push+360)%360;moved=true;}}if(!moved) break;}
        return pts;
    }
    var transitPts=spreadFree(TR,RTG);
    for(var i=0;i<transitPts.length;i++){
        var p=transitPts[i],pp=pol(p.a,p.r),lp=pol(p.origA,RO);
        mk('line',{x1:lp[0],y1:lp[1],x2:pp[0],y2:pp[1],stroke:C.border,'stroke-width':'0.5','stroke-dasharray':'2,3'});
        tx(pp[0],pp[1],BODY_G[p.body]||'\u2605',{'text-anchor':'middle','dominant-baseline':'central','font-size':'12','fill':BODY_C[p.body]||ACCENT,'font-family':'serif','pointer-events':'none'});
        var dis=p.lon%30,dg=Math.floor(dis),mn=Math.floor((dis-dg)*60),dlp=pol(p.a,p.r+14);
        tx(dlp[0],dlp[1],dg+'\u00b0'+pad2(mn)+"'",{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':C.muted,'font-family':'sans-serif','pointer-events':'none'});
        if(p.rx){tx(pp[0]+8,pp[1]-6,'r',{'text-anchor':'middle','dominant-baseline':'central','font-size':'7','fill':C.muted,'font-family':'serif','font-style':'italic','pointer-events':'none'});}
    }

    // ASC/MC labels (on top)
    if(ascLon!==null){
        function degStr(lon){var w=((lon%30)+30)%30,d=Math.floor(w),m=Math.round((w-d)*60);return d+'\u00b0'+pad2(m>=60?59:m)+"'";}
        function lbl(x,y,name,deg,fill){tx(x,y-4,name,{'text-anchor':'middle','dominant-baseline':'central','font-size':'7','fill':fill,'font-family':'sans-serif','font-weight':'bold','pointer-events':'none'});tx(x,y+5,deg,{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':fill,'font-family':'sans-serif','pointer-events':'none'});}
        lbl(CX-RC+9,CY,'AC',degStr(ascLon),ACCENT);
        lbl(CX+RC-9,CY,'DC',degStr((ascLon+180)%360),C.muted);
        if(HS.length===12){var mp=pol(l2a(HS[9]),RC-14);lbl(mp[0],mp[1],'MC',degStr(HS[9]),ACCENT);var ip=pol(l2a(HS[3]),RC-14);lbl(ip[0],ip[1],'IC',degStr(HS[3]),C.muted);}
    }
};
</script>
@endif

{{-- ── AI Overview ──────────────────────────────────────────────────────── --}}
@if($aiText)
<div class="section">
    <div class="section-title-gold">{{ __('ui.daily.ai_overview') }}</div>
    <div class="prose">{!! $aiText !!}</div>
</div>
<hr class="divider">
@endif

{{-- ── Key Transit Factors ──────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ __('ui.daily.key_transits') }}</div>
    @if(empty($transitTexts))
    <div class="muted">{{ __('ui.daily.no_transits') }}</div>
    @else
    @foreach($transitTexts as $item)
    <div class="entry">
        <div class="chip">{{ $item['chip'] }}</div>
        @if($item['text'])
        <div class="prose">{!! $item['text'] !!}</div>
        @endif
    </div>
    @endforeach
    @endif
</div>

{{-- ── Lunar Day ────────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ __('ui.daily.lunar_day') }}</div>
    <div class="lunar-meta">
        Moon in {{ $moonSignGlyph }} {{ $dto->moon->signName }}
        · Day {{ $dto->moon->lunarDay }} / 30
        · {{ $dto->moon->phaseName }}
    </div>
    @if($lunarText)
    <div class="prose">{!! $lunarText !!}</div>
    @endif
</div>

{{-- ── Tip of the Day ───────────────────────────────────────────────────── --}}
@if($tipText)
<div class="section">
    <div class="section-title-gold">{{ __('ui.daily.tip_label') }}</div>
    <div class="prose">{!! $tipText !!}</div>
</div>
@endif

{{-- ── Clothing & Jewelry ───────────────────────────────────────────────── --}}
@if($clothingText)
<div class="section">
    <div class="section-title">{{ __('ui.daily.clothing_label') }}</div>
    <div class="lunar-meta">
        {{ $dto->dayRuler->weekday }} · {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}
        @if($dto->natalVenusSign !== null)
        · Venus in {{ $signNames[$dto->natalVenusSign] ?? '' }}
        @endif
    </div>
    <div class="prose">{!! $clothingText !!}</div>
</div>
@endif

{{-- ── Areas of Life ────────────────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">{{ __('ui.daily.areas_of_life') }}</div>
    @foreach($dto->areasOfLife as $area)
    <div class="area-row">
        <div class="area-name">{{ $areaEmojis[$area->slug] ?? '·' }} {{ $area->name }}</div>
        <div class="area-stars">
            @if($area->rating === 0)
            <span class="muted">wait</span>
            @else
            {{ str_repeat('★', $area->rating) }}{{ str_repeat('☆', $area->maxRating - $area->rating) }}
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- ── Day meta ─────────────────────────────────────────────────────────── --}}
<div class="day-meta">
    <div class="day-meta-row">{{ $dto->dayRuler->weekday }} · {{ $rulerGlyph }} {{ $dto->dayRuler->planet }}</div>
    <div class="day-meta-row">Color: {{ $dto->dayRuler->color }} · Gem: {{ $dto->dayRuler->gem }} · Number: {{ $dto->dayRuler->number }}</div>
</div>

</body>
</html>
