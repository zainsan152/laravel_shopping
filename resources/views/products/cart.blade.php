@extends('layouts.app')
@section('content')
    <h2>Shopping Cart Page</h2>
    @if(isset($cart) && $cart->getContents())
    <div class="card">
        <table class="table table-hover shopping-cart-wrap">
            <thead class="text-muted">
            <tr>
                <th scope="col">Product</th>
                <th scope="col" width="120">Quantity</th>
                <th scope="col" width="120">Price</th>
                <th scope="col" class="text-right" width="200">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($cart->getContents() as $id => $product)


            <tr>
                <td>
                    <figure class="media">
                        <div class="img-wrap"><img src="{{asset('uploads/'.$product['product']->thumbnail)}}" class="img-thumbnail img-sm"
                            width="100px"></div>
                        <figcaption class="media-body">
                            <h6 class="title text-truncate">{{$product['product']->title}} </h6>
                            <dl class="dlist-inline small">
                                <dt>Size: </dt>
                                <dd>XXL</dd>
                            </dl>
                            <dl class="dlist-inline small">
                                <dt>Color: </dt>
                                <dd>Orange color</dd>
                            </dl>
                        </figcaption>
                    </figure>
                </td>
                <td>
                    <form method="post"action="{{route('cart.update' ,$id)}}">
                        @csrf
                    <input type="number" name="qty" id="qty" class="form-control text-center" min="0" max="99" value="{{$product['qty']}}">
                    <input type="submit" name="update" value="Update" class="btn btn-block btn-outline-success btn-round">
                    </form>
                </td>
                <td>
                    <div class="price-wrap">
                        <var class="price">USD {{$product['price']}}</var>
                        <small class="text-muted">(USD {{$product['product']->price}} each)</small>
                    </div> <!-- price-wrap .// -->
                </td>
                <td class="text-right">
                    <form action="{{route('cart.remove' , $id)}}" method="POST">
                        @csrf
                    <input type="submit" class="btn btn-outline-danger" value="Remove">
                    </form>
                </td>
            </tr>

                @endforeach
            <tr>
                <th colspan="2">Total Quantity: </th>
                <td>{{$cart->getTotalQty()}}</td>
            </tr>
            <tr>
                <th colspan="2">Total Price: </th>
                <td>{{$cart->getTotalPrice()}}</td>
            </tr>



            </tbody>
            </table>
    </div>
        @else
        <p class="alert alert-danger" >No products in the cart<a href="{{route('products.all')}}"> Buy some products" </a></p>
    @endif
@endsection
