<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stellar Omens — Coming Soon</title>
    @vite(['resources/css/app.scss'])
</head>
<body class="min-h-screen" style="display:flex;align-items:center;justify-content:center;text-align:center;">
    <div>
        <div class="logo-text" style="font-size:1.6rem;letter-spacing:0.12em;">
            STELLAR <span class="logo-accent">✦ OMENS</span>
        </div>
        <p style="margin-top:1.5rem;color:var(--theme-muted);font-size:0.95rem;letter-spacing:0.04em;">
            Launching {{ $launch->format('F j, Y') }} at {{ $launch->format('H:i') }}
        </p>
    </div>
</body>
</html>
