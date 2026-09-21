<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

final class Fixtures
{
    /** JPEG (GD-made) with an injected EXIF block: Orientation tag and a GPS IFD pointer. */
    public static function jpegWithExif(int $width, int $height, int $orientation = 1): string
    {
        $img = imagecreatetruecolor($width, $height);
        imagefilledrectangle($img, 0, 0, (int) ($width / 2), $height, imagecolorallocate($img, 200, 30, 30));
        ob_start();
        imagejpeg($img, null, 90);
        $jpeg = ob_get_clean();

        // TIFF little-endian header + IFD0 with 2 entries: Orientation (0x0112) and GPS IFD pointer (0x8825) -> empty GPS IFD.
        $ifd0Offset = 8;
        $gpsOffset = $ifd0Offset + 2 + 2 * 12 + 4;
        $tiff = 'II'.pack('v', 42).pack('V', $ifd0Offset)
            .pack('v', 2)
            .pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', $orientation).pack('v', 0)
            .pack('v', 0x8825).pack('v', 4).pack('V', 1).pack('V', $gpsOffset)
            .pack('V', 0)
            .pack('v', 0).pack('V', 0);
        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
        $withExif = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        $path = tempnam(sys_get_temp_dir(), 'fx').'.jpg';
        file_put_contents($path, $withExif);

        return $path;
    }

    public static function uploaded(string $path, string $name, ?string $mime = null): UploadedFile
    {
        return new UploadedFile($path, $name, $mime, null, true);
    }

    /**
     * A tiny PNG whose IHDR declares $width × $height. With $validData = false the
     * IDAT stream is garbage, so the header reads fine but decoding fails.
     */
    public static function pngDeclaring(int $width, int $height, bool $validData = false): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $idat = $validData ? gzcompress(str_repeat("\0".str_repeat("\0\0\0", $width), $height)) : 'this is not zlib data at all';
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', $idat)
            .$chunk('IEND', '');

        $path = tempnam(sys_get_temp_dir(), 'fx').'.png';
        file_put_contents($path, $png);

        return $path;
    }

    /** Starts like a JPEG (so finfo says image/jpeg) but the rest is garbage. */
    public static function corruptJpeg(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fx').'.jpg';
        file_put_contents($path, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00".random_bytes(2048));

        return $path;
    }

    public static function pdf(int $extraBytes = 0): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fx').'.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n".str_repeat(' ', $extraBytes));

        return $path;
    }

    public static function mp4(int $extraBytes = 0): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fx').'.mp4';
        $ftyp = pack('N', 24).'ftypisom'.pack('N', 0x200).'isomiso2';
        file_put_contents($path, $ftyp.pack('N', 8).'free'.str_repeat("\0", $extraBytes));

        return $path;
    }
}
