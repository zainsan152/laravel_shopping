<?php

namespace App\Http\Controllers;

use App\Order;
use App\Customer;
use Illuminate\Http\Request;
use App\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\StoreOrder;
use DB;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use Stripe\Stripe;
use Stripe\Charge;



class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Session::has('cart') || empty(Session::get('cart')->getContents()))
        {
            return redirect('products')->with('message' , 'No products in the cart');
        }
        $cart = Session::get('cart');
        return view('products.checkout', compact('cart'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrder $request)
    {
        //
        $cart = [];
        $order = '';
        $checkout = '';
        $error = '';
        $success = '';

        Stripe::setApiKey('sk_test_p0xuhINNHRf6Birnl92Bb3s700RRJzOEbB');
        if (Session::has('cart'))
        {
            $cart =Session::get('cart');
            $charge = Charge::create([
            'amount' => $cart->getTotalPrice() * 100,
            'currency' => 'usd',
            'source' => $request->stripeToken,
            'receipt_email' => $request->email,
        ]);
        }
        //dd($charge);
        //dd($request->all());
        if (isset($charge)){
            if ($request->shipping_address) {
                $customer = [
                    "billing_firstName" => $request->billing_firstName,
                    "billing_lastName" => $request->billing_lastName,
                    'username' => $charge->id,
                    "email" => $request->email,
                    "billing_address1" => $request->billing_address1,
                    "billing_address2" => $request->billing_address2,
                    "billing_country" => $request->billing_country,
                    "billing_state" => $request->billing_state,
                    "billing_zip" => $request->billing_zip,
                ];
            }else
            {
                $customer = [
                    "billing_firstName" => $request->billing_firstName,
                    "billing_lastName" => $request->billing_lastName,
                    "username" => $charge->id,
                    "email" => $request->email,
                    "billing_address1" => $request->billing_address1,
                    "billing_address2" => $request->billing_address2,
                    "billing_country" => $request->billing_country,
                    "billing_state" => $request->billing_state,
                    "billing_zip" => $request->billing_zip,
                ];
            }
        }

        DB::beginTransaction();
        $checkout = Customer::create($customer);
        foreach ($cart->getContents() as $id => $product) {
            $products = [
                'user_id' => $checkout->id,
                'product_id' => $product['product']->id,
                'qty' => $product['qty'],
                'status' => 'Pending',
                'price' => $product['price'],
                'payment_id' => 0,
                ];
            $order = Order::create($products);
        }
        if ($checkout && $order)
        {
        DB::commit();
        $request->session()->forget('cart');
        //$request->session()->flush();
        return redirect('products')->with('message' , 'Your Order successfully processs');
            }
        else
        {
            DB::rollback();
            return redirect('checkout')->with('message' , 'Invalid Activity!');
        }
    }

    public function paypal(StoreOrder $request)
    {
        if (Session::has('cart'))
        {
            $cart =Session::get('cart');
            $apiContext = new ApiContext(
                new \PayPal\Auth\OAuthTokenCredential(
                    env('PAYPAL_CLIENT_ID'),
                    env('PAYPAL_SECRET_ID')
                )
            );
            // Create new payer and method
            $payer = new Payer();
            $payer->setPaymentMethod("paypal");

// Set redirect URLs
            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl(route('process.paypal'))
                ->setCancelUrl(route('cancel.paypal'));

// Set payment amount
            $amount = new Amount();
            $amount->setCurrency("USD")
                ->setTotal($cart->getTotalPrice());

// Set transaction object
            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setDescription("Payment description");

// Create the full payment object
            $payment = new Payment();
            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions(array($transaction));
            // Create payment with valid API context
            try {
                $payment->create($apiContext);

                // Get PayPal redirect URL and redirect the customer
                $approvalUrl = $payment->getApprovalLink();
                if ($request->shipping_address) {
                    $customer = [
                        "billing_firstName" => $request->billing_firstName,
                        "billing_lastName" => $request->billing_lastName,
                        //'username' => Auth::user()->name,
                        "email" => $request->email,
                        "billing_address1" => $request->billing_address1,
                        "billing_address2" => $request->billing_address2,
                        "billing_country" => $request->billing_country,
                        "billing_state" => $request->billing_state,
                        "billing_zip" => $request->billing_zip,
                    ];
                }else {
                    $customer = [
                        "billing_firstName" => $request->billing_firstName,
                        "billing_lastName" => $request->billing_lastName,
                       // "username" => Auth::user()->name,
                        "email" => $request->email,
                        "billing_address1" => $request->billing_address1,
                        "billing_address2" => $request->billing_address2,
                        "billing_country" => $request->billing_country,
                        "billing_state" => $request->billing_state,
                        "billing_zip" => $request->billing_zip,
                    ];
                }
                Session::put('customer', json_encode($customer));

                    return redirect($approvalUrl);

                // Redirect the customer to $approvalUrl
            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                echo $ex->getCode();
                echo $ex->getData();
                die($ex);
            } catch (Exception $ex) {
                die($ex);
            }
        }
        else
        {
            return redirect('checkout')->with('message' , 'Invalid Activity!');

        }
    }
    public function returnPaypal(Request $request)
    {
        $cart = Session::get('cart');

        // Get payment object by passing paymentId
        $apiContext = new ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'),
                env('PAYPAL_SECRET_ID')
            )
        );
        $paymentId = $request->paymentId;
        $payment = Payment::get($paymentId, $apiContext);
        $payerId = $request->PayerID;

// Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            // Execute payment
            $result = $payment->execute($execution, $apiContext);
            if (isset($result) and strtolower($result->state)=='approved')
            {
                DB::beginTransaction();
                foreach ($cart->getContents() as $id => $product) {
                    $products = [
                        'user_id' => Auth::user()->id,
                        'product_id' => $product['product']->id,
                        'qty' => $product['qty'],
                        'status' => 'Pending',
                        'price' => $product['price'],
                        'payment_id' => 0,
                    ];
                    $order = Order::create($products);
                }
                $customer =json_decode(Session::get('customer'));
                $checkout = Customer::create([
                    'billing_firstName' => $customer->billing_firstName,
                    'billing_lastName' => $customer->billing_lastName,
                    'username' => $result->id,
                    'email' => $customer->email,
                    'billing_address1' => $customer->billing_address1,
                    'billing_address2' => $customer->billing_address2,
                    'billing_country' => $customer->billing_country,
                    'billing_state' => $customer->billing_state,
                    'billing_zip' => $customer->billing_zip,
                ]);
                if ($order && $checkout)
                {
                    DB::commit();
                    $request->session()->forget('cart');
                    //$request->session()->flush();
                    return redirect('products')->with('message' , 'Your Order successfully processs');
                }
                else
                {
                    DB::rollback();
                    return redirect('checkout')->with('message' , 'Invalid Activity!');
                }
            }
            else
                {
                    return redirect('checkout')->with('message' , 'Invalid Activity!');
                }
           // dd($result);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }

    }

    public function cancelPaypal()
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
