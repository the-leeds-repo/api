<?php

namespace App\Console\Commands\Tlr;

use App\BatchUpload\BatchUploader;
use Illuminate\Console\Command;

class BatchUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tlr:batch-upload {path : The path to the spreadsheet to upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uploads an xlsx spreadsheet to the database';

    /**
     * @var \App\BatchUpload\BatchUploader
     */
    protected $batchUploader;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->batchUploader = new BatchUploader();
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $this->line('Uploading file...');

        $path = storage_path($this->argument('path'));
        $this->batchUploader->upload($path);

        $this->info('Spreadsheet uploaded');
    }
}
