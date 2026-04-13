<?php

namespace App\Apartment\Ingest;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;

class TextExtractor
{
    public const MIN_USABLE_CHARS = 50;

    public function __construct(private readonly Parser $parser = new Parser) {}

    /**
     * @return array{text: string, source: 'pdfparser'|'ocr'}
     */
    public function extract(string $absolutePath): array
    {
        $text = $this->tryPdfParser($absolutePath);

        if (mb_strlen(trim($text)) >= self::MIN_USABLE_CHARS) {
            return ['text' => $text, 'source' => 'pdfparser'];
        }

        Log::channel('apartment')->info('ingest.ocr_used', ['path' => $absolutePath, 'pdfparser_chars' => mb_strlen($text)]);

        return ['text' => $this->ocr($absolutePath), 'source' => 'ocr'];
    }

    private function tryPdfParser(string $absolutePath): string
    {
        try {
            return $this->parser->parseFile($absolutePath)->getText();
        } catch (\Throwable $e) {
            Log::channel('apartment')->warning('ingest.pdfparser_failed', ['path' => $absolutePath, 'error' => $e->getMessage()]);

            return '';
        }
    }

    private function ocr(string $absolutePath): string
    {
        $tmp = sys_get_temp_dir().'/apartment-ocr-'.bin2hex(random_bytes(6));
        @mkdir($tmp);

        try {
            $rasterize = new Process(['pdftoppm', '-r', '200', '-png', $absolutePath, $tmp.'/page']);
            $rasterize->setTimeout(180);
            $rasterize->mustRun();

            $images = glob($tmp.'/page-*.png') ?: [];
            sort($images);

            $out = '';
            foreach ($images as $image) {
                $tess = new Process(['tesseract', $image, 'stdout', '-l', 'hun+eng']);
                $tess->setTimeout(120);
                $tess->mustRun();
                $out .= $tess->getOutput()."\n";
            }

            return $out;
        } finally {
            foreach (glob($tmp.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmp);
        }
    }
}
