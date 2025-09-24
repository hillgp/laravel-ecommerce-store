<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Services\CartService;
use LaravelEcommerce\Store\Services\OrderService;
use LaravelEcommerce\Store\Services\PaymentService;
use LaravelEcommerce\Store\Services\ShippingService;
use LaravelEcommerce\Store\Services\NotificationService;
use LaravelEcommerce\Store\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    protected CartService $cartService;
    protected OrderService $orderService;
    protected PaymentService $paymentService;
    protected ShippingService $shippingService;
    protected NotificationService $notificationService;
    protected CouponService $couponService;

    public function __construct(
        CartService $cartService,
        OrderService $orderService,
        PaymentService $paymentService,
        ShippingService $shippingService,
        NotificationService $notificationService,
        CouponService $couponService
    ) {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->shippingService = $shippingService;
        $this->notificationService = $notificationService;
        $this->couponService = $couponService;
    }

    /**
     * Display checkout page.
     */
    public function index(): View|RedirectResponse
    {
        if ($this->cartService->isEmpty()) {
            return redirect()->route('store.products.index')
                ->with('error', 'Seu carrinho está vazio');
        }

        $cartItems = $this->cartService->getItems();
        $cartSummary = $this->cartService->getSummary();
        $appliedCoupon = session('applied_coupon');
        $shippingMethods = $this->shippingService->getAvailableShippingMethods();
        $paymentMethods = $this->paymentService->getPaymentMethods();

        return view('store::checkout.index', compact(
            'cartItems',
            'cartSummary',
            'appliedCoupon',
            'shippingMethods',
            'paymentMethods'
        ));
    }

    /**
     * Process checkout.
     */
    public function process(Request $request): JsonResponse|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('store.cart.index')
                ->with('error', 'Carrinho vazio');
        }

        $validatedData = $request->validate([
            'billing_address' => 'required|array',
            'shipping_address' => 'nullable|array',
            'payment_method' => 'required|string',
            'shipping_method' => 'required|string',
            'notes' => 'nullable|string',
            'terms' => 'required|accepted',
        ]);

        try {
            // Calculate shipping cost
            $shippingCost = $this->shippingService->calculateShipping($cart, $validatedData['shipping_method']);
            $shippingCost = $shippingCost['cost'] ?? 0;

            // Calculate discount
            $appliedCoupon = session('applied_coupon');
            $discountAmount = $appliedCoupon['discount'] ?? 0;

            // Create order
            $orderData = [
                'shipping_amount' => $shippingCost,
                'discount_amount' => $discountAmount,
                'billing_address' => $validatedData['billing_address'],
                'shipping_address' => $validatedData['shipping_address'] ?: $validatedData['billing_address'],
                'payment_method' => $validatedData['payment_method'],
                'shipping_method' => $validatedData['shipping_method'],
                'notes' => $validatedData['notes'],
            ];

            $order = $this->orderService->createOrderFromCart($cart, $orderData);

            // Process payment
            $paymentData = [
                'gateway' => $validatedData['payment_method'],
                'amount' => $order->total,
                'currency' => $order->currency,
                'order_id' => $order->id,
            ];

            $paymentResult = $this->paymentService->processPayment($order, $paymentData);

            if ($paymentResult['success']) {
                // Send confirmation email
                $this->notificationService->sendOrderConfirmation($order);

                // Send admin notification
                $this->notificationService->sendNewOrderNotification($order);

                // Clear applied coupon
                session()->forget('applied_coupon');

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Pedido criado com sucesso!',
                        'order' => [
                            'number' => $order->order_number,
                            'total' => $order->total,
                            'status' => $order->status,
                        ],
                        'redirect' => route('store.checkout.success', $order->order_number),
                    ]);
                }

                return redirect()->route('store.checkout.success', $order->order_number)
                    ->with('success', 'Pedido criado com sucesso!');
            } else {
                // Payment failed, cancel order
                $this->orderService->cancelOrder($order, 'Falha no pagamento');

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message'] ?? 'Falha no pagamento',
                    ], 400);
                }

                return redirect()->back()
                    ->with('error', $paymentResult['message'] ?? 'Falha no pagamento')
                    ->withInput();
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao processar pedido: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao processar pedido: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display checkout success page.
     */
    public function success(string $orderNumber): View|RedirectResponse
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);

        if (!$order) {
            return redirect()->route('store.products.index')
                ->with('error', 'Pedido não encontrado');
        }

        return view('store::checkout.success', compact('order'));
    }

    /**
     * Calculate shipping cost.
     */
    public function calculateShipping(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart();
        $shippingMethod = $request->get('method');
        $address = $request->get('address', []);

        try {
            $shippingCost = $this->shippingService->calculateShipping($cart, $shippingMethod);

            return response()->json([
                'success' => true,
                'shipping' => $shippingCost,
                'cart' => $this->cartService->getSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular frete',
            ], 500);
        }
    }

    /**
     * Validate checkout data.
     */
    public function validateCheckout(Request $request): JsonResponse
    {
        $errors = [];

        // Validate billing address
        if (empty($request->get('billing_address'))) {
            $errors['billing_address'] = 'Endereço de cobrança é obrigatório';
        }

        // Validate shipping address if different
        if ($request->get('different_shipping_address') && empty($request->get('shipping_address'))) {
            $errors['shipping_address'] = 'Endereço de entrega é obrigatório';
        }

        // Validate payment method
        if (empty($request->get('payment_method'))) {
            $errors['payment_method'] = 'Método de pagamento é obrigatório';
        }

        // Validate shipping method
        if (empty($request->get('shipping_method'))) {
            $errors['shipping_method'] = 'Método de entrega é obrigatório';
        }

        // Validate terms acceptance
        if (!$request->get('terms')) {
            $errors['terms'] = 'Você deve aceitar os termos e condições';
        }

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors,
        ]);
    }

    /**
     * Get payment methods.
     */
    public function paymentMethods(): JsonResponse
    {
        $paymentMethods = $this->paymentService->getPaymentMethods();
        $gateways = $this->paymentService->getAvailableGateways();

        return response()->json([
            'success' => true,
            'methods' => $paymentMethods,
            'gateways' => $gateways,
        ]);
    }

    /**
     * Get shipping methods.
     */
    public function shippingMethods(): JsonResponse
    {
        $shippingMethods = $this->shippingService->getAvailableShippingMethods();

        return response()->json([
            'success' => true,
            'methods' => $shippingMethods,
        ]);
    }

    /**
     * Apply coupon during checkout.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $couponCode = $request->get('coupon_code');
        $subtotal = $this->cartService->getSubtotal();

        if (empty($couponCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Código do cupom é obrigatório',
            ], 400);
        }

        try {
            $result = $this->couponService->applyCoupon($couponCode, $subtotal);

            if ($result['success']) {
                // Store coupon in session
                session(['applied_coupon' => [
                    'code' => $couponCode,
                    'discount' => $result['discount'],
                ]]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'cart' => $this->cartService->getSummary(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aplicar cupom',
            ], 500);
        }
    }

    /**
     * Get checkout summary.
     */
    public function summary(): JsonResponse
    {
        $cartSummary = $this->cartService->getSummary();
        $appliedCoupon = session('applied_coupon');

        return response()->json([
            'success' => true,
            'cart' => $cartSummary,
            'coupon' => $appliedCoupon,
        ]);
    }

    /**
     * Process guest checkout.
     */
    public function guestCheckout(Request $request): JsonResponse
    {
        // This would handle guest checkout process
        // For now, redirect to login or registration

        return response()->json([
            'success' => false,
            'message' => 'Checkout como convidado não está disponível. Faça login ou cadastre-se.',
            'redirect' => route('login'),
        ]);
    }

    /**
     * Save checkout data to session.
     */
    public function saveCheckoutData(Request $request): JsonResponse
    {
        $checkoutData = $request->only([
            'billing_address',
            'shipping_address',
            'payment_method',
            'shipping_method',
            'notes',
        ]);

        session(['checkout_data' => $checkoutData]);

        return response()->json([
            'success' => true,
            'message' => 'Dados salvos com sucesso',
        ]);
    }

    /**
     * Get saved checkout data.
     */
    public function getCheckoutData(): JsonResponse
    {
        $checkoutData = session('checkout_data', []);

        return response()->json([
            'success' => true,
            'data' => $checkoutData,
        ]);
    }
}