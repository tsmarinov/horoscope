@php
    $svgId     = $svgId     ?? 'biwheel';
    $svgWidth  = $svgWidth  ?? '100%';
    $svgHeight = $svgHeight ?? null;
    $svgClass  = $svgClass  ?? 'wheel-svg';
    $pdfMode   = $pdfMode   ?? false;

    $bwAscEff  = $wheelAsc !== null ? (float) $wheelAsc : 0.0;
    $bwAsc     = $wheelAsc !== null ? (float) $wheelAsc : 'null';
@endphp

<svg id="{{ $svgId }}"
     viewBox="-28 -28 376 376"
     width="{{ $svgWidth }}"
     @if($svgHeight !== null) height="{{ $svgHeight }}" @endif
     @if($svgClass) class="{{ $svgClass }}" @endif
     aria-label="Natal chart with transits"></svg>
<script>
(function() {
    var ascLon = {{ $bwAsc }};
    var PL = @json($wheelNatalPlanets);
    var HS = @json($wheelHouses);
    var AS = @json($wheelAspects);
    var TR = @json($wheelTransits);

    function draw() {
        var NS   = 'http://www.w3.org/2000/svg';
        var CX   = 160, CY = 160;
        var RO   = 154;
        var RZ   = 136;
        var R4IN = 120;
        var R3IN = 72;
        var RPG  = 112;
        var RH   = 67;
        var RC   = 62;
        var RTG  = 168;
        var ACCENT = '#6a329f';

        var ascEff = (ascLon !== null) ? ascLon : 0;

        var svg = document.getElementById('{{ $svgId }}');
        if (!svg) return;
        svg.innerHTML = '';

        @if($pdfMode)
        var C = {bg:'#f5f0fc',card:'#ffffff',raised:'#ede8f5',border:'#c8c0d8',muted:'#6b6880'};
        @else
        var st = getComputedStyle(document.documentElement);
        var C = {
            bg:     st.getPropertyValue('--theme-bg').trim()     || '#0e0e18',
            card:   st.getPropertyValue('--theme-card').trim()   || '#17172a',
            raised: st.getPropertyValue('--theme-raised').trim() || '#1e1e32',
            border: st.getPropertyValue('--theme-border').trim() || '#2e2e4a',
            muted:  st.getPropertyValue('--theme-muted').trim()  || '#7070a0'
        };
        @endif

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
            svg.appendChild(e);
            return e;
        }
        function tx(x, y, t, attrs) {
            var e = document.createElementNS(NS, 'text');
            e.setAttribute('x', x); e.setAttribute('y', y); e.textContent = t;
            var keys = Object.keys(attrs);
            for (var i = 0; i < keys.length; i++) { e.setAttribute(keys[i], String(attrs[keys[i]])); }
            svg.appendChild(e);
        }
        function sector(rI, rO, aSt, aEn, fill, stroke) {
            var span = ((aSt - aEn) + 360) % 360;
            var lg   = span > 180 ? 1 : 0;
            var p1 = pol(aSt, rO), p2 = pol(aEn, rO);
            var p3 = pol(aEn, rI), p4 = pol(aSt, rI);
            mk('path', {
                d: 'M'+p1[0]+' '+p1[1]+'A'+rO+' '+rO+' 0 '+lg+' 0 '+p2[0]+' '+p2[1]+'L'+p3[0]+' '+p3[1]+'A'+rI+' '+rI+' 0 '+lg+' 1 '+p4[0]+' '+p4[1]+'Z',
                fill: fill, stroke: stroke, 'stroke-width': '0.25'
            });
        }

        var SIGN_ELEM = [0,1,2,3, 0,1,2,3, 0,1,2,3];
        var ELEM_C    = ['#c43030','#287838','#b08010','#1d5fa8'];
        var SIGN_G    = ['\u2648','\u2649','\u264a','\u264b','\u264c','\u264d','\u264e','\u264f','\u2650','\u2651','\u2652','\u2653'];
        for (var i = 0; i < SIGN_G.length; i++) SIGN_G[i] += '\ufe0e';

        // ── Zodiac ring ───────────────────────────────────────────────────────
        for (var s = 0; s < 12; s++) {
            sector(RZ, RO, l2a(s * 30), l2a((s + 1) * 30), s % 2 === 0 ? C.raised : C.card, C.border);
        }

        // ── Tick marks: 360° fine scale from outer edge inward ────────────────
        for (var deg = 0; deg < 360; deg++) {
            var a      = l2a(deg);
            var isTen  = deg % 10 === 0;
            var isFive = deg % 5  === 0;
            var len    = isTen ? 5.5 : isFive ? 3.5 : 2;
            var t1 = pol(a, RO), t2 = pol(a, RO - len);
            mk('line', {x1:t1[0], y1:t1[1], x2:t2[0], y2:t2[1],
                stroke: C.border,
                'stroke-width': isTen ? '0.9' : isFive ? '0.7' : '0.5'
            });
        }

        // ── Sign glyphs in zodiac ring ────────────────────────────────────────
        var RGLYPH = RZ + 7;
        for (var s = 0; s < 12; s++) {
            var gp = pol(l2a(s * 30 + 15), RGLYPH);
            tx(gp[0], gp[1], SIGN_G[s], {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'10', 'fill': ELEM_C[SIGN_ELEM[s]],
                'font-family':'serif', 'pointer-events':'none'
            });
        }
        mk('circle', {cx:CX, cy:CY, r:RZ, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

        // ── Inner rings background ────────────────────────────────────────────
        mk('circle', {cx:CX, cy:CY, r:RZ, fill:C.card, stroke:'none'});

        var HOUSE_N = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        var ANGULAR = [0,3,6,9];
        function isAngular(h) { return ANGULAR.indexOf(h) !== -1; }

        if (HS.length === 12) {
            mk('circle', {cx:CX, cy:CY, r:R4IN, fill:'none', stroke:C.border, 'stroke-width':'0.4'});
            mk('circle', {cx:CX, cy:CY, r:R3IN, fill:'none', stroke:C.border, 'stroke-width':'0.4'});
        }

        // ── House cusps + labels ──────────────────────────────────────────────
        if (HS.length === 12) {
            var R4MID = (RZ + R4IN) / 2;
            for (var h = 0; h < 12; h++) {
                var ha   = l2a(HS[h]);
                var isA  = isAngular(h);
                var pp1  = pol(ha, R4IN), pp2 = pol(ha, RC);
                mk('line', {x1:pp1[0], y1:pp1[1], x2:pp2[0], y2:pp2[1],
                    stroke: isA ? ACCENT : C.border,
                    'stroke-width': isA ? '1.2' : '0.6'
                });
                var cDeg  = Math.floor(HS[h] % 30);
                var cMin  = Math.round(((HS[h] % 30) - cDeg) * 60);
                var cSign = Math.floor(HS[h] / 30) % 12;
                var dp = pol(ha, R4MID + 2);
                tx(dp[0], dp[1], cDeg + '\u00b0' + pad2(cMin) + "'", {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'4.5', 'fill': isA ? ACCENT : C.muted,
                    'font-family':'sans-serif', 'pointer-events':'none'
                });
                var sp = pol(ha, R4MID - 4);
                tx(sp[0], sp[1], SIGN_G[cSign], {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'5.5', 'fill': isA ? ACCENT : ELEM_C[SIGN_ELEM[cSign]],
                    'font-family':'serif', 'pointer-events':'none'
                });
            }

            // ── House numbers (sorted by ecliptic longitude) ──────────────────
            var sortedIdx = [];
            for (var i = 0; i < 12; i++) sortedIdx.push(i);
            sortedIdx.sort(function(a, b) { return HS[a] - HS[b]; });
            for (var i = 0; i < 12; i++) {
                var hIdx    = sortedIdx[i];
                var nIdx    = sortedIdx[(i + 1) % 12];
                var hspan   = ((HS[nIdx] - HS[hIdx]) + 360) % 360;
                var midLon  = HS[hIdx] + hspan / 2;
                var np = pol(l2a(midLon), RH);
                tx(np[0], np[1], HOUSE_N[hIdx], {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'7.5', 'fill': C.muted,
                    'font-family':'sans-serif', 'font-weight':'normal', 'pointer-events':'none'
                });
            }
        }

        // ── Aspect colors ─────────────────────────────────────────────────────
        var ASP_C = {
            conjunction:    {color:'#2060c0', w:'1.0', op:'0.80'},
            opposition:     {color:'#c02020', w:'1.0', op:'0.80'},
            trine:          {color:'#2060c0', w:'0.8', op:'0.75'},
            square:         {color:'#c02020', w:'0.8', op:'0.75'},
            sextile:        {color:'#2060c0', w:'0.7', op:'0.75'},
            quincunx:       {color:'#208040', w:'0.6', op:'0.60'},
            semi_sextile:   {color:'#2060c0', w:'0.5', op:'0.40'},
            semisquare:     {color:'#c02020', w:'0.5', op:'0.45'},
            sesquiquadrate: {color:'#c02020', w:'0.5', op:'0.45'}
        };

        // ── Center circle fill — BEFORE aspect lines ──────────────────────────
        mk('circle', {cx:CX, cy:CY, r:RC, fill:C.card, stroke:'none'});

        // ── Aspect glyph at midpoint of each line ─────────────────────────────
        function aspGlyph(x1, y1, x2, y2, type, color) {
            var mx = parseFloat(((x1 + x2) / 2).toFixed(1));
            var my = parseFloat(((y1 + y2) / 2).toFixed(1));
            var s  = 3.8, sw = '0.8';
            mk('circle', {cx:mx, cy:my, r:String((s * 1.15).toFixed(1)), fill:'none', stroke:'none'});
            if (type === 'trine') {
                var h = s * 0.9;
                mk('polygon', {
                    points: mx+','+(my-h).toFixed(1)+' '+(mx-h*0.866).toFixed(1)+','+(my+h*0.5).toFixed(1)+' '+(mx+h*0.866).toFixed(1)+','+(my+h*0.5).toFixed(1),
                    fill:'none', stroke:color, 'stroke-width':sw, 'stroke-linejoin':'round', 'pointer-events':'none'
                });
            } else if (type === 'square') {
                mk('rect', {x:(mx-s*0.72).toFixed(1), y:(my-s*0.72).toFixed(1),
                    width:String((2*s*0.72).toFixed(1)), height:String((2*s*0.72).toFixed(1)),
                    fill:'none', stroke:color, 'stroke-width':sw, 'pointer-events':'none'});
            } else if (type === 'sextile') {
                for (var si = 0; si < 6; si++) {
                    var sa = si * 60 * Math.PI / 180;
                    mk('line', {x1:mx, y1:my,
                        x2:parseFloat((mx + s * Math.cos(sa)).toFixed(1)),
                        y2:parseFloat((my + s * Math.sin(sa)).toFixed(1)),
                        stroke:color, 'stroke-width':sw, 'pointer-events':'none'});
                }
            } else if (type === 'conjunction') {
                mk('circle', {cx:mx, cy:my, r:String(s.toFixed(1)), fill:'none', stroke:color, 'stroke-width':sw});
                mk('circle', {cx:mx, cy:my, r:'1.4', fill:color, stroke:'none'});
            } else if (type === 'opposition') {
                mk('line', {x1:(mx-s).toFixed(1), y1:my, x2:(mx+s).toFixed(1), y2:my, stroke:color, 'stroke-width':sw});
                mk('circle', {cx:mx, cy:my, r:'1.4', fill:color, stroke:'none'});
            } else if (type === 'quincunx') {
                mk('line', {x1:(mx-s*0.5).toFixed(1), y1:(my+s*0.5).toFixed(1), x2:mx, y2:(my-s*0.5).toFixed(1), stroke:color, 'stroke-width':'0.9', 'pointer-events':'none'});
                mk('line', {x1:(mx+s*0.5).toFixed(1), y1:(my+s*0.5).toFixed(1), x2:mx, y2:(my-s*0.5).toFixed(1), stroke:color, 'stroke-width':'0.9', 'pointer-events':'none'});
            } else if (type === 'semi_sextile') {
                var te = mk('text', {x:mx, y:(my+s*0.4).toFixed(1), 'text-anchor':'middle', 'dominant-baseline':'middle',
                    fill:color, 'font-size':'7', 'pointer-events':'none'});
                te.textContent = '\u26ba';
            }
        }

        // ── Natal aspect lines + glyphs ───────────────────────────────────────
        var lonMap = {};
        for (var i = 0; i < PL.length; i++) lonMap[PL[i].body] = PL[i].lon;
        for (var i = 0; i < AS.length; i++) {
            var asp  = AS[i];
            var lonA = lonMap[asp.a], lonB = lonMap[asp.b];
            if (lonA === undefined || lonB === undefined) continue;
            if (asp.type === 'mutual_reception') continue;
            var cfg = ASP_C[asp.type] || {color:'#505060', w:'0.5', op:'0.30'};
            var ap1 = pol(l2a(lonA), RC), ap2 = pol(l2a(lonB), RC);
            mk('line', {x1:ap1[0], y1:ap1[1], x2:ap2[0], y2:ap2[1], stroke:cfg.color, 'stroke-width':cfg.w, opacity:cfg.op});
            aspGlyph(ap1[0], ap1[1], ap2[0], ap2[1], asp.type, cfg.color);
        }

        // ── Planet-to-angle dashed lines (ASC + MC) ───────────────────────────
        var ANG_ORBS = [
            {type:'conjunction', angle:  0, orb:8},
            {type:'opposition',  angle:180, orb:8},
            {type:'trine',       angle:120, orb:6},
            {type:'square',      angle: 90, orb:6},
            {type:'sextile',     angle: 60, orb:4}
        ];
        function angAsp(lonA, lonB) {
            var d = Math.abs(lonA - lonB); if (d > 180) d = 360 - d;
            var best = null, bo = Infinity;
            for (var k = 0; k < ANG_ORBS.length; k++) {
                var dev = Math.abs(d - ANG_ORBS[k].angle);
                if (dev <= ANG_ORBS[k].orb && dev < bo) { bo = dev; best = ANG_ORBS[k].type; }
            }
            return best;
        }
        function drawAngLine(lonP, lonAng) {
            var type = angAsp(lonP, lonAng);
            if (!type) return;
            var cfg2 = ASP_C[type];
            var al1 = pol(l2a(lonP),   RC), al2 = pol(l2a(lonAng), RC);
            mk('line', {x1:al1[0], y1:al1[1], x2:al2[0], y2:al2[1], stroke:cfg2.color,
                'stroke-width': String((parseFloat(cfg2.w) * 0.85).toFixed(2)),
                'opacity':       String((parseFloat(cfg2.op) * 0.85).toFixed(2)),
                'stroke-dasharray':'2.5,2'});
        }
        if (ascLon !== null) {
            for (var i = 0; i < PL.length; i++) drawAngLine(PL[i].lon, ascLon);
            if (HS.length === 12) { for (var i = 0; i < PL.length; i++) drawAngLine(PL[i].lon, HS[9]); }
        }

        // ── Center ring stroke ────────────────────────────────────────────────
        mk('circle', {cx:CX, cy:CY, r:RC, fill:'none', stroke:C.border, 'stroke-width':'0.4'});

        // ── ASC/DSC/MC/IC axis lines with arrowheads ──────────────────────────
        if (ascLon !== null) {
            function arrowHead(ax, ay, ang, color, sz) {
                var a1 = ang + Math.PI * 5 / 6, a2 = ang - Math.PI * 5 / 6;
                mk('polygon', {
                    points: parseFloat(ax).toFixed(1)+','+parseFloat(ay).toFixed(1)+' '+
                            (ax+sz*Math.cos(a1)).toFixed(1)+','+(ay+sz*Math.sin(a1)).toFixed(1)+' '+
                            (ax+sz*Math.cos(a2)).toFixed(1)+','+(ay+sz*Math.sin(a2)).toFixed(1),
                    fill: color, stroke: 'none', 'pointer-events': 'none'
                });
            }
            function axisLine(lon1, lon2) {
                var a1 = l2a(lon1), a2 = l2a(lon2);
                var ox1 = pol(a1, R4IN), ox2 = pol(a2, R4IN);
                var ix1 = pol(a1, R3IN), ix2 = pol(a2, R3IN);
                mk('line', {x1:ix1[0], y1:ix1[1], x2:ox1[0], y2:ox1[1], stroke:C.muted, 'stroke-width':'1.2', opacity:'0.9'});
                arrowHead(ox1[0], ox1[1], Math.atan2(ox1[1] - CY, ox1[0] - CX), C.muted, 5);
                mk('line', {x1:ix2[0], y1:ix2[1], x2:ox2[0], y2:ox2[1], stroke:C.muted, 'stroke-width':'1.2', opacity:'0.9'});
            }
            axisLine(ascLon, ascLon + 180);
            if (HS.length === 12) axisLine(HS[9], HS[3]);
        }

        // ── Natal planet glyphs at RPG=112 (with spreadPlanets) ──────────────
        var BODY_G = ['\u2609','\u263d','\u263f','\u2640','\u2642','\u2643','\u2644','\u26e2','\u2646','\u2647','\u26b7','\u260a','\u26b8'];
        for (var i = 0; i < BODY_G.length; i++) BODY_G[i] += '\ufe0e';
        var BODY_C = ['#c49a18','#5588aa','#3a7a68','#a84e80','#c03828','#9a6218','#4a6898','#2888a8','#3858a8','#6838a0','#508080','#4068a0','#885060'];

        function spreadPlanets(planets, rGlyph) {
            var pts = [];
            for (var i = 0; i < planets.length; i++) {
                pts.push({body:planets[i].body, lon:planets[i].lon, rx:planets[i].rx, origA:l2a(planets[i].lon), a:l2a(planets[i].lon), r:rGlyph});
            }
            // Sort ONCE by true position — never re-sort — guarantees lines never cross
            pts.sort(function(a, b) { return a.origA - b.origA; });
            function clampToSign(p) {
                var signIdx = Math.floor(((p.lon % 360) + 360) % 360 / 30);
                var aMid = l2a(signIdx * 30 + 15);
                var dd = ((p.a - aMid + 360) % 360);
                if (dd > 180) dd -= 360;
                if (dd >  14) p.a = (aMid + 14 + 360) % 360;
                if (dd < -14) p.a = (aMid - 14 + 360) % 360;
            }
            var MIN_ANG = 9;
            for (var iter = 0; iter < 60; iter++) {
                var moved = false;
                for (var i = 0; i < pts.length; i++) {
                    var j    = (i + 1) % pts.length;
                    var diff = ((pts[j].a - pts[i].a) + 360) % 360;
                    if (diff > 180) diff -= 360; // signed: handles crossed pairs too
                    if (diff < MIN_ANG) {
                        var push = (MIN_ANG - diff) / 2;
                        pts[i].a = (pts[i].a - push + 360) % 360;
                        pts[j].a = (pts[j].a + push + 360) % 360;
                        moved = true;
                    }
                }
                for (var i = 0; i < pts.length; i++) clampToSign(pts[i]);
                if (!moved) break;
            }
            return pts;
        }

        var natalPts = spreadPlanets(PL, RPG);
        for (var i = 0; i < natalPts.length; i++) {
            var p   = natalPts[i];
            var pp  = pol(p.a, p.r);
            var lp  = pol(p.origA, RZ);
            mk('line', {x1:lp[0], y1:lp[1], x2:pp[0], y2:pp[1], stroke:C.border, 'stroke-width':'0.5', 'stroke-dasharray':'2,3'});
            tx(pp[0], pp[1], BODY_G[p.body] || '\u2605', {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'13', 'fill': BODY_C[p.body] || ACCENT,
                'font-family':'serif', 'pointer-events':'none'
            });
            var dis = p.lon % 30, dg = Math.floor(dis), mn = Math.floor((dis - dg) * 60);
            var dlp = pol(p.a, p.r - 12);
            tx(dlp[0], dlp[1], dg + '\u00b0' + pad2(mn) + "'", {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'5.5', 'fill':C.muted,
                'font-family':'sans-serif', 'pointer-events':'none'
            });
            if (p.rx) {
                tx(pp[0] + 8, pp[1] - 6, 'r', {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'7', 'fill':C.muted,
                    'font-family':'serif', 'font-style':'italic', 'pointer-events':'none'
                });
            }
        }

        // ── Transit planet glyphs at RTG=168 (with spreadFree) ───────────────
        function spreadFree(planets, rGlyph) {
            if (planets.length === 0) return [];
            var pts = [];
            for (var i = 0; i < planets.length; i++) {
                pts.push({body:planets[i].body, lon:planets[i].lon, rx:planets[i].rx, origA:l2a(planets[i].lon), a:l2a(planets[i].lon), r:rGlyph});
            }
            pts.sort(function(a, b) { return a.origA - b.origA; });
            var N = pts.length, MIN_ANG = 7;
            for (var iter = 0; iter < 80; iter++) {
                var moved = false;
                for (var i = 0; i < N; i++) {
                    var j    = (i + 1) % N;
                    var diff = ((pts[j].a - pts[i].a) + 360) % 360;
                    if (diff > 180) diff -= 360;
                    if (diff < MIN_ANG) {
                        var push = (MIN_ANG - diff) / 2;
                        pts[i].a = (pts[i].a - push + 360) % 360;
                        pts[j].a = (pts[j].a + push + 360) % 360;
                        moved = true;
                    }
                }
                if (!moved) break;
            }
            return pts;
        }

        var transitPts = spreadFree(TR, RTG);
        for (var i = 0; i < transitPts.length; i++) {
            var p   = transitPts[i];
            var pp  = pol(p.a, p.r);
            var lp  = pol(p.origA, RO);
            mk('line', {x1:lp[0], y1:lp[1], x2:pp[0], y2:pp[1], stroke:C.border, 'stroke-width':'0.5', 'stroke-dasharray':'2,3'});
            tx(pp[0], pp[1], BODY_G[p.body] || '\u2605', {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'12', 'fill': BODY_C[p.body] || ACCENT,
                'font-family':'serif', 'pointer-events':'none'
            });
            var dis = p.lon % 30, dg = Math.floor(dis), mn = Math.floor((dis - dg) * 60);
            var dlp = pol(p.a, p.r + 14);
            tx(dlp[0], dlp[1], dg + '\u00b0' + pad2(mn) + "'", {
                'text-anchor':'middle', 'dominant-baseline':'central',
                'font-size':'5.5', 'fill':C.muted,
                'font-family':'sans-serif', 'pointer-events':'none'
            });
            if (p.rx) {
                tx(pp[0] + 8, pp[1] - 6, 'r', {
                    'text-anchor':'middle', 'dominant-baseline':'central',
                    'font-size':'7', 'fill':C.muted,
                    'font-family':'serif', 'font-style':'italic', 'pointer-events':'none'
                });
            }
        }

        // ── ASC/MC labels on top ──────────────────────────────────────────────
        if (ascLon !== null) {
            function degStr(lon) {
                var w = ((lon % 30) + 30) % 30, d = Math.floor(w), m = Math.round((w - d) * 60);
                return d + '\u00b0' + pad2(m >= 60 ? 59 : m) + "'";
            }
            function lbl(x, y, name, deg, fill) {
                tx(x, y-4, name, {'text-anchor':'middle','dominant-baseline':'central','font-size':'7','fill':fill,'font-family':'sans-serif','font-weight':'bold','pointer-events':'none'});
                tx(x, y+5, deg,  {'text-anchor':'middle','dominant-baseline':'central','font-size':'5.5','fill':fill,'font-family':'sans-serif','pointer-events':'none'});
            }
            lbl(CX - RC + 9, CY, 'AC', degStr(ascLon), ACCENT);
            lbl(CX + RC - 9, CY, 'DC', degStr((ascLon + 180) % 360), C.muted);
            if (HS.length === 12) {
                var mp = pol(l2a(HS[9]), RC - 14);
                lbl(mp[0], mp[1], 'MC', degStr(HS[9]), ACCENT);
                var ip = pol(l2a(HS[3]), RC - 14);
                lbl(ip[0], ip[1], 'IC', degStr(HS[3]), C.muted);
            }
        }
    } // end draw()

    @if($pdfMode)
    window.onload = draw;
    @else
    window.addEventListener('alpine:initialized', draw);
    if (document.readyState === 'complete') { requestAnimationFrame(draw); }
    document.addEventListener('DOMContentLoaded', function() { requestAnimationFrame(draw); });
    new MutationObserver(draw).observe(document.documentElement, {attributes:true, attributeFilter:['data-theme']});
    @endif
})();
</script>
