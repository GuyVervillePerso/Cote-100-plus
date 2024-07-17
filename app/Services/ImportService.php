<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use League\HTMLToMarkdown\HtmlConverter;
use Statamic\Facades\Asset;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Tiptap\Editor;
use Tiptap\Extensions;
use Tiptap\Marks;
use Tiptap\Nodes;

class ImportService
{
    protected $tags = [];

    protected $lang = 'default';

    public function __construct()
    {
        self::getAllTags();
    }

    protected function getAllTags()
    {
        $tags = Term::query()
            ->where('categories')
            ->where('locale', 'default')
            ->get()->toArray();
        foreach ($tags as $tag) {
            $this->tags[] = $tag['slug'];
        }
    }

    public function importJSONEntries()
    {

        $jsonPath = storage_path('app/import/entre_les_lignes.json');
        $json = json_decode(file_get_contents($jsonPath), true);
        foreach (array_slice($json['pages'], 40, 40) as $item) {
            $this->importJsonEntry($item);
        }
    }

    protected function importJsonEntry($item): void
    {
        $entry_fr = Entry::query()
            ->where('collection', 'entre_les_lignes')
            ->where('slug', $item['settings']['name'])
            ->first();

        if (! $entry_fr) {
            $entryTags = $this->getEntryTags($item['data']['blog_article_categories']);
            $carbon = Carbon::parse($item['data']['blog_publication_date']);
            $slugFr = $item['settings']['name'];
            $slugEn = $item['settings']['name_en'];
            $titleFr = $item['data']['title']['default'];
            $titleEn = $item['data']['title']['en'];
            ray($titleFr)->green();
            $contentFr = $this->reformatImageContent($this->createEntry($item['data']['blog_content']['default']));
            $contentEn = $this->reformatImageContent($this->createEntry($item['data']['blog_content']['en']));

            $dataFr = [
                'title' => $titleFr,
                'html_content' => $contentFr,
                'updated_at' => $carbon,
                'categories' => $entryTags,
                'chapeau' => strip_tags($item['data']['blog_summary']['default']),
                'author' => '9c87d8d7-e83f-438d-8d13-6efd9c2fae40',
                'slug' => $slugFr,
            ];
            $dataEn = [
                'title' => $titleEn,
                'html_content' => $contentEn,
                'chapeau' => strip_tags($item['data']['blog_summary']['en']),
                'slug' => $slugEn,
            ];
            if (count($item['data']['blog_images_in_content']) > 0) {
                $this->moveImages($item['data']['blog_images_in_content']);
            }
            if (count($item['data']['blog_main_img']) > 0) {
                $assetId = $this->moveImages($item['data']['blog_main_img']);
                if ($assetId) {
                    $dataFr['main_visual'] = $assetId;
                }
            }
            try {
                $entry_fr = Entry::make()
                    ->locale('default') // Set the locale to French (default)
                    ->collection('entre_les_lignes') // Set the collection handle
                    ->slug($slugFr) // Set the slug
                    ->data($dataFr) // Set the data fields
                    ->date($carbon)
                    ->save();
                if ($entry_fr) {
                    $entry_fr_id = Entry::query()
                        ->where('collection', 'entre_les_lignes')
                        ->where('slug', $slugFr)
                        ->first()->id();
                    Entry::make()
                        ->locale('anglais') // Set the locale to English
                        ->collection('entre_les_lignes') // Set the collection handle
                        ->slug($slugEn) // Set the slug
                        ->data($dataEn) // Set the data fields
                        ->date($carbon)
                        ->origin($entry_fr_id) // Set the origin ID
                        ->save();
                }
            } catch (Exception $e) {
                dd($e);
            }
        }
    }

    protected function getEntryTags($tagArray = [])
    {
        $tagResults = [];
        if (count($tagArray) > 0) {
            foreach ($tagArray as $tag) {
                $t = str_replace('/', '', $tag);
                if (in_array($t, $this->tags)) {
                    $tagResults[] = $t;
                }
            }
        }

        return $tagResults;
    }

    protected function reformatImageContent($content): array
    {
        foreach ($content as $key => $item) {
            if (isset($item['content'][0]['type'])) {
                if ($item['content'][0]['type'] == 'image') {
                    $basename = basename($item['content'][0]['attrs']['src']);
                    $imageArray[$basename] = ['url' => $item['content'][0]['attrs']['src']];
                    $content[$key]['content'][0]['attrs']['src'] = 'asset::assets::blog/'.$this->moveImages($imageArray, true);
                }
            }

        }

        return $content;
    }

    protected function moveImages($images, $sendFilename = false)
    {
        if (count($images) == 0) {
            return [];
        }
        foreach ($images as $key => $image) {
            $filename = $key;
            $imageUrl = $image['url'];
            // Get the image content from the URL
            try {
                $imageContent = file_get_contents($imageUrl);
            } catch (Exception $e) {
                return '';
            }

            // Define a temporary path to store the image
            $tempPath = storage_path('app/public/temp.jpg');
            // Use Laravel's File facade to put the image content into the temporary path
            File::put($tempPath, $imageContent);

            // Create an uploaded file instance for the image
            $file = new UploadedFile($tempPath, $filename);
            // Create an asset, set the container and the path, then upload the file
            $asset = Asset::make()
                ->container('assets')
                ->path('blog/'.$filename);
            $asset->upload($file);
            // Save the asset
            $asset->save();

            // Delete the temporary file
            File::delete($tempPath);
        }
        if ($sendFilename) {
            return $filename;
        }

        return $asset->id;
    }

    protected function createEntry($data)
    {
        return (new Editor([
            'extensions' => [
                new Extensions\StarterKit,
                new Nodes\Image,
                new Nodes\Table,
                new Nodes\TableCell,
                new Nodes\TableHeader,
                new Nodes\TableRow,
                new Marks\Link,
            ],
        ]))->setContent($data)->getDocument()['content'];
    }

    public function importBlogEntries()
    {
        $csvPath = storage_path('app/import/promenades.csv');
        // Create a CSV Reader instance
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setDelimiter('^');
        $csv->setHeaderOffset(0);
        // Get the header row from the CSV
        $header = $csv->fetchOne();
        $converter = new HtmlConverter();
        // Loop through each row in the CSV (except the header row)
        foreach ($csv as $record) {
            // Map the CSV row data to Statamic fields
            if ($record['name_fr'] == '') {
                $record['name_fr'] = $record['name_en'];
            }
            $entry_fr = Entry::query()
                ->where('collection', 'promenades')
                ->where('slug', $record['name_fr'])
                ->first();
            if ($entry_fr) {
                continue;
            }
            $contentFr = $converter->convert($record['body_fr']);
            $contentEn = $converter->convert($record['body_en']);

            $contentFr = $this->cleanText($contentFr);
            $contentEn = $this->cleanText($contentEn);

            $seoDescriptionFr = $this->truncateForSeo($contentFr);
            $seoDescriptionEn = $this->truncateForSeo($contentEn);

            $entryTags = $this->getEntryTags($record['tags']);
            $finalImages = $this->moveImages($record['images'], $record);

            $carbon = Carbon::parse($record['date']);

            $dataFr = [
                'title' => $record['title_fr'],
                'content' => $contentFr,
                'date_publication' => $record['date'],
                'tags' => $entryTags,
                'legend' => $record['legend_fr'],
                'seo_description' => $seoDescriptionFr,
                'slug' => $record['name_fr'],
            ];
            if (count($entryTags) > 0) {
                $dataFr['tags'] = $entryTags;
            }

            if ($record['legend_fr'] == '' && $record['legend_en'] != '') {
                $dataFr['legend'] = $record['legend_en'];
            }
            if ($record['legend_fr'] != '') {
                $dataFr['legend'] = $record['legend_fr'];
            }
            if (count($finalImages) > 0) {
                if (count($finalImages) == 1) {
                    $dataFr['main_visual'] = $finalImages[0];
                } else {
                    $firstImage = array_shift($finalImages);
                    $dataFr['main_visual'] = $firstImage;
                    $dataFr['secondary_visuals'] = $finalImages;
                }
                $data['images'] = $finalImages;
            }
            $dataEn = [
                'title' => $record['title_en'],
                'content' => $contentEn,
                'legend' => $record['legend_en'],
                'seo_description' => $seoDescriptionEn,
                'slug' => $record['name_en'],
            ];
            if ($record['legend_en'] != '') {
                $dataFr['legend'] = $record['legend_en'];
            }
            try {
                $entry_fr = Entry::make()
                    ->locale('default') // Set the locale to French (default)
                    ->collection('promenades') // Set the collection handle
                    ->slug($record['name_fr']) // Set the slug
                    ->blueprint('promenade') // Set the blueprint handle
                    ->data($dataFr) // Set the data fields
                    ->date($carbon) // Set the date
                    ->save();
                if ($entry_fr) {
                    $entry_fr_id = Entry::query()
                        ->where('collection', 'promenades')
                        ->where('slug', $record['name_fr'])
                        ->first()->id();
                    $entry_en = Entry::make()
                        ->locale('en') // Set the locale to English
                        ->collection('promenades') // Set the collection handle
                        ->slug($record['name_en']) // Set the slug
                        ->data($dataEn) // Set the data fields
                        ->origin($entry_fr_id) // Set the origin ID
                        ->date($carbon)
                        ->save();
                }
            } catch (Exception $e) {
                dd($e);
            }
        }
    }

    protected function cleanText($text): string
    {
        $text = str_replace('\[\[s\]\]', ' ', $text);
        $text = str_replace('* *', ' ', $text);
        $text = str_replace('\[\[etoile\]\]', '* * * * *', $text);
        $text = str_replace("'", '’', $text);

        return $text;
    }

    protected function truncateForSeo($content)
    {
        // Check if the content is binary
        if (! mb_check_encoding($content, 'UTF-8')) {
            // Content is binary, convert to UTF-8
            $content = mb_convert_encoding($content, 'UTF-8');
        }

        // Remove invalid UTF-8 sequences
        $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);

        // Strip tags
        $text = strip_tags($content);

        // Set the desired character limit
        $characterLimit = 160;

        // Check if the text exceeds the character limit
        if (mb_strlen($text) > $characterLimit) {
            // Find the last occurrence of a space within the character limit
            $lastSpaceIndex = mb_strrpos(mb_substr($text, 0, $characterLimit), ' ');

            // Extract the substring up to the last space (including the space itself)
            $text = mb_substr($text, 0, $lastSpaceIndex + 1);
        }
        $text = str_replace("\n", ' ', $text);
        $text = str_replace("\r", ' ', $text);
        $text = str_replace("\t", ' ', $text);
        $text = str_replace('  ', ' ', $text);
        $text = strip_tags($text);

        return $text;
    }
}
