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
                &$admins,
                &$organisations,
                &$services,
                &$topics,
                &$snomedCodes
            ) {
                $this->truncateTables();

                // Process topics.
                $this->processTopics($topics);

                // Process SNOMED codes.
                $this->processSnomedCodes($topics, $snomedCodes);

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

        foreach ($array as $rowIndex => &$rowValue) {
            $resource = [];

            foreach ($headings as $column => $heading) {
                $resource[$heading] = $rowValue[$column];
            }

            $rowValue = $resource;
        }

        return $array;
    }

    /**
     * Truncate tables.
     */
    protected function truncateTables()
    {
        // Delete topic taxonomies.
        Taxonomy::category()->children()->each(
            function (Taxonomy $topic) {
                $topic->delete();
            }
        );

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

        // Persist the topics.
        foreach ($topics as $topic) {
            if (Taxonomy::find($topic['_id'])) {
                continue;
            }

            Taxonomy::create([
                'id' => $topic['_id'],
                'parent_id' => $topic['_parent_id'],
                'name' => $topic['name'],
                'order' => 1,
            ]);
        }
    }

    /**
     * @param array $topics
     * @param array $snomedCodes
     */
    protected function processSnomedCodes(array &$topics, array &$snomedCodes)
    {
        // Map each SNOMED code's topic IDs to their UUID.
        foreach ($snomedCodes as $row => &$snomedCode) {
            $topicIds = explode(';', $snomedCode['topic_ids']);

            $linkedTopics = array_filter(
                $topics,
                function (array $topic) use ($topicIds): bool {
                    return in_array((string)$topic['id'], $topicIds);
                }
            );

            $snomedCode['_topic_ids'] = Arr::pluck(
                $linkedTopics,
                '_id'
            );
        }

        // Persist the SNOMED codes.
        foreach ($snomedCodes as &$snomedCode) {
            $snomedCode['_model'] = Collection::create([
                'type' => Collection::TYPE_SNOMED,
                'name' => $snomedCode['code'],
                'meta' => [
                    'name' => $snomedCode['name'] ?: null,
                ],
                'order' => 1,
            ]);
        }

        // Persist the SNOMED topic links.
        foreach ($snomedCodes as $snomedCode) {
            $snomedCode['_model']->syncCollectionTaxonomies(
                Taxonomy::query()->whereIn(
                    'id',
                    $snomedCode['_topic_ids']
                )->get()
            );
        }
    }
}
