<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Services\CustomerService;
use LaravelEcommerce\Store\Services\OrderService;
use LaravelEcommerce\Store\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    protected CustomerService $customerService;
    protected OrderService $orderService;
    protected ReviewService $reviewService;

    public function __construct(
        CustomerService $customerService,
        OrderService $orderService,
        ReviewService $reviewService
    ) {
        $this->customerService = $customerService;
        $this->orderService = $orderService;
        $this->reviewService = $reviewService;
    }

    /**
     * Display customer registration form.
     */
    public function create(): View
    {
        return view('store::customers.create');
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'document' => 'nullable|string|max:20',
            'newsletter_subscription' => 'boolean',
        ]);

        try {
            $customer = $this->customerService->createCustomer($validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conta criada com sucesso!',
                    'customer' => $customer,
                ]);
            }

            return redirect()->route('store.login')
                ->with('success', 'Conta criada com sucesso! Faça login para continuar.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar conta: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao criar conta: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display customer login form.
     */
    public function login(): View
    {
        return view('store::customers.login');
    }

    /**
     * Authenticate customer.
     */
    public function authenticate(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = $this->customerService->authenticate($credentials['email'], $credentials['password']);

        if (!$customer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciais inválidas',
                ], 401);
            }

            return redirect()->back()
                ->with('error', 'Credenciais inválidas')
                ->withInput();
        }

        // Log the customer in
        Auth::guard('customer')->login($customer);

        // Merge guest cart with customer cart
        $this->customerService->mergeGuestCart();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'redirect' => route('store.dashboard'),
            ]);
        }

        return redirect()->intended(route('store.dashboard'))
            ->with('success', 'Login realizado com sucesso!');
    }

    /**
     * Logout customer.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::guard('customer')->logout();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso!',
            ]);
        }

        return redirect()->route('store.products.index')
            ->with('success', 'Logout realizado com sucesso!');
    }

    /**
     * Display customer dashboard.
     */
    public function dashboard(): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();
        $recentOrders = $this->orderService->getUserOrders($customer->id, [], 5)->items();
        $profileCompleteness = $this->customerService->getProfileCompleteness($customer);

        return view('store::customers.dashboard', compact('customer', 'recentOrders', 'profileCompleteness'));
    }

    /**
     * Display customer profile.
     */
    public function profile(): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();

        return view('store::customers.profile', compact('customer'));
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request): JsonResponse|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'document' => 'nullable|string|max:20',
        ]);

        try {
            $updatedCustomer = $this->customerService->updateCustomer($customer, $validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Perfil atualizado com sucesso!',
                    'customer' => $updatedCustomer,
                ]);
            }

            return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar perfil: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao atualizar perfil: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display customer orders.
     */
    public function orders(Request $request): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();
        $filters = $request->only(['status', 'date_from', 'date_to']);
        $orders = $this->orderService->getUserOrders($customer->id, $filters, 10);

        return view('store::customers.orders', compact('orders', 'filters'));
    }

    /**
     * Display customer order details.
     */
    public function order(string $orderNumber): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();
        $order = $this->orderService->getOrderByNumber($orderNumber);

        if (!$order || $order->user_id !== $customer->id) {
            return redirect()->route('store.customer.orders')
                ->with('error', 'Pedido não encontrado');
        }

        return view('store::customers.order', compact('order'));
    }

    /**
     * Display customer addresses.
     */
    public function addresses(): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();
        $addresses = $this->customerService->getCustomerAddresses($customer);

        return view('store::customers.addresses', compact('customer', 'addresses'));
    }

    /**
     * Display customer wishlist.
     */
    public function wishlist(): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();
        $wishlistItems = $this->customerService->getCustomerWishlist($customer);

        return view('store::customers.wishlist', compact('wishlistItems'));
    }

    /**
     * Add product to wishlist.
     */
    public function addToWishlist(Request $request, int $productId): JsonResponse|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faça login para adicionar à lista de desejos',
                ], 401);
            }

            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();

        try {
            $this->customerService->addToWishlist($customer, $productId);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produto adicionado à lista de desejos',
                ]);
            }

            return redirect()->back()->with('success', 'Produto adicionado à lista de desejos');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao adicionar produto à lista de desejos',
                ], 500);
            }

            return redirect()->back()->with('error', 'Erro ao adicionar produto à lista de desejos');
        }
    }

    /**
     * Remove product from wishlist.
     */
    public function removeFromWishlist(Request $request, int $productId): JsonResponse|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faça login para gerenciar sua lista de desejos',
                ], 401);
            }

            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();

        try {
            $this->customerService->removeFromWishlist($customer, $productId);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produto removido da lista de desejos',
                ]);
            }

            return redirect()->back()->with('success', 'Produto removido da lista de desejos');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao remover produto da lista de desejos',
                ], 500);
            }

            return redirect()->back()->with('error', 'Erro ao remover produto da lista de desejos');
        }
    }

    /**
     * Display customer reviews.
     */
    public function reviews(Request $request): View|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();
        $reviews = $this->reviewService->getCustomerReviews($customer->id, 10);

        return view('store::customers.reviews', compact('reviews'));
    }

    /**
     * Change customer password.
     */
    public function changePassword(Request $request): JsonResponse|RedirectResponse
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('store.login');
        }

        $customer = Auth::guard('customer')->user();

        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validatedData['current_password'], $customer->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual incorreta',
                ], 400);
            }

            return redirect()->back()->with('error', 'Senha atual incorreta');
        }

        try {
            $this->customerService->updateCustomer($customer, [
                'password' => $validatedData['password']
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Senha alterada com sucesso!',
                ]);
            }

            return redirect()->back()->with('success', 'Senha alterada com sucesso!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao alterar senha: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Erro ao alterar senha: ' . $e->getMessage());
        }
    }

    /**
     * Get customer statistics.
     */
    public function stats(): JsonResponse
    {
        if (!Auth::guard('customer')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado',
            ], 401);
        }

        $customer = Auth::guard('customer')->user();
        $stats = [
            'total_orders' => $customer->orders()->count(),
            'total_spent' => $this->customerService->getCustomerLifetimeValue($customer),
            'average_order_value' => $this->customerService->getCustomerAverageOrderValue($customer),
            'total_reviews' => $customer->reviews()->count(),
            'wishlist_count' => $customer->wishlistItems()->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}