<?php

namespace App\Console\Commands\Tlr;

use Laravel\Scout\Console\ImportCommand;

class ScoutImportCommand extends ImportCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tlr:scout-import {model}';
}
