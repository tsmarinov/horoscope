<?php

namespace App\Http\Controllers\Concerns;

trait BuildsPdfFooter
{
    private function buildFooterFile(): string
    {
        $html = view('partials.pdf-page-footer')->render();
        $path = sys_get_temp_dir() . '/pdf_footer_' . uniqid() . '.html';
        file_put_contents($path, $html);
        register_shutdown_function(static function () use ($path) { @unlink($path); });
        return 'file://' . $path;
    }
}
