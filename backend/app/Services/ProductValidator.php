<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Size;
use App\Models\Color;
final class ProductValidator
{
    public static function validate(array $d): array
    {
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
                !Size::exists((int) $v["size_id"]) ||
                !Color::exists((int) $v["color_id"])
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
        return compact(
            "name",
            "pid",
            "price",
            "description",
            "variants",
            "colorIds",
        );
    }
}
