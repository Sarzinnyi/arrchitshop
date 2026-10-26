<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Product;
final class ProductService
{
    public function save(array $data, array $files): array
    {
        $validated = ProductValidator::validate($data);
        $id = (int) ($data["id"] ?? 0);
        $old = $id ? Product::find($id) : null;
        $duplicate = Product::byCode($validated["pid"]);
        if ($duplicate && (int) $duplicate["id"] !== $id) {
            fail(
                "This product ID already exists. Edit that product to add another color or size.",
                409,
            );
        }
        $photos = PhotoService::prepare(
            $data,
            $old,
            $validated["colorIds"],
            $files,
        );
        $moved = [];
        try {
            db()->beginTransaction();
            $id = Product::save(
                $id,
                $validated["name"],
                $validated["pid"],
                $validated["price"],
                $validated["description"],
            );
            Product::replaceVariants($id, $validated["variants"]);
            PhotoService::store($photos, $moved);
            Product::replacePhotos($id, $photos);
            db()->commit();
        } catch (\Throwable $error) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            foreach ($moved as $file) {
                @unlink($file);
            }
            throw $error;
        }
        PhotoService::removeReplaced($old["photos"] ?? [], $photos);
        return ["product" => Product::find($id), "status" => $old ? 200 : 201];
    }
    public function delete(int $id): void
    {
        $product = Product::find($id);
        Product::delete($id);
        PhotoService::remove($product["photos"]);
    }
}
