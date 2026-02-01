<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'code',
        'description',
        'image',
        'purchasePrice',
        'salePrice',
        'profitMargin',
        'quantity',
        'active'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }


    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function getProfitMargin(): Float
    {
        return $this->profitMargin;
    }
    public function getProfit()
    {
        $salePriceWithoutTax = $this->salePrice / 1.13;

        return $salePriceWithoutTax - $this->purchasePrice;
    }

}





