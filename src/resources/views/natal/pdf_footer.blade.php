<!DOCTYPE html>
<html><head><meta charset="UTF-8"/><style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; padding: 0 20mm; }
.footer { display:table; width:100%; border-top:1px solid #e0d8f0; padding-top:5px; }
.logo  { display:table-cell; vertical-align:middle; }
.logo-name   { font-size:9pt; font-weight:800; color:#6a329f; letter-spacing:0.02em; }
.logo-domain { font-size:7pt; color:#a090c0; letter-spacing:0.04em; margin-left:6px; }
.pages { display:table-cell; vertical-align:middle; text-align:right; font-size:8pt; color:#999; }
</style></head>
<body>
<div class="footer">
    <div class="logo">
        <span class="logo-name">Stellar ✦ Omens</span>
        <span class="logo-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span>
    </div>
    <div class="pages" id="pg"></div>
</div>
<script>
var vars = {};
window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, k, v) { vars[k] = decodeURIComponent(v); });
document.getElementById('pg').textContent = (vars.page || '1') + ' / ' + (vars.toPage || vars.topage || '1');
</script>
</body></html>
