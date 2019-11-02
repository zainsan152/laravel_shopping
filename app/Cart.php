<?php
namespace App;

use tests\Mockery\Generator\ClassWithDebugInfo;
use Illuminate\Support\Arr;

class Cart
{
    private $contents;
    private $totalQty;
    private $totalPrice;

    public function __construct($oldCart)
    {
        if ($oldCart)
        {
            $this->contents = $oldCart->contents;
            $this->totalQty = $oldCart->totalQty;
            $this->totalPrice = $oldCart->totalPrice;
        }
    }
    public function addProduct($product , $qty)
    {
        $products = ['qty' => 0 , 'price' => $product->price , 'product' => $product];
        if ($this->contents)
        {
            if (array_key_exists($product->id , $this->contents))
            {
                $products = $this->contents[$product->id];
            }
        }
        $products['qty']+=$qty;
        $products['price'] = $product->price * $products['qty'];
        $this->contents[$product->id] = $products;
        $this ->totalQty+=$qty;
        $this->totalPrice += $product->price;
    }
    public function removeProduct($product)
    {
        if($this->contents){
            if(array_key_exists($product->id, $this->contents)){
                $rProduct = $this->contents[$product->id];
                $this->totalQty -= $rProduct['qty'];
                $this->totalPrice -= $rProduct['price'];
                Arr::forget($this->contents, $product->id);
            }
        }
    }
    public function updateProduct($product , $qty)
    {
        if($this->contents){
            if(array_key_exists($product->id, $this->contents))
            {
                $products = $this->contents[$product->id];
            }
        }
        $this ->totalQty -= $products['qty'];
        $this->totalPrice -= $products['price'];
        $products['qty'] = $qty;
        $products['price'] = $product->price * $qty;
        $this ->totalQty += $qty;
        $this->totalPrice += $products['price'];
        $this->contents[$product->id] = $products;
    }
    public function getContents()
    {
        return $this->contents;
    }
    public function getTotalQty()
    {
        return $this->totalQty;
    }
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }
   /* public function countProducts()
    {
        return count($this->contents);
    }*/
}
