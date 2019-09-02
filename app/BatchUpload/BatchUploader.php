<?php

namespace App\BatchUpload;

use App\Models\Collection;
use App\Models\Organisation;
use App\Models\Taxonomy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BatchUploader
{
    /**
     * @var \PhpOffice\PhpSpreadsheet\Reader\Xlsx
     */
    protected $reader;

    /**
     * BatchUploader constructor.
     */
    public function __construct()
    {
        $this->reader = new XlsxReader();
        $this->reader->setReadDataOnly(true);
    }

    /**
     * Validates and then uploads the file.
     *
     * @param string $filePath
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Exception
     */
    public function upload(string $filePath)
    {
        // Load the spreadsheet.
        $spreadsheet = $this->reader->load($filePath);

        // Load each worksheet.
        $adminsSheet = $spreadsheet->getSheetByName('Admins');
        $organisationsSheet = $spreadsheet->getSheetByName('Organisations');
        $servicesSheet = $spreadsheet->getSheetByName('Services');
        $topicsSheet = $spreadsheet->getSheetByName('Topics');
        $snomedSheet = $spreadsheet->getSheetByName('SNOMED');

        // Convert the worksheets to associative arrays.
        $admins = $this->toArray($adminsSheet);
        $organisations = $this->toArray($organisationsSheet);
        $services = $this->toArray($servicesSheet);
        $topics = $this->toArray($topicsSheet);
        $snomedCodes = $this->toArray($snomedSheet);

        // Process.
        DB::transaction(
            function () use (
                $admins,
                $organisations,
                $services,
                $topics,
                $snomedCodes
            ) {
                $this->truncateTables();

                // Process topics.
                $this->processTopics($topics);

                // TODO: Process SNOMED codes.

                // TODO: Process organisations.

                // TODO: Process services.

                // TODO: Process admins.
            }
        );
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    protected function toArray(Worksheet $sheet): array
    {
        $array = $sheet->toArray();
        $headings = array_shift($array);

        $array = array_map(function ($row) use ($headings) {
            $resource = [];

            foreach ($headings as $column => $heading) {
                $resource[$heading] = $row[$column];
            }

            return $resource;
        }, $array);

        return $array;
    }

    /**
     * Truncate tables.
     */
    protected function truncateTables()
    {
        // Delete SNOMED collections.
        Collection::query()->snomed()->get()->each(
            function (Collection $snomedCode) {
                $snomedCode->delete();
            }
        );

        // Delete organisations.
        Organisation::all()->each(function (Organisation $organisation) {
            $organisation->delete();
        });
    }

    /**
     * @param array $topics
     */
    protected function processTopics(array &$topics)
    {
        $topLevelTaxonomy = Taxonomy::category();

        // Give each topic a UUID.
        foreach ($topics as &$topic) {
            $topic['_id'] = Str::uuid()->toString();
        }

        // Map each topic's parent_id to a UUID.
        foreach ($topics as &$topic) {
            // Set parent ID of top level topics to Category taxonomy.
            if ($topic['parent_id'] === null) {
                $topic['_parent_id'] = $topLevelTaxonomy->id;
                continue;
            }

            $topic['_parent_id'] = Arr::first(
                $topics,
                function (array $parentTopic) use ($topic) {
                    return $topic['parent_id'] === $parentTopic['id'];
                }
            )['_id'];
        }

        // Set the order for the topics.
        foreach ($topics as &$topic) {
            $topic['_order'] = 0; // TODO
        }

        // Persist the topics.
        foreach ($topics as &$topic) {
            Taxonomy::create([
                'id' => $topic['_id'],
                'parent_id' => $topic['_parent_id'],
                'name' => $topic['name'],
                'order' => $topic['_order'],
            ]);
        }
    }
}
