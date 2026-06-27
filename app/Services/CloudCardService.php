<?php

namespace App\Services;

use App\Models\Cloud;
use Carbon\Carbon;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;

class CloudCardService
{
    private ImageManager $manager;

    private const WIDTH = 1080;

    private const HEIGHT = 1440;

    private const PHOTO_HEIGHT = 900;

    /** @var array<string, string> */
    private const COLORS = [
        'white' => '#FFFFFF',
        'brand' => '#7EC8E3',
        'text_dark' => '#3D4A5A',
        'text_mid' => '#8A9AA8',
        'text_muted' => '#B5C8D8',
        'border' => '#E8EDF2',
        'overlay_text' => '#FFFFFF',
        'overlay_sub' => 'rgba(255, 255, 255, 0.92)',
    ];

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * 生成云朵分享卡片，返回 storage 相对路径
     */
    public function generate(Cloud $cloud, bool $force = false): string
    {
        $relativePath = $this->cardRelativePath($cloud);

        if (! $force && Storage::disk('public')->exists($relativePath)) {
            return $relativePath;
        }

        $sourcePath = Storage::disk('public')->path($cloud->image_path);
        if (! is_file($sourcePath)) {
            throw new RuntimeException('云朵原图不存在');
        }

        $outputPath = Storage::disk('public')->path($relativePath);
        $this->ensureOutputDirectory($outputPath);

        $fontPath = $this->resolveFontPath();
        $width = (int) config('cloud.card_width', self::WIDTH);
        $height = (int) config('cloud.card_height', self::HEIGHT);

        $canvas = $this->manager->create($width, $height)->fill(self::COLORS['white']);

        // 二、云朵照片（全宽主角，1080×900）
        $hero = $this->prepareHeroPhoto($sourcePath, $width, self::PHOTO_HEIGHT);
        $canvas->place($hero, 'top-left', 0, 0);

        // 三、照片底部渐变信息条
        $gradient = $this->createBottomGradient($width, 120, 0.5);
        $canvas->place($gradient, 'top-left', 0, 780);

        $dateText = $this->formatCollectDateShort($cloud->collect_date);
        $this->drawText($canvas, $dateText, 40, 810, $fontPath, 32, self::COLORS['overlay_text']);

        $mood = $this->moodLabel((int) $cloud->mood);
        $location = filled($cloud->location_city) ? (string) $cloud->location_city : '未知地点';
        $infoText = $location.'  ·  '.$mood;
        $this->drawText($canvas, $infoText, 1040, 810, $fontPath, 28, self::COLORS['overlay_sub'], 'right');

        // 四、备注（大字居中）
        $contentY = 960;
        if (filled($cloud->note)) {
            $noteText = $this->truncateText((string) $cloud->note, 20);
            $this->drawText($canvas, '"', 80, 900, $fontPath, 64, 'rgba(126, 200, 227, 0.3)');
            $this->drawText($canvas, '「'.$noteText.'」', 540, $contentY, $fontPath, 48, self::COLORS['text_dark'], 'center');
            $contentY = 1040;
        }

        // 五、云类型标签（居中浮层）
        $cloudType = filled($cloud->cloud_type) ? (string) $cloud->cloud_type : '云朵';
        $tagWidth = max(240, mb_strlen($cloudType) * 34 + 80);
        $tagHeight = 52;
        $tagX = (int) (($width - $tagWidth) / 2);
        $tagY = filled($cloud->note) ? $contentY : 980;

        $tag = $this->createBorderedRoundedLayer($tagWidth, $tagHeight, 26, 255, 255, 255, 232, 237, 242);
        $canvas->place($tag, 'top-left', $tagX, $tagY);
        $this->drawText(
            $canvas,
            $cloudType,
            (int) ($width / 2),
            $tagY + (int) ($tagHeight / 2),
            $fontPath,
            28,
            self::COLORS['text_mid'],
            'center',
            'middle'
        );

        // 六、底部品牌落款
        $this->placeHorizontalLine($canvas, 200, 1320, 680, 232, 237, 242, 1.0);
        $this->drawText($canvas, '云屿集', 540, 1360, $fontPath, 32, self::COLORS['brand'], 'center');
        $this->drawText($canvas, '记录每日云朵的心情', 540, 1400, $fontPath, 20, self::COLORS['text_muted'], 'center');

        $canvas->toJpeg(92)->save($outputPath);

        return $relativePath;
    }

    /**
     * 宽度撑满，等比缩放后从顶部裁切/不足则白底填充
     */
    private function prepareHeroPhoto(string $sourcePath, int $width, int $height): ImageInterface
    {
        $photo = $this->manager->read($sourcePath);
        $photo->scale(width: $width);

        if ($photo->height() >= $height) {
            return $photo->crop($width, $height, 0, 0);
        }

        $canvas = $this->manager->create($width, $height)->fill(self::COLORS['white']);
        $canvas->place($photo, 'top-left', 0, 0);

        return $canvas;
    }

    /** 照片底部：透明 → 半透明黑 渐变 */
    private function createBottomGradient(int $width, int $height, float $maxOpacity): ImageInterface
    {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('无法创建渐变图层');
        }

        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        for ($y = 0; $y < $height; $y++) {
            $opacity = ($y / max(1, $height - 1)) * $maxOpacity;
            $color = imagecolorallocatealpha($image, 0, 0, 0, $this->alphaToGd($opacity));
            imageline($image, 0, $y, $width, $y, $color);
        }

        return $this->manager->read($this->encodePng($image));
    }

    private function createBorderedRoundedLayer(
        int $width,
        int $height,
        int $radius,
        int $fillR,
        int $fillG,
        int $fillB,
        int $borderR,
        int $borderG,
        int $borderB
    ): ImageInterface {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('无法创建标签图层');
        }

        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        $border = imagecolorallocate($image, $borderR, $borderG, $borderB);
        $fill = imagecolorallocate($image, $fillR, $fillG, $fillB);

        $this->fillRoundedRect($image, 0, 0, $width, $height, $radius, $border);
        $this->fillRoundedRect($image, 1, 1, $width - 2, $height - 2, max(1, $radius - 1), $fill);

        return $this->manager->read($this->encodePng($image));
    }

    private function placeHorizontalLine(
        ImageInterface $canvas,
        int $x,
        int $y,
        int $width,
        int $r,
        int $g,
        int $b,
        float $alpha
    ): void {
        $line = $this->createSolidLayer($width, 1, $r, $g, $b, $alpha);
        $canvas->place($line, 'top-left', $x, $y);
    }

    private function createSolidLayer(int $width, int $height, int $r, int $g, int $b, float $alpha): ImageInterface
    {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('无法创建图层');
        }

        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        $color = imagecolorallocatealpha($image, $r, $g, $b, $this->alphaToGd($alpha));
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        return $this->manager->read($this->encodePng($image));
    }

    private function fillRoundedRect(
        GdImage $image,
        int $x,
        int $y,
        int $width,
        int $height,
        int $radius,
        int $color
    ): void {
        $radius = max(1, min($radius, (int) min($width, $height) / 2));

        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
        imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
    }

    private function encodePng(GdImage $image): string
    {
        ob_start();
        imagepng($image);
        $binary = ob_get_clean() ?: '';
        imagedestroy($image);

        return $binary;
    }

    private function alphaToGd(float $opacity): int
    {
        $opacity = max(0.0, min(1.0, $opacity));

        return (int) round((1 - $opacity) * 127);
    }

    private function ensureOutputDirectory(string $absoluteFilePath): void
    {
        $publicRoot = storage_path('app/public');
        if (! is_dir($publicRoot)) {
            if (! @mkdir($publicRoot, 0755, true) && ! is_dir($publicRoot)) {
                throw new RuntimeException('storage/app/public 目录不存在且无法创建');
            }
        }

        $directory = dirname($absoluteFilePath);
        if (is_dir($directory)) {
            if (! is_writable($directory)) {
                throw new RuntimeException('卡片目录不可写，请检查 storage 权限');
            }

            return;
        }

        if (! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('无法创建卡片目录: '.$directory);
        }

        if (! is_writable($directory)) {
            throw new RuntimeException('卡片目录不可写，请执行 chmod -R 775 storage');
        }
    }

    public function cardRelativePath(Cloud $cloud): string
    {
        $version = (int) config('cloud.card_version', 1);
        $updated = $cloud->updated_at?->timestamp ?? time();

        return sprintf('clouds/cards/%d/%d_v%d_%d.jpg', $cloud->user_id, $cloud->id, $version, $updated);
    }

    private function moodLabel(int $mood): string
    {
        $labels = config('cloud.mood_labels', []);

        return $labels[$mood] ?? '平静';
    }

    private function formatCollectDateShort(Carbon|string $date): string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];

        return sprintf(
            '%04d.%02d.%02d · 周%s',
            $date->year,
            $date->month,
            $date->day,
            $weekdays[$date->dayOfWeek]
        );
    }

    private function truncateText(string $text, int $maxChars): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, $maxChars - 1).'…';
    }

    private function drawText(
        ImageInterface $canvas,
        string $text,
        int $x,
        int $y,
        string $fontPath,
        int $size,
        string $color,
        string $align = 'left',
        string $valign = 'top'
    ): void {
        if ($text === '') {
            return;
        }

        try {
            $canvas->text($text, $x, $y, function ($font) use ($fontPath, $size, $color, $align, $valign): void {
                $font->filename($fontPath);
                $font->size($size);
                $font->color($color);
                $font->align($align);
                $font->valign($valign);
            });
        } catch (\Throwable $e) {
            Log::warning('Cloud card text render failed', [
                'text' => $text,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveFontPath(): string
    {
        $candidates = array_filter(array_merge(
            [config('cloud.card_font')],
            config('cloud.card_font_candidates', []),
            $this->discoverProjectFonts()
        ));

        foreach ($candidates as $path) {
            $resolved = $this->resolveAccessibleFontPath($path);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw new RuntimeException(
            '未找到中文字体。请将任意 .otf / .ttf / .ttc 放到 resources/fonts/ 目录即可，文件名不限'
        );
    }

    /**
     * @return list<string>
     */
    private function discoverProjectFonts(): array
    {
        $dirs = array_unique([
            resource_path('fonts'),
            storage_path('app/fonts'),
        ]);

        $files = [];
        $extensions = ['ttf', 'otf', 'ttc'];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            foreach (scandir($dir) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, $extensions, true)) {
                    $files[] = $dir.DIRECTORY_SEPARATOR.$name;
                }
            }
        }

        sort($files);

        return $files;
    }

    private function resolveAccessibleFontPath(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $projectRoot = realpath(base_path());
        if ($projectRoot === false) {
            return null;
        }

        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            return null;
        }

        if (! str_starts_with($resolved, $projectRoot)) {
            return null;
        }

        return $resolved;
    }
}
