<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Size;
use App\Models\Color;
final class OptionController
{
    public function index(): never
    {
        respond(["sizes" => Size::all(), "colors" => Color::all()]);
    }
    public function storeSize(): never
    {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        respond(Size::create(text($data["label"] ?? null, 32, "size")), 201);
    }
    public function storeColor(): never
    {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $name = text($data["name"] ?? null, 60, "color");
        $hex = $data["hex"] ?? "";
        if (!is_string($hex) || !preg_match('/^#[a-f0-9]{6}$/i', $hex)) {
            fail("Choose a valid color.");
        }
        respond(Color::create($name, $hex), 201);
    }
}
