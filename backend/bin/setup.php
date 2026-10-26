<?php
declare(strict_types=1);
require __DIR__ . "/../bootstrap.php";
use App\Models\Admin;
use App\Models\Size;
use App\Models\Color;
umask(0077);
if (PHP_SAPI !== "cli") {
    exit(1);
}
@mkdir(__DIR__ . "/../var", 0700, true);
$driver = db()->getAttribute(PDO::ATTR_DRIVER_NAME);
if (!in_array($driver, ["sqlite", "mysql"], true)) {
    throw new RuntimeException("Use SQLite or MySQL.");
}
db()->exec(file_get_contents(__DIR__ . "/../database/schema.$driver.sql"));
foreach (["S", "M", "L", "28", "29", "30"] as $label) {
    if (!Size::byLabel($label)) {
        Size::create($label);
    }
}
foreach (
    [
        ["Black", "#202020"],
        ["White", "#f6f5f0"],
        ["Navy", "#27364d"],
        ["Olive", "#797e5c"],
        ["Sand", "#d5c3a4"],
    ]
    as $c
) {
    if (!Color::byName($c[0])) {
        Color::create($c[0], $c[1]);
    }
}
echo "Database schema and default options are ready.\n";
if (in_array("--create-admin", $argv, true)) {
    $email = strtolower(trim(getenv("ADMIN_EMAIL") ?: ""));
    $name = getenv("ADMIN_NAME") ?: "Shop owner";
    $password = getenv("ADMIN_PASSWORD") ?: "";
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        (strlen($password) < 12 ||
            strlen($password) > 72 ||
            str_contains($password, "\0"))
    ) {
        throw new RuntimeException(
            "Set ADMIN_EMAIL and ADMIN_PASSWORD (12–72 bytes).",
        );
    }
    if (Admin::byEmail($email)) {
        echo "Admin already exists; password left unchanged.\n";
        exit();
    }
    Admin::create($email, $name, password_hash($password, PASSWORD_DEFAULT));
    echo "Admin created.\n";
}
