<?php
declare(strict_types=1);
namespace App\Services;

final class PhotoService
{
    public static function serve(string $path): void
    {
        if (
            preg_match(
                '~^/uploads/([a-f0-9]{32}\.(?:jpg|png|webp))$~',
                $path,
                $m,
            )
        ) {
            $file = dirname(__DIR__, 2) . "/public/uploads/" . $m[1];
            if (!is_file($file)) {
                http_response_code(404);
                exit();
            }
            header(
                "Content-Type: " .
                    [
                        "jpg" => "image/jpeg",
                        "png" => "image/png",
                        "webp" => "image/webp",
                    ][pathinfo($file, PATHINFO_EXTENSION)],
            );
            header("X-Content-Type-Options: nosniff");
            header("Cache-Control: public, max-age=86400");
            readfile($file);
            exit();
        }
    }
    public static function prepare(
        array $d,
        ?array $old,
        array $colorIds,
        array $files,
    ): array {
        $order = $d["photos"] ?? [];
        if (!is_array($order) || count($order) > 12) {
            fail("Use at most 12 photos.");
        }
        $photos = [];
        $seen = [];
        $uploadCount = count($files["name"] ?? []);
        foreach ($order as $entry) {
            $token = $entry["key"] ?? "";
            if (!is_string($token) || isset($seen[$token])) {
                fail("Invalid photo order.");
            }
            $seen[$token] = true;
            $color = $entry["color_id"] ?? null;
            if ($color !== null && !in_array((int) $color, $colorIds, true)) {
                fail("Photo color must match a product variant.");
            }
            if (str_starts_with($token, "old:")) {
                $photoId = (int) substr($token, 4);
                $found = null;
                foreach ($old["photos"] ?? [] as $op) {
                    if ((int) $op["id"] === $photoId) {
                        $found = $op;
                    }
                }
                if (!$found) {
                    fail("Unknown existing photo.");
                }
                $photos[] = ["path" => $found["path"], "color_id" => $color];
            } elseif (preg_match('/^new:(\d+)$/', $token, $match)) {
                $i = (int) $match[1];
                if (
                    $i >= $uploadCount ||
                    ($files["error"][$i] ?? UPLOAD_ERR_NO_FILE) !==
                        UPLOAD_ERR_OK
                ) {
                    fail("Photo upload failed. Each photo must be under 5 MB.");
                }
                $tmp = $files["tmp_name"][$i];
                if (filesize($tmp) > 5 * 1024 * 1024) {
                    fail("Each photo must be under 5 MB.");
                }
                $info = @getimagesize($tmp);
                $mime = $info["mime"] ?? "";
                $ext =
                    [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp",
                    ][$mime] ?? null;
                if (!$ext || $info[0] > 10000 || $info[1] > 10000) {
                    fail(
                        "Use JPEG, PNG or WebP images under 10,000 pixels per side.",
                    );
                }
                $photos[] = [
                    "tmp" => $tmp,
                    "path" =>
                        "/uploads/" . bin2hex(random_bytes(16)) . "." . $ext,
                    "color_id" => $color,
                ];
            } else {
                fail("Invalid photo.");
            }
        }
        if (!$photos) {
            fail("Add at least one product photo.");
        }
        return $photos;
    }
    public static function store(array $photos, array &$moved): void
    {
        @mkdir(dirname(__DIR__, 2) . "/public/uploads", 0755, true);
        foreach ($photos as $photo) {
            if (isset($photo["tmp"])) {
                $destination = dirname(__DIR__, 2) . "/public" . $photo["path"];
                if (!move_uploaded_file($photo["tmp"], $destination)) {
                    throw new \RuntimeException("Could not store upload.");
                }
                $moved[] = $destination;
            }
        }
    }
    public static function remove(array $photos): void
    {
        foreach ($photos as $photo) {
            @unlink(dirname(__DIR__, 2) . "/public" . $photo["path"]);
        }
    }
    public static function removeReplaced(array $old, array $photos): void
    {
        self::remove(
            array_filter(
                $old,
                fn($photo) => !in_array(
                    $photo["path"],
                    array_column($photos, "path"),
                    true,
                ),
            ),
        );
    }
}
