<?php
namespace app\components\helpers;

use Exception;

class ImageConverter
{
    const OUT_WIDTH = 640;
    const OUT_HEIGHT = 360;

    const JPEG_QUALITY = 90;
    const WEBP_QUALITY = 90;

    public static function convert($binary, $outPathJpeg, $outPathWebp)
    {
        if (!$tmpName = self::convertImpl($binary)) {
            return false;
        }
        if (!self::copyJpeg($tmpName->get(), $outPathJpeg)) {
            @unlink($outPathJpeg);
            @unlink($outPathWebp);
            return false;
        }
        if (!self::copyWebp($tmpName->get(), $outPathWebp)) {
            @unlink($outPathJpeg);
            @unlink($outPathWebp);
            return false;
        }
        return true;
    }

    protected static function convertImpl($binary)
    {
        try {
            $in = new Resource(@imagecreatefromstring($binary), 'imagedestroy');
            if (!$in->get()) {
                throw new Exception();
            }
            $out = new Resource(imagecreatetruecolor(self::OUT_WIDTH, self::OUT_HEIGHT), 'imagedestroy');
            if (!$out->get()) {
                throw new Exception();
            }
            $inW = imagesx($in->get());
            $inH = imagesy($in->get());
            if ($inW < 100 || $inH < 100) {
                throw new Exception();
            }
            $scale = min(self::OUT_WIDTH / $inW, self::OUT_HEIGHT / $inH);
            $cpW = (int)round($inW * $scale);
            $cpH = (int)round($inH * $scale);
            $cpX = (int)round(self::OUT_WIDTH / 2 - $cpW / 2);
            $cpY = (int)round(self::OUT_HEIGHT / 2 - $cpH / 2);
            imagealphablending($out->get(), false);
            imagefill($out->get(), 0, 0, 0xffffff);
            imagealphablending($out->get(), true);
            imagecopyresampled(
                $out->get(),
                $in->get(),
                $cpX,
                $cpY,
                0,
                0,
                $cpW,
                $cpH,
                $inW,
                $inH
            );
            $tmpName = new Resource(tempnam(sys_get_temp_dir(), 'statink-'), 'unlink');
            imagepng($out->get(), $tmpName->get(), 9, PNG_ALL_FILTERS);
            return $tmpName;
        } catch (Exception $e) {
        }
        return false;
    }

    protected static function copyJpeg($inPath, $outPath)
    {
        self::mkdir(dirname($outPath));
        $cmdlines = [
            sprintf(
                '/usr/bin/env %s %s -quality %d %s',
                escapeshellarg('convert'),
                escapeshellarg($inPath),
                self::JPEG_QUALITY,
                escapeshellarg($outPath)
            ),
            sprintf(
                '/usr/bin/env %s --quiet --strip-all %s',
                escapeshellarg('jpegoptim'),
                escapeshellarg($outPath)
            ),
        ];
        foreach ($cmdlines as $cmdline) {
            $lines = [];
            $status = -1;
            @exec($cmdline, $lines, $status);
            if ($status != 0) {
                @unlink($outPath);
                return false;
            }
        }
        return true;
    }

    protected static function copyWebp($inPath, $outPath)
    {
        self::mkdir(dirname($outPath));
        $cmdlines = [
            sprintf(
                '/usr/bin/env %s -q %d -m 6 -quiet %s -o %s',
                escapeshellarg('cwebp'),
                self::WEBP_QUALITY,
                escapeshellarg($inPath),
                escapeshellarg($outPath)
            ),
        ];
        foreach ($cmdlines as $cmdline) {
            $lines = [];
            $status = -1;
            @exec($cmdline, $lines, $status);
            if ($status != 0) {
                @unlink($outPath);
                return false;
            }
        }
        return true;
    }

    private static function mkdir($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
