<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\CityTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportCities extends Command
{
    protected $signature = 'horoscope:import-cities
                            {--fresh : Truncate existing data before import}
                            {--skip-download : Use already-downloaded file in storage/app/geonames/cities5000.txt}';

    protected $description = 'Import world cities (population 5000+) from GeoNames into cities + city_translations tables';

    private const DOWNLOAD_URL = 'https://download.geonames.org/export/dump/cities5000.zip';
    private const STORAGE_DIR  = 'geonames';
    private const ZIP_FILE     = 'cities5000.zip';
    private const TXT_FILE     = 'cities5000.txt';
    private const CHUNK_SIZE   = 500;

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Truncating cities and city_translations tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            CityTranslation::truncate();
            City::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $txtPath = Storage::path(self::STORAGE_DIR . '/' . self::TXT_FILE);

        if (! $this->option('skip-download')) {
            $this->downloadAndExtract($txtPath);
        } elseif (! file_exists($txtPath)) {
            $this->error('File not found: ' . $txtPath);
            $this->line('Run without --skip-download to fetch it automatically.');
            return self::FAILURE;
        }

        $this->import($txtPath);

        return self::SUCCESS;
    }

    private function downloadAndExtract(string $txtPath): void
    {
        Storage::makeDirectory(self::STORAGE_DIR);
        $zipPath = Storage::path(self::STORAGE_DIR . '/' . self::ZIP_FILE);

        $this->info('Downloading ' . self::DOWNLOAD_URL . ' ...');

        $response = Http::timeout(120)->get(self::DOWNLOAD_URL);

        if (! $response->successful()) {
            $this->error('Download failed: HTTP ' . $response->status());
            exit(self::FAILURE);
        }

        file_put_contents($zipPath, $response->body());
        $this->line('Saved to ' . $zipPath);

        $this->info('Extracting...');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Failed to open zip file.');
            exit(self::FAILURE);
        }
        $zip->extractTo(Storage::path(self::STORAGE_DIR));
        $zip->close();

        $this->line('Extracted: ' . $txtPath);
    }

    private function import(string $txtPath): void
    {
        $this->info('Counting lines...');
        $total = 0;
        $fh = fopen($txtPath, 'r');
        while (fgets($fh) !== false) {
            $total++;
        }
        fclose($fh);

        $this->info("Importing {$total} cities...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $cityChunk       = [];
        $translationChunk = [];
        $processed       = 0;

        // GeoNames TSV columns (0-indexed):
        // 0:geonameid 1:name 2:asciiname 3:alternatenames 4:lat 5:lng
        // 6:feature_class 7:feature_code 8:country_code 9:cc2
        // 10:admin1 11:admin2 12:admin3 13:admin4
        // 14:population 15:elevation 16:dem 17:timezone 18:modification_date

        $fh = fopen($txtPath, 'r');
        while (($line = fgets($fh)) !== false) {
            $fields = explode("\t", trim($line));

            if (count($fields) < 18) {
                $bar->advance();
                continue;
            }

            $geonamesId  = (int) $fields[0];
            $name        = $fields[1];
            $countryCode = $fields[8];
            $lat         = (float) $fields[4];
            $lng         = (float) $fields[5];
            $timezone    = $fields[17];
            $population  = (int) $fields[14];

            if (empty($countryCode) || empty($timezone)) {
                $bar->advance();
                continue;
            }

            $cityChunk[] = [
                'geonames_id'  => $geonamesId,
                'country_code' => $countryCode,
                'lat'          => $lat,
                'lng'          => $lng,
                'timezone'     => $timezone,
                'population'   => $population,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            $translationChunk[] = [
                'geonames_id' => $geonamesId, // temp key for joining after insert
                'locale'      => 'en',
                'name'        => $name,
            ];

            $processed++;

            if ($processed % self::CHUNK_SIZE === 0) {
                $this->flushChunk($cityChunk, $translationChunk);
                $cityChunk        = [];
                $translationChunk = [];
            }

            $bar->advance();
        }
        fclose($fh);

        if (! empty($cityChunk)) {
            $this->flushChunk($cityChunk, $translationChunk);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Imported {$processed} cities.");
    }

    private function flushChunk(array $cities, array $translations): void
    {
        // Insert cities, ignore duplicates (re-runnable)
        DB::table('cities')->insertOrIgnore(
            array_map(fn ($c) => array_diff_key($c, ['geonames_id' => null]) + ['geonames_id' => $c['geonames_id']], $cities)
        );

        // Fetch the IDs we just inserted (or already existed)
        $geonamesIds = array_column($cities, 'geonames_id');
        $idMap = DB::table('cities')
            ->whereIn('geonames_id', $geonamesIds)
            ->pluck('id', 'geonames_id');

        $translationRows = [];
        foreach ($translations as $t) {
            $cityId = $idMap[$t['geonames_id']] ?? null;
            if ($cityId === null) {
                continue;
            }
            $translationRows[] = [
                'city_id' => $cityId,
                'locale'  => $t['locale'],
                'name'    => $t['name'],
            ];
        }

        if (! empty($translationRows)) {
            DB::table('city_translations')->insertOrIgnore($translationRows);
        }
    }
}
