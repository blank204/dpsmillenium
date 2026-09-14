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
