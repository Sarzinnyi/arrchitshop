<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Product;
use App\Services\ProductService;
final class ProductController
{
    public function index(): never
    {
        respond(["products" => Product::all()]);
    }
    public function store(): never
    {
        $data = json_decode($_POST["product"] ?? "", true);
        if (!is_array($data)) {
            fail("Product data is required. Check upload size limits.");
        }
        $result = new ProductService()->save($data, $_FILES["photos"] ?? []);
        respond(["product" => $result["product"]], $result["status"]);
    }
    public function destroy(int $id): never
    {
        new ProductService()->delete($id);
        respond(["ok" => true]);
    }
}
