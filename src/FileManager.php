<?php

namespace BrainDump;

class FileManager
{
    /**
     * Copy non-image static assets from $sourceDir to $destDir.
     * Skips markdown files and image files.
     * Uses checksum to avoid unnecessary writes.
     */
    public static function copyStaticAssets($sourceDir, $destDir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        // Define image extensions to skip
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getFilename()) !== 'content.md') {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (in_array($ext, $imageExtensions)) {
                    // Skip image files since we process them separately.
                    continue;
                }
                $relativePath = str_replace(realpath($sourceDir), '', realpath($file->getPathname()));
                $destPath = rtrim($destDir, '/') . '/' . ltrim($relativePath, '/');
                $destFolder = dirname($destPath);
                if (!is_dir($destFolder)) {
                    mkdir($destFolder, 0755, true);
                }
                // Compare checksum before copying
                $newHash = md5_file($file->getPathname());
                $oldHash = file_exists($destPath) ? md5_file($destPath) : '';
                if ($newHash !== $oldHash) {
                    copy($file->getPathname(), $destPath);
                    echo "Copied static asset: {$destPath}\n";
                } else {
                    echo "Static asset unchanged: {$destPath}\n";
                }
            }
        }
    }

    /**
     * Process images found in the $sourceDir, converting them to webp and generating thumbnails.
     * The processed files are output to the corresponding path in $destDir.
     * Uses checksum to avoid unnecessary writes.
     */
    public static function processImages($sourceDir, $config, $destDir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions)) {
                    continue;
                }
                $srcPath = $file->getPathname();
                // Determine relative directory from the source
                $relativeDir = str_replace(realpath($sourceDir), '', realpath(dirname($srcPath)));
                $relativeDir = ltrim($relativeDir, '/');
                $outputDir = rtrim($destDir, '/') . '/' . $relativeDir;
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }
                // Create webp filename (e.g., image.jpg becomes image.webp)
                $webpFilename = pathinfo($file->getFilename(), PATHINFO_FILENAME) . '.webp';
                $webpPath = $outputDir . '/' . $webpFilename;
                self::writeWebpWithChecksum($srcPath, $webpPath, $config['images']);

                // Generate thumbnail in a subfolder "thumbs"
                $thumbDir = $outputDir . '/thumbs';
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }
                $thumbPath = $thumbDir . '/' . $webpFilename;
                self::writeThumbnailWithChecksum($srcPath, $thumbPath, $config['images']);
            }
        }
    }

    /**
     * Helper function: Convert an image to webp format with resizing.
     * Uses output buffering to capture the generated image data, computes its checksum,
     * and writes it to $destPath only if changed.
     */
    private static function writeWebpWithChecksum($srcPath, $destPath, $imgSettings)
    {
        list($width, $height) = getimagesize($srcPath);
        $ratio = min($imgSettings['max_width'] / $width, $imgSettings['max_height'] / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        $src = imagecreatefromstring(file_get_contents($srcPath));
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        // Capture output of webp conversion
        ob_start();
        imagewebp($dst, null, $imgSettings['quality']);
        $data = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);
        $newHash = md5($data);
        $oldHash = file_exists($destPath) ? md5_file($destPath) : '';
        if ($newHash !== $oldHash) {
            file_put_contents($destPath, $data);
            echo "Processed image: {$destPath}\n";
        } else {
            echo "Image unchanged: {$destPath}\n";
        }
    }

    /**
     * Helper function: Generate a thumbnail (webp format) for the source image.
     * Uses output buffering to capture the thumbnail data, computes its checksum,
     * and writes it to $destPath only if changed.
     */
    private static function writeThumbnailWithChecksum($srcPath, $destPath, $imgSettings)
    {
        list($width, $height) = getimagesize($srcPath);
        $ratio = min($imgSettings['thumbnail_width'] / $width, $imgSettings['thumbnail_height'] / $height);
        $thumbWidth = (int)($width * $ratio);
        $thumbHeight = (int)($height * $ratio);
        $src = imagecreatefromstring(file_get_contents($srcPath));
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        ob_start();
        imagewebp($thumb, null, $imgSettings['quality']);
        $data = ob_get_clean();
        imagedestroy($src);
        imagedestroy($thumb);
        $newHash = md5($data);
        $oldHash = file_exists($destPath) ? md5_file($destPath) : '';
        if ($newHash !== $oldHash) {
            file_put_contents($destPath, $data);
            echo "Generated thumbnail: {$destPath}\n";
        } else {
            echo "Thumbnail unchanged: {$destPath}\n";
        }
    }
}
