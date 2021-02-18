<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Pest\Plugin;
use Storage;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class CheckWebsites extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'check:websites';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Quick way to check for website updates. ';

    private $updated = [];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $newWebsite = $this->choice('Would you like to check a new website?', [
            'Yes', 'No'
        ], 'No');

        $websites = collect([]);

        if (Storage::exists('websites.json')) {
            $websites = collect(json_decode(Storage::get('websites.json')));
        }

        if ($newWebsite == 'Yes') {
            $newWebsiteURL = $this->ask('What is the URL of the website?');
            $websites->push((object) ['url' => $newWebsiteURL, 'hash' => '']);
        }

        if ($websites->count() < 1) {
            $this->error('No Websites to check');
            return false;
        }

        $websites = $websites->transform(function($website) {
            return $this->checkForUpdate($website);
        });

        Storage::put('websites.json', $websites->toJson());

        $this->table(
            ['Website', 'Updated'],
            $this->updated
        );
    }

    private function checkForUpdate($website)
    {
        $url = $website->url;

        $contents = file_get_contents($url);
        $hash     = $website->hash;

        if ($hash == ($pageHash = md5($contents))) {
            array_push($this->updated,[$website->url, 'No']);

        } else {
            array_push($this->updated,[$website->url, 'Yes']);
            $website->hash = $pageHash;
        }

        return $website;
    }
}
