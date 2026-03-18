<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Natal Chart — {{ $profile->name }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10pt;
        color: #1a1a2e;
        line-height: 1.55;
        background: #fff;
    }

    /* ── Page header ─────────────────────────────────────────────────────── */
    .page-header {
        border-bottom: 2px solid #6a329f;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }
    .page-header h1 {
        font-size: 18pt;
        font-weight: 700;
        color: #6a329f;
        letter-spacing: 0.04em;
    }
    .page-header .meta {
        font-size: 9pt;
        color: #666;
        margin-top: 3px;
    }
    .page-header .meta span { margin-right: 8px; }

    /* ── Section ─────────────────────────────────────────────────────────── */
    .section {
        margin-bottom: 18px;
    }
    .section:last-of-type {
        margin-bottom: 0;
    }
    .section-title {
        font-size: 8.5pt;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #6a329f;
        border-bottom: 1px solid #e0d8f0;
        padding-bottom: 3px;
        margin-bottom: 8px;
    }

    /* ── Table ────────────────────────────────────────────────────────────── */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    table th {
        text-align: left;
        font-weight: 600;
        color: #444;
        border-bottom: 1px solid #ddd;
        padding: 3px 6px;
        font-size: 9.5pt;
    }
    table td {
        padding: 3px 6px;
        border-bottom: 1px solid #f0ecf8;
        vertical-align: top;
    }
    table tr:last-child td { border-bottom: none; }
    .accent { color: #6a329f; font-weight: 600; }
    .muted  { color: #666; }
    .num    { text-align: right; font-variant-numeric: tabular-nums; }
    .rx-on  { color: #c03828; font-weight: 700; }

    /* ── Two-column layout ───────────────────────────────────────────────── */
    .two-col { width: 100%; }
    .two-col td { width: 50%; vertical-align: top; padding-right: 12px; }
    .two-col td:last-child { padding-right: 0; }

    /* ── Text entries (aspects etc.) ─────────────────────────────────────── */
    .entry { margin-bottom: 10px; page-break-inside: avoid; }
    .entry-label {
        font-size: 9.5pt;
        font-weight: 700;
        color: #6a329f;
        margin-bottom: 2px;
    }
    .entry-sub {
        font-size: 9pt;
        color: #888;
        margin-bottom: 2px;
    }
    .entry-text {
        font-size: 10pt;
        color: #333;
        line-height: 1.55;
    }
    .entry-text strong { font-weight: 700; color: #1a1a2e; }
    .entry-text em     { font-style: italic; color: #6a329f; }

    /* ── Wheel placeholder ───────────────────────────────────────────────── */
    .wheel-placeholder {
        border: 1px dashed #c8b8e8;
        border-radius: 6px;
        padding: 14px 18px;
        margin-bottom: 18px;
        color: #999;
        font-size: 9pt;
        text-align: center;
    }

    /* ── Footer ──────────────────────────────────────────────────────────── */
    .pdf-footer {
        margin-top: 20px;
        border-top: 1px solid #e0d8f0;
        padding-top: 6px;
        font-size: 8pt;
        color: #aaa;
        text-align: center;
    }
</style>
</head>
<body>

@php
    $signGlyphs = ['♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓'];
    $signNames  = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $bodyGlyphs = ['☉','☽','☿','♀','♂','♃','♄','♅','♆','♇','⚷','☊','⚸'];
    $bodyNames  = ['Sun','Moon','Mercury','Venus','Mars','Jupiter','Saturn','Uranus','Neptune','Pluto','Chiron','North Node','Lilith'];
    $houseNames = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $aspectGlyphs = [
        'conjunction'=>'☌','opposition'=>'☍','trine'=>'△','square'=>'□',
        'sextile'=>'⚹','quincunx'=>'⚻','semi_sextile'=>'⚺','mutual_reception'=>'⇌',
    ];
    $aspectNames = [
        'conjunction'=>'Conjunction','opposition'=>'Opposition','trine'=>'Trine',
        'square'=>'Square','sextile'=>'Sextile','quincunx'=>'Quincunx',
        'semi_sextile'=>'Semi-sextile','mutual_reception'=>'Mutual Reception',
    ];
    $planets = $chart->planets ?? [];
    $aspects = $chart->aspects ?? [];
    $houses  = $chart->houses  ?? [];

    $ascSign = $chart->ascendant !== null ? (int)floor($chart->ascendant / 30) : null;
    $ascDeg  = $chart->ascendant !== null ? (int)floor(fmod($chart->ascendant, 30)) : null;
    $ascMin  = $chart->ascendant !== null ? (int)round((fmod($chart->ascendant, 30) - $ascDeg) * 60) : null;
@endphp

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="page-header" style="display:table;width:100%">
    <div style="display:table-cell;vertical-align:middle">
        <div style="font-size:18pt;font-weight:800;color:#6a329f;letter-spacing:0.03em;line-height:1.1">Stellar ✦ Omens</div>
        <div style="font-size:10pt;color:#8a70b8;letter-spacing:0.04em;font-style:italic;margin-top:2px">Your stars, decoded</div>
        <div style="font-size:9pt;color:#a090c0;letter-spacing:0.06em;margin-top:2px">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</div>
    </div>
    <div style="display:table-cell;vertical-align:middle;text-align:right">
        <div style="font-size:9pt;color:#bbb">{{ __('ui.natal.page_title') }} · Generated {{ now()->format('M j, Y') }}</div>
    </div>
</div>

{{-- ── Profile info (centered, above wheel) ─────────────────────────────── --}}
<div style="text-align:center;margin-bottom:10px;margin-top:4px">
    <div style="font-size:16pt;font-weight:700;color:#1a1a2e;letter-spacing:0.02em">{{ $profile->name }}</div>
    <div style="font-size:9pt;color:#666;margin-top:3px">
        @if($profile->birth_date)<span>{{ $profile->birth_date->format('F j, Y') }}</span>@endif
        @if($profile->birth_time)<span style="margin-left:8px">{{ substr($profile->birth_time, 0, 5) }}</span>@endif
        @if($profile->birthCity)<span style="margin-left:8px">{{ $profile->birthCity->name }}</span>@endif
        @if($ascSign !== null)<span style="margin-left:8px">ASC {{ $signGlyphs[$ascSign] }} {{ $signNames[$ascSign] }} {{ $ascDeg }}°{{ str_pad($ascMin, 2, '0', STR_PAD_LEFT) }}'</span>@endif
    </div>
</div>

{{-- ── Natal Wheel ─────────────────────────────────────────────────────── --}}
@php
    $wheelAsc     = $chart->ascendant;
    $wheelPlanets = array_values(array_map(fn($p) => [
        'body' => (int)($p['body'] ?? 0),
        'lon'  => (float)(($p['sign'] ?? 0) * 30 + ($p['degree'] ?? 0)),
        'rx'   => (bool)($p['is_retrograde'] ?? false),
    ], $planets));
    $wheelHouses  = array_values($houses);
    $wheelAspects = array_values(array_map(fn($a) => [
        'a'    => (int)($a['body_a'] ?? 0),
        'b'    => (int)($a['body_b'] ?? 0),
        'type' => $a['aspect'] ?? '',
    ], $aspects));
@endphp
<div style="text-align:center;margin-bottom:18px">
    <svg id="natal-wheel" viewBox="0 0 320 320" width="560" height="560"
         style="display:inline-block" aria-label="Natal chart wheel"></svg>
</div>
<script>
window.onload = function() {
    var ascLon = {{ $wheelAsc !== null ? (float)$wheelAsc : 'null' }};
    var PL = @json($wheelPlanets);
    var HS = @json($wheelHouses);
    var AS = @json($wheelAspects);
    var NS = 'http://www.w3.org/2000/svg';
    var CX=160,CY=160,RO=154,RZ=136,R4IN=120,R3IN=72,RPG=112,RH=67,RC=62;
    var ascEff = (ascLon !== null) ? ascLon : 0;
    var C = {bg:'#f5f0fc',card:'#ffffff',raised:'#ede8f5',border:'#c8c0d8',muted:'#6b6880'};
    var ACCENT = '#6a329f';
    var svg = document.getElementById('natal-wheel');
    if (!svg) return;
    svg.innerHTML = '';

    function pad2(n) { return n < 10 ? '0' + n : '' + n; }
    function l2a(lon) { return ((180 - (lon - ascEff)) % 360 + 360) % 360; }
    function pol(deg, r) {
        var a = deg * Math.PI / 180;
        return [parseFloat((CX + r * Math.cos(a)).toFixed(2)), parseFloat((CY + r * Math.sin(a)).toFixed(2))];
    }
    function mk(tag, attrs) {
        var e = document.createElementNS(NS, tag);
        var keys = Object.keys(attrs);
        for (var i = 0; i < keys.length; i++) { e.setAttribute(keys[i], String(attrs[keys[i]])); }
        svg.appendChild(e); return e;
    }
    function tx(x, y, t, attrs) {
        var e = document.createElementNS(NS, 'text');
        e.setAttribute('x', x); e.setAttribute('y', y); e.textContent = t;
        var keys = Object.keys(attrs);
        for (var i = 0; i < keys.length; i++) { e.setAttribute(keys[i], String(attrs[keys[i]])); }
        svg.appendChild(e);
    }
    function sector(rI, rO, aSt, aEn, fill, stroke) {
        var span = ((aSt - aEn) + 360) % 360, lg = span > 180 ? 1 : 0;
        var p1=pol(aSt,rO),p2=pol(aEn,rO),p3=pol(aEn,rI),p4=pol(aSt,rI);
        mk('path',{d:'M'+p1[0]+' '+p1[1]+'A'+rO+' '+rO+' 0 '+lg+' 0 '+p2[0]+' '+p2[1]+'L'+p3[0]+' '+p3[1]+'A'+rI+' '+rI+' 0 '+lg+' 1 '+p4[0]+' '+p4[1]+'Z',fill:fill,stroke:stroke,'stroke-width':'0.25'});
    }

    var SIGN_ELEM=[0,1,2,3,0,1,2,3,0,1,2,3];
    var ELEM_C=['#c43030','#287838','#b08010','#1d5fa8'];
    var SIGN_G=['\u2648','\u2649','\u264a','\u264b','\u264c','\u264d','\u264e','\u264f','\u2650','\u2651','\u2652','\u2653'];
    for (var i=0;i<SIGN_G.length;i++) SIGN_G[i]+='\ufe0e';

    mk('circle',{cx:CX,cy:CY,r:RO,fill:C.bg,stroke:C.border,'stroke-width':'0.2'});
    for (var s=0;s<12;s++) sector(RZ,RO,l2a(s*30),l2a((s+1)*30),s%2===0?C.raised:C.card,C.border);
    for (var deg=0;deg<360;deg++) {
        var a=l2a(deg),isTen=deg%10===0,isFive=deg%5===0,len=isTen?5.5:isFive?3.5:2;
        var p1=pol(a,RO),p2=pol(a,RO-len);
        mk('line',{x1:p1[0],y1:p1[1],x2:p2[0],y2:p2[1],stroke:C.border,'stroke-width':isTen?'0.9':isFive?'0.7':'0.5'});
    }
    var RGLYPH=RZ+7;
    for (var s=0;s<12;s++) {
        var gp=pol(l2a(s*30+15),RGLYPH);
        tx(gp[0],gp[1],SIGN_G[s],{'text-anchor':'middle','dominant-baseline':'central','font-size':'10','fill':ELEM_C[SIGN_ELEM[s]],'font-family':'serif','pointer-events':'none'});
    }
    mk('circle',{cx:CX,cy:CY,r:RZ,fill:'none',stroke:C.border,'stroke-width':'0.4'});
    mk('circle',{cx:CX,cy:CY,r:RZ,fill:C.card,stroke:'none'});
    if (HS.length===12) {
        mk('circle',{cx:CX,cy:CY,r:R4IN,fill:'none',stroke:C.border,'stroke-width':'0.4'});
        mk('circle',{cx:CX,cy:CY,r:R3IN,fill:'none',stroke:C.border,'stroke-width':'0.4'});
    }

    var HOUSE_N=['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    var ANGULAR=[0,3,6,9];
    function isAngular(h) { return ANGULAR.indexOf(h)!==-1; }

    if (HS.length===12) {
        var R4MID=(RZ+R4IN)/2;
        for (var h=0;h<12;h++) {
            var a=l2a(HS[h]),isA=isAngular(h);
            var pp1=pol(a,R4IN),pp2=pol(a,RC);
            mk('line',{x1:pp1[0],y1:pp1[1],x2:pp2[0],y2:pp2[1],stroke:isA?ACCENT:C.border,'stroke-width':isA?'1.2':'0.6'});
            var cDeg=Math.floor(HS[h]%30),cMin=Math.round(((HS[h]%30)-cDeg)*60),cSign=Math.floor(HS[h]/30)%12;
            var dp=pol(a,R4MID+2);
            tx(dp[0],dp[1],cDeg+'\u00b0'+pad2(cMin)+"'",{'text-anchor':'middle','dominant-baseline':'central','font-size':'4.5','fill':isA?ACCENT:C.muted,'font-family':'sans-serif','pointer-events':'none'});
            var sp=pol(a,R4MID-4);
            tx(sp[0],sp[1],SIGN_G[cSign],{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':isA?ACCENT:ELEM_C[SIGN_ELEM[cSign]],'font-family':'serif','pointer-events':'none'});
        }
        var sortedIdx=[];
        for (var i=0;i<12;i++) sortedIdx.push(i);
        sortedIdx.sort(function(a,b){return HS[a]-HS[b];});
        for (var i=0;i<12;i++) {
            var hIdx=sortedIdx[i],nIdx=sortedIdx[(i+1)%12];
            var span=((HS[nIdx]-HS[hIdx])+360)%360,midLon=HS[hIdx]+span/2;
            var np=pol(l2a(midLon),RH);
            tx(np[0],np[1],HOUSE_N[hIdx],{'text-anchor':'middle','dominant-baseline':'central','font-size':'7.5','fill':C.muted,'font-family':'sans-serif','font-weight':'normal','pointer-events':'none'});
        }
    }

    var ASP_C={conjunction:{color:'#2060c0',w:'1.0',op:'0.80'},opposition:{color:'#c02020',w:'1.0',op:'0.80'},trine:{color:'#2060c0',w:'0.8',op:'0.75'},square:{color:'#c02020',w:'0.8',op:'0.75'},sextile:{color:'#2060c0',w:'0.7',op:'0.75'},quincunx:{color:'#208040',w:'0.6',op:'0.60'},semi_sextile:{color:'#2060c0',w:'0.5',op:'0.40'}};
    var lonMap={};
    for (var i=0;i<PL.length;i++) lonMap[PL[i].body]=PL[i].lon;
    function aspGlyph(x1,y1,x2,y2,type,color) {
        var mx=parseFloat(((x1+x2)/2).toFixed(1)),my=parseFloat(((y1+y2)/2).toFixed(1));
        var s=3.8,sw='0.8';
        if (type==='trine') {
            var h=s*0.9;
            mk('polygon',{points:mx+','+(my-h).toFixed(1)+' '+(mx-h*0.866).toFixed(1)+','+(my+h*0.5).toFixed(1)+' '+(mx+h*0.866).toFixed(1)+','+(my+h*0.5).toFixed(1),fill:'none',stroke:color,'stroke-width':sw,'stroke-linejoin':'round','pointer-events':'none'});
        } else if (type==='square') {
            mk('rect',{x:(mx-s*0.72).toFixed(1),y:(my-s*0.72).toFixed(1),width:String((2*s*0.72).toFixed(1)),height:String((2*s*0.72).toFixed(1)),fill:'none',stroke:color,'stroke-width':sw,'pointer-events':'none'});
        } else if (type==='sextile') {
            for (var si=0;si<6;si++) { var sa=si*60*Math.PI/180; mk('line',{x1:mx,y1:my,x2:parseFloat((mx+s*Math.cos(sa)).toFixed(1)),y2:parseFloat((my+s*Math.sin(sa)).toFixed(1)),stroke:color,'stroke-width':sw,'pointer-events':'none'}); }
        } else if (type==='conjunction') {
            mk('circle',{cx:mx,cy:my,r:String(s.toFixed(1)),fill:'none',stroke:color,'stroke-width':sw});
            mk('circle',{cx:mx,cy:my,r:'1.4',fill:color,stroke:'none'});
        } else if (type==='opposition') {
            mk('line',{x1:(mx-s).toFixed(1),y1:my,x2:(mx+s).toFixed(1),y2:my,stroke:color,'stroke-width':sw});
            mk('circle',{cx:mx,cy:my,r:'1.4',fill:color,stroke:'none'});
        } else if (type==='quincunx') {
            mk('line',{x1:(mx-s*0.5).toFixed(1),y1:(my+s*0.5).toFixed(1),x2:mx,y2:(my-s*0.5).toFixed(1),stroke:color,'stroke-width':'0.9','pointer-events':'none'});
            mk('line',{x1:(mx+s*0.5).toFixed(1),y1:(my+s*0.5).toFixed(1),x2:mx,y2:(my-s*0.5).toFixed(1),stroke:color,'stroke-width':'0.9','pointer-events':'none'});
        } else if (type==='semi_sextile') {
            var t=mk('text',{x:mx,y:(my+s*0.4).toFixed(1),'text-anchor':'middle','dominant-baseline':'middle',fill:color,'font-size':'7','pointer-events':'none'});
            t.textContent='\u26ba';
        }
    }
    mk('circle',{cx:CX,cy:CY,r:RC,fill:C.card,stroke:'none'});
    for (var i=0;i<AS.length;i++) {
        var asp=AS[i],lonA=lonMap[asp.a],lonB=lonMap[asp.b];
        if(lonA===undefined||lonB===undefined||asp.type==='mutual_reception') continue;
        var cfg=ASP_C[asp.type]||{color:'#505060',w:'0.5',op:'0.30'};
        var ap1=pol(l2a(lonA),RC),ap2=pol(l2a(lonB),RC);
        mk('line',{x1:ap1[0],y1:ap1[1],x2:ap2[0],y2:ap2[1],stroke:cfg.color,'stroke-width':cfg.w,opacity:cfg.op});
        aspGlyph(ap1[0],ap1[1],ap2[0],ap2[1],asp.type,cfg.color);
    }
    mk('circle',{cx:CX,cy:CY,r:RC,fill:'none',stroke:C.border,'stroke-width':'0.4'});

    var BODY_G=['\u2609','\u263d','\u263f','\u2640','\u2642','\u2643','\u2644','\u26e2','\u2646','\u2647','\u26b7','\u260a','\u26b8'];
    for (var i=0;i<BODY_G.length;i++) BODY_G[i]+='\ufe0e';
    var BODY_C=['#c49a18','#5588aa','#3a7a68','#a84e80','#c03828','#9a6218','#4a6898','#2888a8','#3858a8','#6838a0','#508080','#4068a0','#885060'];

    var pts=[];
    for (var i=0;i<PL.length;i++) pts.push({body:PL[i].body,lon:PL[i].lon,rx:PL[i].rx,origA:l2a(PL[i].lon),a:l2a(PL[i].lon),r:RPG});
    pts.sort(function(a,b){return a.a-b.a;});
    var MIN_ANG=9;
    for (var iter=0;iter<40;iter++) {
        var moved=false;
        for (var i=0;i<pts.length;i++) {
            var j=(i+1)%pts.length,diff=((pts[j].a-pts[i].a)+360)%360;
            if(diff>0&&diff<MIN_ANG){var push=(MIN_ANG-diff)/2;pts[i].a=(pts[i].a-push+360)%360;pts[j].a=(pts[j].a+push)%360;moved=true;}
        }
        pts.sort(function(a,b){return a.a-b.a;});
        if(!moved) break;
    }
    for (var i=0;i<pts.length;i++) {
        var p=pts[i],pp=pol(p.a,p.r),lp=pol(p.origA,RZ);
        mk('line',{x1:lp[0],y1:lp[1],x2:pp[0],y2:pp[1],stroke:C.border,'stroke-width':'0.5','stroke-dasharray':'2,3'});
        tx(pp[0],pp[1],BODY_G[p.body]||'\u2605',{'text-anchor':'middle','dominant-baseline':'central','font-size':'13','fill':BODY_C[p.body]||ACCENT,'font-family':'serif','pointer-events':'none'});
        var dis=p.lon%30,dg=Math.floor(dis),mn=Math.floor((dis-dg)*60),dlp=pol(p.a,p.r-12);
        tx(dlp[0],dlp[1],dg+'\u00b0'+pad2(mn)+"'",{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':C.muted,'font-family':'sans-serif','pointer-events':'none'});
    }

    if (ascLon!==null) {
        function arrowHead(ax,ay,ang,color,sz){
            var a1=ang+Math.PI*5/6,a2=ang-Math.PI*5/6;
            mk('polygon',{points:parseFloat(ax).toFixed(1)+','+parseFloat(ay).toFixed(1)+' '+(ax+sz*Math.cos(a1)).toFixed(1)+','+(ay+sz*Math.sin(a1)).toFixed(1)+' '+(ax+sz*Math.cos(a2)).toFixed(1)+','+(ay+sz*Math.sin(a2)).toFixed(1),fill:color,stroke:'none','pointer-events':'none'});
        }
        function axisLine(lon1,lon2){
            var a1=l2a(lon1),a2=l2a(lon2);
            var ox1=pol(a1,R4IN),ox2=pol(a2,R4IN),ix1=pol(a1,R3IN),ix2=pol(a2,R3IN);
            mk('line',{x1:ix1[0],y1:ix1[1],x2:ox1[0],y2:ox1[1],stroke:C.muted,'stroke-width':'1.2',opacity:'0.9'});
            arrowHead(ox1[0],ox1[1],Math.atan2(ox1[1]-CY,ox1[0]-CX),C.muted,5);
            mk('line',{x1:ix2[0],y1:ix2[1],x2:ox2[0],y2:ox2[1],stroke:C.muted,'stroke-width':'1.2',opacity:'0.9'});
        }
        axisLine(ascLon,ascLon+180);
        if(HS.length===12) axisLine(HS[9],HS[3]);
        function degStr(lon){var w=((lon%30)+30)%30,d=Math.floor(w),m=Math.round((w-d)*60);return d+'\u00b0'+pad2(m>=60?59:m)+"'";}
        function lbl(x,y,name,deg,fill){
            tx(x,y-4,name,{'text-anchor':'middle','dominant-baseline':'central','font-size':'7','fill':fill,'font-family':'sans-serif','font-weight':'bold','pointer-events':'none'});
            tx(x,y+5,deg,{'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':fill,'font-family':'sans-serif','pointer-events':'none'});
        }
        lbl(CX-RC+9,CY,'AC',degStr(ascLon),ACCENT);
        lbl(CX+RC-9,CY,'DC',degStr((ascLon+180)%360),C.muted);
        if(HS.length===12){var mp=pol(l2a(HS[9]),RC-14);lbl(mp[0],mp[1],'MC',degStr(HS[9]),ACCENT);var ip=pol(l2a(HS[3]),RC-14);lbl(ip[0],ip[1],'IC',degStr(HS[3]),C.muted);}
    }
};
</script>

{{-- ── Portrait ──────────────────────────────────────────────────────── --}}
@if(!empty($portrait))
<div class="section" style="background:#fdf9ec;border:1px solid #e8d88a;border-radius:5px;padding:10px 12px">
    <div style="font-size:10pt;line-height:1.65;color:#1a1a2e">{!! $portrait !!}</div>
</div>
@endif

{{-- ── Planets + Houses (two-column) ───────────────────────────────────── --}}
<div class="section" style="overflow:hidden">
    <div style="float:left;width:48%">
        <div class="section-title">{{ __('ui.natal.section_planets') }}</div>
        <table>
            <thead>
                <tr>
                    <th>Planet</th>
                    <th>Sign</th>
                    <th class="num">Position</th>
                    @if(count($houses))<th>H</th>@endif
                    <th>Rx</th>
                </tr>
            </thead>
            <tbody>
            @foreach($planets as $p)
            @php
                $deg = floor($p['degree']);
                $min = floor(($p['degree'] - $deg) * 60);
            @endphp
            <tr>
                <td><span class="accent">{{ $bodyGlyphs[$p['body']] ?? '' }}</span> {{ $bodyNames[$p['body']] ?? '' }}</td>
                <td class="muted">{{ $signGlyphs[$p['sign']] ?? '' }} {{ $signNames[$p['sign']] ?? '' }}</td>
                <td class="num">{{ $deg }}°{{ str_pad($min, 2, '0', STR_PAD_LEFT) }}'</td>
                @if(count($houses))<td class="accent" style="text-align:center">{{ $houseNames[($p['house'] ?? 1) - 1] ?? '' }}</td>@endif
                <td class="{{ $p['is_retrograde'] ? 'rx-on' : 'muted' }}">{{ $p['is_retrograde'] ? 'Rx' : '·' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if(count($houses))
    <div style="float:right;width:48%">
        <div class="section-title">{{ __('ui.natal.section_houses') }}</div>
        <table>
            <tbody>
            @foreach($houses as $i => $cusp)
            @php
                $s   = (int)floor($cusp / 30);
                $d   = floor(fmod($cusp, 30));
                $m   = round((fmod($cusp, 30) - $d) * 60);
            @endphp
            <tr>
                <td style="{{ in_array($i,[0,3,6,9]) ? 'color:#6a329f' : 'color:#666' }};width:24px">{{ $houseNames[$i] }}</td>
                <td class="muted">{{ $signGlyphs[$s] ?? '' }} {{ $signNames[$s] ?? '' }}</td>
                <td class="num">{{ $d }}°{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}'</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <div style="clear:both"></div>
</div>

{{-- ── Aspects table ─────────────────────────────────────────────────────── --}}
@if(count($aspects))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_aspects_table') }}</div>
    <table>
        <tbody>
        @foreach($aspects as $asp)
        @php $type = $asp['aspect'] ?? ''; @endphp
        @continue($type === 'mutual_reception')
        <tr>
            <td style="width:14px" class="accent">{{ $bodyGlyphs[$asp['body_a'] ?? 0] ?? '' }}</td>
            <td style="width:70px">{{ $bodyNames[$asp['body_a'] ?? 0] ?? '' }}</td>
            <td style="width:16px;text-align:center">{{ $aspectGlyphs[$type] ?? '' }}</td>
            <td style="width:100px" class="muted">{{ $aspectNames[$type] ?? $type }}</td>
            <td style="width:14px" class="accent">{{ $bodyGlyphs[$asp['body_b'] ?? 0] ?? '' }}</td>
            <td>{{ $bodyNames[$asp['body_b'] ?? 0] ?? '' }}</td>
            <td class="num muted" style="width:40px">{{ round(abs($asp['orb'] ?? 0), 1) }}°</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Singleton / Missing Element ─────────────────────────────────────── --}}
@if(count($singletons))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_element_pattern') }}</div>
    @foreach($singletons as $s)
    <div class="entry">
        <div class="entry-label">
            @if($s['type'] === 'singleton')
                Singleton: {{ $bodyNames[$s['planet']['body']] ?? '' }} ({{ $s['element'] }})
            @else
                Missing element: {{ $s['element'] }}
            @endif
        </div>
        @if($s['text'])<div class="entry-text">{{ $s['text'] }}</div>@endif
    </div>
    @endforeach
</div>
@endif

{{-- ── House Lords ───────────────────────────────────────────────────────── --}}
@if(count($houseLords))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_house_lords') }}</div>
    @foreach($houseLords as $hl)
    <div class="entry">
        <div class="entry-label">{{ $hl['label'] }}</div>
        <div class="entry-text">{{ $hl['text'] }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- ── House Lord Aspects ────────────────────────────────────────────────── --}}
@if(count($houseLordAspects))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_house_lord_aspects') }}</div>
    @foreach($houseLordAspects as $item)
    <div class="entry">
        <div class="entry-label">{{ $item['label'] }}</div>
        <div class="entry-sub">{{ $item['lord'] }} · {{ ucfirst(str_replace('_', '-', $item['aspect'])) }} · {{ $item['other'] }}</div>
        <div class="entry-text">{!! strip_tags($item['text'], '<strong><em>') !!}</div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Aspects to Angles ─────────────────────────────────────────────────── --}}
@if(count($angleAspectTexts))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_angle_aspects') }}</div>
    @foreach($angleAspectTexts as $item)
    <div class="entry">
        <div class="entry-label">
            {{ $item['planet'] }} {{ $aspectGlyphs[$item['aspect']] ?? '' }} {{ $aspectNames[$item['aspect']] ?? $item['aspect'] }} {{ $item['angle'] }}
        </div>
        <div class="entry-text">{!! strip_tags($item['text'], '<strong><em>') !!}</div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Natal Aspects (planet-planet) ───────────────────────────────────── --}}
@if(count($aspectTexts))
<div class="section">
    <div class="section-title">{{ __('ui.natal.section_aspects') }}</div>
    @foreach($aspectTexts as $item)
    @php
        $nA = \App\Models\PlanetaryPosition::BODY_NAMES[$item['bodyA']] ?? '';
        $nB = \App\Models\PlanetaryPosition::BODY_NAMES[$item['bodyB']] ?? '';
        $aLabel = $aspectNames[$item['aspect']] ?? ucwords(str_replace('_',' ',$item['aspect']));
    @endphp
    <div class="entry">
        <div class="entry-label">{{ $nA }} {{ $aspectGlyphs[$item['aspect']] ?? '' }} {{ $aLabel }} {{ $nB }}</div>
        @if($item['text'])
        <div class="entry-text">{!! strip_tags($item['text'], '<strong><em>') !!}</div>
        @endif
    </div>
    @endforeach
</div>
@endif
<div style="height:0;line-height:0;font-size:0;clear:both;page-break-after:avoid"></div>
</body>
</html>
