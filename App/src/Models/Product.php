<?php
namespace App\Models;

class Product extends Model
{
    protected string $table = 'products';
    protected array $fillable = ['name', 'type', 'rate', 'tenor_months', 'fee'];
    protected array $casts = [
        'rate' => 'float',
        'tenor_months' => 'int',
        'fee' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Get loan products
     */
    public function getLoanProducts(): array
    {
        return $this->findWhere(['type' => 'loan'], ['name' => 'ASC']);
    }

    /**
     * Get savings products
     */
    public function getSavingsProducts(): array
    {
        return $this->findWhere(['type' => 'savings'], ['name' => 'ASC']);
    }

    /**
     * Calculate loan fee
     */
    public function calculateFee(int $productId, float $amount): float
    {
        $product = $this->find($productId);
        if (!$product) {
            return 0.0;
        }

        // For now, return fixed fee. Could be enhanced with percentage-based fees
        return (float)$product['fee'];
    }

    /**
     * Calculate monthly interest
     */
    public function calculateMonthlyInterest(int $productId, float $amount): float
    {
        $product = $this->find($productId);
        if (!$product) {
            return 0.0;
        }

        $monthlyRate = $product['rate'] / 100 / 12;
        return $amount * $monthlyRate;
    }
}
