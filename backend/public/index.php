<?php
declare(strict_types=1);
require __DIR__ . "/../bootstrap.php";
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if (preg_match('~^/uploads/([a-f0-9]{32}\.(?:jpg|png|webp))$~', $path, $m)) {
    $file = __DIR__ . "/uploads/" . $m[1];
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
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store");
@mkdir(__DIR__ . "/../var/sessions", 0700, true);
session_save_path(__DIR__ . "/../var/sessions");
session_name("arrchit_admin");
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => getenv("APP_ENV") === "production",
    "httponly" => true,
    "samesite" => "Strict",
]);
ini_set("session.use_strict_mode", "1");
session_start();
if (
    isset($_SESSION["admin_id"]) &&
    time() - ($_SESSION["last_active"] ?? 0) > 28800
) {
    $_SESSION = [];
    session_regenerate_id(true);
}
if (isset($_SESSION["admin_id"])) {
    $_SESSION["last_active"] = time();
}
if (!isset($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}
$method = $_SERVER["REQUEST_METHOD"];
$moved = [];
try {
    if ($path === "/api/session" && $method === "GET") {
        $a = isset($_SESSION["admin_id"])
            ? query("SELECT id,name,email FROM admins WHERE id=?", [
                $_SESSION["admin_id"],
            ])->fetch()
            : null;
        respond(["admin" => $a ?: null, "csrf" => $_SESSION["csrf"]]);
    }
    if (!in_array($method, ["GET", "HEAD"], true)) {
        csrf();
    }
    if ($path === "/api/login" && $method === "POST") {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $email = strtolower(
            trim(is_string($data["email"] ?? null) ? $data["email"] : ""),
        );
        $pass = $data["password"] ?? "";
        if (
            strlen($email) > 190 ||
            !is_string($pass) ||
            (strlen($pass) > 72 || str_contains($pass, "\0"))
        ) {
            fail("Invalid email or password.", 401);
        }
        $now = time();
        foreach (
            [
                ["ip:" . ($_SERVER["REMOTE_ADDR"] ?? "local"), 30],
                ["account:" . $email, 5],
            ]
            as [$key, $max]
        ) {
            $key = hash("sha256", $key);
            $expiry = $now + 900;
            if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === "sqlite") {
                query(
                    "INSERT INTO login_limits(bucket,attempts,expires_at) VALUES (?,1,?) ON CONFLICT(bucket) DO UPDATE SET attempts=CASE WHEN expires_at<=? THEN 1 ELSE attempts+1 END, expires_at=CASE WHEN expires_at<=? THEN ? ELSE expires_at END",
                    [$key, $expiry, $now, $now, $expiry],
                );
            } else {
                query(
                    "INSERT INTO login_limits(bucket,attempts,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(expires_at<=?,1,attempts+1), expires_at=IF(expires_at<=?,?,expires_at)",
                    [$key, $expiry, $now, $now, $expiry],
                );
            }
            $limit = query("SELECT * FROM login_limits WHERE bucket=?", [
                $key,
            ])->fetch();
            if ((int) $limit["attempts"] > $max) {
                header("Retry-After: " . max(1, $limit["expires_at"] - $now));
                fail(
                    "Too many sign-in attempts. Try again in 15 minutes.",
                    429,
                );
            }
        }
        $a = query("SELECT * FROM admins WHERE email=?", [$email])->fetch();
        // Always perform password verification, including for unknown accounts.
        $hash = $a
            ? $a["password_hash"]
            : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($pass, $hash) || !$a) {
            fail("Invalid email or password.", 401);
        }
        query("DELETE FROM login_limits WHERE bucket=?", [
            hash("sha256", "account:" . $email),
        ]);
        query("DELETE FROM login_limits WHERE expires_at<?", [$now]);
        session_regenerate_id(true);
        $_SESSION["admin_id"] = $a["id"];
        $_SESSION["last_active"] = time();
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        respond([
            "admin" => [
                "id" => $a["id"],
                "name" => $a["name"],
                "email" => $a["email"],
            ],
            "csrf" => $_SESSION["csrf"],
        ]);
    }
    admin();
    if ($path === "/api/logout" && $method === "POST") {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => getenv("APP_ENV") === "production",
            "httponly" => true,
            "samesite" => "Strict",
        ]);
        respond(["ok" => true]);
    }
    if ($path === "/api/options" && $method === "GET") {
        respond([
            "sizes" => query("SELECT * FROM sizes ORDER BY id")->fetchAll(),
            "colors" => query("SELECT * FROM colors ORDER BY id")->fetchAll(),
        ]);
    }
    if ($path === "/api/sizes" && $method === "POST") {
        $d = json_decode(file_get_contents("php://input"), true) ?: [];
        $label = text($d["label"] ?? null, 32, "size");
        query("INSERT INTO sizes(label) VALUES (?)", [$label]);
        respond(["id" => (int) db()->lastInsertId(), "label" => $label], 201);
    }
    if ($path === "/api/colors" && $method === "POST") {
        $d = json_decode(file_get_contents("php://input"), true) ?: [];
        $name = text($d["name"] ?? null, 60, "color");
        $hex = $d["hex"] ?? "";
        if (!is_string($hex) || !preg_match('/^#[a-f0-9]{6}$/i', $hex)) {
            fail("Choose a valid color.");
        }
        query("INSERT INTO colors(name,hex) VALUES (?,?)", [$name, $hex]);
        respond(
            [
                "id" => (int) db()->lastInsertId(),
                "name" => $name,
                "hex" => $hex,
            ],
            201,
        );
    }
    if ($path === "/api/products" && $method === "GET") {
        $rows = query("SELECT id FROM products ORDER BY id DESC")->fetchAll();
        respond([
            "products" => array_map(fn($r) => product((int) $r["id"]), $rows),
        ]);
    }
    if ($path === "/api/products" && $method === "POST") {
        $d = json_decode($_POST["product"] ?? "", true);
        if (!is_array($d)) {
            fail("Product data is required. Check upload size limits.");
        }
        $name = text($d["name"] ?? null, 160, "name");
        $pid = text($d["p_id"] ?? null, 64, "product ID");
        $price = (string) ($d["price"] ?? "");
        if (!preg_match('/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/', $price)) {
            fail("Enter a valid non-negative price with up to two decimals.");
        }
        $description = $d["description"] ?? "";
        if (!is_string($description) || mb_strlen($description) > 5000) {
            fail("Description is too long.");
        }
        $variants = $d["variants"] ?? [];
        if (
            !is_array($variants) ||
            count($variants) < 1 ||
            count($variants) > 500
        ) {
            fail("Add between 1 and 500 size/color combinations.");
        }
        $keys = [];
        $colorIds = [];
        foreach ($variants as $v) {
            if (!is_array($v)) {
                fail("Invalid variant.");
            }
            foreach (["size_id", "color_id", "quantity"] as $k) {
                if (filter_var($v[$k] ?? null, FILTER_VALIDATE_INT) === false) {
                    fail("Invalid variant value.");
                }
            }
            if ($v["quantity"] < 0 || $v["quantity"] > 1000000) {
                fail("Quantity must be 0–1,000,000.");
            }
            if (
                !query("SELECT id FROM sizes WHERE id=?", [
                    $v["size_id"],
                ])->fetch() ||
                !query("SELECT id FROM colors WHERE id=?", [
                    $v["color_id"],
                ])->fetch()
            ) {
                fail("Unknown size or color.");
            }
            $key = $v["size_id"] . ":" . $v["color_id"];
            if (isset($keys[$key])) {
                fail("Each size/color combination must be unique.");
            }
            $keys[$key] = true;
            $colorIds[] = (int) $v["color_id"];
        }
        $id = (int) ($d["id"] ?? 0);
        $old = $id ? product($id) : null;
        $dupe = query("SELECT id FROM products WHERE p_id=?", [$pid])->fetch();
        if ($dupe && (int) $dupe["id"] !== $id) {
            fail(
                "This product ID already exists. Edit that product to add another color or size.",
                409,
            );
        }
        $order = $d["photos"] ?? [];
        if (!is_array($order) || count($order) > 12) {
            fail("Use at most 12 photos.");
        }
        $photos = [];
        $seen = [];
        $uploadCount = count($_FILES["photos"]["name"] ?? []);
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
                    ($_FILES["photos"]["error"][$i] ?? UPLOAD_ERR_NO_FILE) !==
                        UPLOAD_ERR_OK
                ) {
                    fail("Photo upload failed. Each photo must be under 5 MB.");
                }
                $tmp = $_FILES["photos"]["tmp_name"][$i];
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
        @mkdir(__DIR__ . "/uploads", 0755, true);
        db()->beginTransaction();
        if ($id) {
            query(
                "UPDATE products SET name=?,p_id=?,price=?,description=? WHERE id=?",
                [$name, $pid, $price, $description, $id],
            );
        } else {
            query(
                "INSERT INTO products(name,p_id,price,description) VALUES (?,?,?,?)",
                [$name, $pid, $price, $description],
            );
            $id = (int) db()->lastInsertId();
        }
        query("DELETE FROM variants WHERE product_id=?", [$id]);
        foreach ($variants as $v) {
            query(
                "INSERT INTO variants(product_id,color_id,size_id,quantity) VALUES (?,?,?,?)",
                [$id, $v["color_id"], $v["size_id"], $v["quantity"]],
            );
        }
        query("DELETE FROM photos WHERE product_id=?", [$id]);
        foreach ($photos as $position => $p) {
            if (isset($p["tmp"])) {
                $dest = __DIR__ . $p["path"];
                if (!move_uploaded_file($p["tmp"], $dest)) {
                    throw new RuntimeException("Could not store upload.");
                }
                $moved[] = $dest;
            }
            query(
                "INSERT INTO photos(product_id,color_id,path,position) VALUES (?,?,?,?)",
                [$id, $p["color_id"], $p["path"], $position],
            );
        }
        db()->commit();
        $moved = [];
        foreach ($old["photos"] ?? [] as $op) {
            if (!in_array($op["path"], array_column($photos, "path"), true)) {
                @unlink(__DIR__ . $op["path"]);
            }
        }
        respond(["product" => product($id)], $old ? 200 : 201);
    }
    if (
        preg_match('~^/api/products/(\d+)$~', $path, $m) &&
        $method === "DELETE"
    ) {
        $p = product((int) $m[1]);
        query("DELETE FROM products WHERE id=?", [(int) $m[1]]);
        foreach ($p["photos"] as $photo) {
            @unlink(__DIR__ . $photo["path"]);
        }
        respond(["ok" => true]);
    }
    fail("Endpoint not found.", 404);
} catch (Throwable $e) {
    try {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
    } catch (Throwable) {
    }
    foreach ($moved as $file) {
        @unlink($file);
    }
    if (
        $e instanceof PDOException &&
        (str_starts_with((string) $e->getCode(), "23") ||
            $e->getCode() === "23000")
    ) {
        fail("That value already exists or is in use.", 409);
    }
    error_log($e->getMessage());
    fail(
        "The server could not complete the request. Check the server log and database setup.",
        500,
    );
}
