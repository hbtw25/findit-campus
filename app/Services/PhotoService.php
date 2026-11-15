<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

// Menyimpan foto laporan: diperkecil (maks 1280 px) + thumbnail persegi 320 px.
// Gambar di-encode ulang dengan GD sehingga metadata EXIF/GPS ikut terbuang.
class PhotoService
{
    public const MAX_SIDE = 1280;

    public const THUMB_SIDE = 320;

    /**
     * @return array{photo: string, photo_thumb: string}
     */
    public function store(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $source = @imagecreatefromstring((string) file_get_contents($path));

        if (! $source) {
            throw new RuntimeException('Berkas bukan gambar yang valid.');
        }

        $name = now()->format('Ym').'/'.Str::random(24).'.jpg';

        $photo = $this->resize($source, self::MAX_SIDE);
        $thumb = $this->squareThumb($source, self::THUMB_SIDE);

        Storage::disk('public')->put('reports/'.$name, $this->toJpeg($photo, 82));
        Storage::disk('public')->put('reports/thumbs/'.$name, $this->toJpeg($thumb, 80));

        imagedestroy($source);
        imagedestroy($photo);
        imagedestroy($thumb);

        return [
            'photo' => 'reports/'.$name,
            'photo_thumb' => 'reports/thumbs/'.$name,
        ];
    }

    public function delete(?string $photo, ?string $thumb): void
    {
        Storage::disk('public')->delete(array_filter([$photo, $thumb]));
    }

    private function resize(\GdImage $src, int $maxSide): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $maxSide / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        $dst = $this->canvas($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    // Potong bagian tengah menjadi persegi, dipakai untuk marker foto di peta
    private function squareThumb(\GdImage $src, int $side): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $crop = min($w, $h);
        $x = (int) (($w - $crop) / 2);
        $y = (int) (($h - $crop) / 2);

        $dst = $this->canvas($side, $side);
        imagecopyresampled($dst, $src, 0, 0, $x, $y, $side, $side, $crop, $crop);

        return $dst;
    }

    // Kanvas putih agar PNG transparan tidak menjadi hitam saat disimpan sebagai JPG
    private function canvas(int $w, int $h): \GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));

        return $img;
    }

    private function toJpeg(\GdImage $img, int $quality): string
    {
        ob_start();
        imagejpeg($img, null, $quality);

        return (string) ob_get_clean();
    }
}
