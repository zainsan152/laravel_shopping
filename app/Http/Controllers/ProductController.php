<?php

namespace App\Http\Controllers;

use App\Category;
use App\Http\Requests\StoreProduct;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Cart;
use Illuminate\Support\Facades\Session;



class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $products = Product::paginate(5);
        return view('admin.products.index' , compact('products'));
    }

    public function trash()
    {
        $products = Product::with('categories')->onlyTrashed()->paginate(3);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $categories = Category::all();
        return view('admin.products.create' ,compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProduct $request)
    {
        //
        $path ='images/no-image-icon-13.png';
        if ($request->has('thumbnail'))
        {
            $extension =".".$request->thumbnail->getClientOriginalExtension();
            $name =basename($request->thumbnail->getClientOriginalName() , $extension).time();
            $name =$name.$extension;
            $path = $request->thumbnail->store('product');


        }
            $product = Product::create([

            'title'=>$request->title,
            'description'=>$request->description,
            'thumbnail'=>$path,
            'status'=>$request->status,
            'options' => isset($request->extras) ? json_encode($request->extras) : null,
            'featured'=>($request->featured)?$request->featured:0,
            'price'=>$request->price,
            'discount'=>$request->discount?$request->discount:0,
            'discount_price'=>($request->discount_price)?$request->discount_price:0,
        ]);
        if ($product )
        {
            $product->categories()->attach($request->category_id);
            return back()->with('message' , 'Product Added');
        }else
        {
            return back()->with('message' , 'Error Product Added');
        }

        //echo $name;
        //dd($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
        $categories = Category::all();
        $products =Product::all();
        return view('products.all' , compact('categories' , 'products'));
    }
    public function single(Product $product)
    {
        return view('products.single' , compact('product'));
    }

    public function addToCart(Product $product ,Request $request)
    {
        //dd(Session::get('cart'));
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $qty =$request->qty ? $request->qty : 1;
        $cart =new Cart($oldCart);
        $cart->addProduct($product , $qty);
        Session::put('cart' , $cart);
        return back()->with('message' , "$product->title has been successfully added to cart");
    }

    public function cart()
    {

        if(!Session::has('cart'))
        {
            return view('products.cart');
        }
        $cart = Session::get('cart');
        return view('products.cart', compact('cart'));
    }
    public function removeProduct(Product $product)
    {
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->removeProduct($product);
        Session::put('cart', $cart);
        return back()->with('message', "Product $product->title has been successfully removed From the Cart");
    }
    public function updateProduct(Product $product, Request $request)
    {
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->updateProduct($product, $request->qty);
        Session::put('cart', $cart);
        return back()->with('message', "Product $product->title has been successfully updates in the Cart");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
        $categories =Category::all();
        return view('admin.products.create' , compact('product' , 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
        $path = 'images/no-thumbnail.jpeg';
        if($request->has('thumbnail')){
            $extension = ".".$request->thumbnail->getClientOriginalExtension();
            $name = basename($request->thumbnail->getClientOriginalName(), $extension).time();
            $name = $name.$extension;
            $path = $request->thumbnail->storeAs('images', $name, 'public');
        }
        $product->title =$request->title;
        //$product->slug = $request->slug;
        $product->description = $request->description;
        $product->status = $request->status;
        $product->featured = ($request->featured) ? $request->featured : 0;
        $product->price = $request->price;
        $product->discount = $request->discount ? $request->discount : 0;
        $product->discount_price = ($request->discount_price) ? $request->discount_price : 0;
        $product->categories()->detach();

        if($product->save()){
            $product->categories()->attach($request->category_id);
            return back()->with('message', "Product Successfully Updated!");
        }else{
            return back()->with('message', "Error Updating Product");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function recoverProduct($id)
    {
        $product = Product::with('categories')->onlyTrashed()->findOrFail($id);
        if($product->restore())
            return back()->with('message','Product Successfully Restored!');
        else
            return back()->with('message','Error Restoring Product');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        if($product->categories()->detach() && $product->forceDelete()){
            Storage::delete($product->thumbnail);
            return back()->with('message','Product Successfully Deleted!');
        }else{
            return back()->with('message','Error Deleting Product');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function remove(Product $product)
    {
        if($product->delete()){
            return back()->with('message','Product Successfully Trashed!');
        }else{
            return back()->with('message','Error Deleting Product');
        }
    }
}
