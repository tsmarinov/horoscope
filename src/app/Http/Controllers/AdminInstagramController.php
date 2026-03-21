<?php

namespace App\Http\Controllers;

use App\Services\SunSignHoroscopeService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminInstagramController extends Controller
{
    private function authorise(): bool
    {
        $req        = request();
        $allowedIps = array_filter(array_map('trim', explode(',', env('ADMIN_IPS', '127.0.0.1,::1'))));

        if (in_array($req->ip(), $allowedIps)) {
            return true;
        }

        $adminUser = env('ADMIN_USER', '');
        $adminPass = env('ADMIN_PASS', '');

        return $adminUser && $req->getUser() === $adminUser && $req->getPassword() === $adminPass;
    }

    public function daily(SunSignHoroscopeService $service, ?string $date = null): View|Response
    {
        if (!$this->authorise()) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Stellar Omens Admin"',
            ]);
        }

        $date = $date
            ? (Carbon::createFromFormat('Y-m-d', $date) ?? Carbon::today())
            : Carbon::today();

        $signKeys   = array_keys(SunSignController::SIGNS);
        $horoscopes = $service->getForDate($date);

        $slides = [];
        for ($i = 0; $i < 12; $i += 2) {
            $pair = [];
            $pair[$signKeys[$i]]     = SunSignController::SIGNS[$signKeys[$i]];
            $pair[$signKeys[$i + 1]] = SunSignController::SIGNS[$signKeys[$i + 1]];
            $slides[] = $pair;
        }

        return view('admin.instagram-daily', [
            'date'       => $date,
            'slides'     => $slides,
            'horoscopes' => $horoscopes,
        ]);
    }

    public function slide(SunSignHoroscopeService $service, string $date, int $num): Response
    {
        if (!$this->authorise()) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Stellar Omens Admin"',
            ]);
        }

        $date = Carbon::createFromFormat('Y-m-d', $date) ?? Carbon::today();

        $signKeys = array_keys(SunSignController::SIGNS);
        $offset   = ($num - 1) * 2;
        $pair     = [
            $signKeys[$offset]     => SunSignController::SIGNS[$signKeys[$offset]],
            $signKeys[$offset + 1] => SunSignController::SIGNS[$signKeys[$offset + 1]],
        ];

        $html = view('admin.instagram-slide', [
            'date'       => $date,
            'pair'       => $pair,
            'horoscopes' => $service->getForDate($date),
            'slideNum'   => $num,
        ])->render();

        $tmpHtml = tempnam(sys_get_temp_dir(), 'igslide') . '.html';
        $tmpPng  = tempnam(sys_get_temp_dir(), 'igslide') . '.png';

        file_put_contents($tmpHtml, $html);

        $cmd = sprintf(
            'wkhtmltoimage --width 1080 --disable-smart-width --zoom 1 --crop-w 1080 --crop-h 1080 --crop-x 0 --crop-y 0 --enable-local-file-access --load-error-handling ignore --quiet %s %s 2>/dev/null',
            escapeshellarg($tmpHtml),
            escapeshellarg($tmpPng)
        );

        exec($cmd, $output, $exitCode);

        @unlink($tmpHtml);

        if ($exitCode !== 0 || !file_exists($tmpPng) || filesize($tmpPng) === 0) {
            @unlink($tmpPng);
            return response('Image generation failed', 500);
        }

        $png = file_get_contents($tmpPng);
        @unlink($tmpPng);

        return response($png, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => sprintf(
                'attachment; filename="stellar-omens-%s-slide-%d.png"',
                $date->toDateString(),
                $num
            ),
        ]);
    }
}
