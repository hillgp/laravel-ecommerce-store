<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\Address;
use Carbon\Carbon;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private Customer $customer;
    private Product $product;
    private Coupon $coupon;
    private Address $billingAddress;
    private Address $shippingAddress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create(['price' => 100.00]);
        $this->coupon = Coupon::factory()->create([
            'code' => 'DESCONTO10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true
        ]);

        $this->billingAddress = Address::factory()->create([
            'customer_id' => $this->customer->id,
            'type' => 'billing'
        ]);

        $this->shippingAddress = Address::factory()->create([
            'customer_id' => $this->customer->id,
            'type' => 'shipping'
        ]);

        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->shippingAddress->id,
            'coupon_id' => $this->coupon->id,
            'order_number' => 'ORD-2024-001',
            'status' => 'pending',
            'subtotal' => 200.00,
            'tax_amount' => 20.00,
            'shipping_amount' => 15.00,
            'discount_amount' => 20.00,
            'total_amount' => 215.00,
            'currency' => 'BRL',
            'payment_method' => 'credit_card',
            'payment_status' => 'pending',
            'shipping_method' => 'standard',
            'notes' => 'Pedido de teste para unit tests'
        ]);

        // Criar item do pedido
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00
        ]);
    }

    public function test_order_belongs_to_customer()
    {
        $this->assertInstanceOf(Customer::class, $this->order->customer);
        $this->assertEquals($this->customer->id, $this->order->customer->id);
    }

    public function test_order_belongs_to_billing_address()
    {
        $this->assertInstanceOf(Address::class, $this->order->billingAddress);
        $this->assertEquals($this->billingAddress->id, $this->order->billingAddress->id);
    }

    public function test_order_belongs_to_shipping_address()
    {
        $this->assertInstanceOf(Address::class, $this->order->shippingAddress);
        $this->assertEquals($this->shippingAddress->id, $this->order->shippingAddress->id);
    }

    public function test_order_belongs_to_coupon()
    {
        $this->assertInstanceOf(Coupon::class, $this->order->coupon);
        $this->assertEquals($this->coupon->id, $this->order->coupon->id);
    }

    public function test_order_has_many_order_items()
    {
        $this->assertCount(1, $this->order->items);
        $this->assertInstanceOf(OrderItem::class, $this->order->items->first());
    }

    public function test_order_can_generate_order_number()
    {
        $order = new Order();
        $order->generateOrderNumber();

        $this->assertStringStartsWith('ORD-', $order->order_number);
        $this->assertEquals(12, strlen($order->order_number));
    }

    public function test_order_can_calculate_totals()
    {
        $this->assertEquals(200.00, $this->order->subtotal);
        $this->assertEquals(20.00, $this->order->tax_amount);
        $this->assertEquals(15.00, $this->order->shipping_amount);
        $this->assertEquals(20.00, $this->order->discount_amount);
        $this->assertEquals(215.00, $this->order->total_amount);
    }

    public function test_order_can_get_formatted_total()
    {
        $formattedTotal = $this->order->getFormattedTotal();
        $this->assertEquals('R$ 215,00', $formattedTotal);
    }

    public function test_order_can_get_formatted_subtotal()
    {
        $formattedSubtotal = $this->order->getFormattedSubtotal();
        $this->assertEquals('R$ 200,00', $formattedSubtotal);
    }

    public function test_order_can_get_status_badge()
    {
        $badge = $this->order->getStatusBadge();
        $this->assertEquals('warning', $badge['type']);
        $this->assertEquals('Pendente', $badge['text']);
    }

    public function test_order_can_get_payment_status_badge()
    {
        $badge = $this->order->getPaymentStatusBadge();
        $this->assertEquals('warning', $badge['type']);
        $this->assertEquals('Pendente', $badge['text']);
    }

    public function test_order_can_check_if_is_pending()
    {
        $this->assertTrue($this->order->isPending());
        $this->assertFalse($this->order->isProcessing());
        $this->assertFalse($this->order->isShipped());
        $this->assertFalse($this->order->isDelivered());
        $this->assertFalse($this->order->isCancelled());
    }

    public function test_order_can_check_if_is_processing()
    {
        $this->order->update(['status' => 'processing']);
        $this->assertFalse($this->order->isPending());
        $this->assertTrue($this->order->isProcessing());
        $this->assertFalse($this->order->isShipped());
        $this->assertFalse($this->order->isDelivered());
        $this->assertFalse($this->order->isCancelled());
    }

    public function test_order_can_check_if_is_shipped()
    {
        $this->order->update(['status' => 'shipped']);
        $this->assertFalse($this->order->isPending());
        $this->assertFalse($this->order->isProcessing());
        $this->assertTrue($this->order->isShipped());
        $this->assertFalse($this->order->isDelivered());
        $this->assertFalse($this->order->isCancelled());
    }

    public function test_order_can_check_if_is_delivered()
    {
        $this->order->update(['status' => 'delivered']);
        $this->assertFalse($this->order->isPending());
        $this->assertFalse($this->order->isProcessing());
        $this->assertFalse($this->order->isShipped());
        $this->assertTrue($this->order->isDelivered());
        $this->assertFalse($this->order->isCancelled());
    }

    public function test_order_can_check_if_is_cancelled()
    {
        $this->order->update(['status' => 'cancelled']);
        $this->assertFalse($this->order->isPending());
        $this->assertFalse($this->order->isProcessing());
        $this->assertFalse($this->order->isShipped());
        $this->assertFalse($this->order->isDelivered());
        $this->assertTrue($this->order->isCancelled());
    }

    public function test_order_can_check_if_payment_is_pending()
    {
        $this->assertTrue($this->order->isPaymentPending());
        $this->assertFalse($this->order->isPaymentPaid());
        $this->assertFalse($this->order->isPaymentFailed());
        $this->assertFalse($this->order->isPaymentRefunded());
    }

    public function test_order_can_check_if_payment_is_paid()
    {
        $this->order->update(['payment_status' => 'paid']);
        $this->assertFalse($this->order->isPaymentPending());
        $this->assertTrue($this->order->isPaymentPaid());
        $this->assertFalse($this->order->isPaymentFailed());
        $this->assertFalse($this->order->isPaymentRefunded());
    }

    public function test_order_can_check_if_payment_is_failed()
    {
        $this->order->update(['payment_status' => 'failed']);
        $this->assertFalse($this->order->isPaymentPending());
        $this->assertFalse($this->order->isPaymentPaid());
        $this->assertTrue($this->order->isPaymentFailed());
        $this->assertFalse($this->order->isPaymentRefunded());
    }

    public function test_order_can_check_if_payment_is_refunded()
    {
        $this->order->update(['payment_status' => 'refunded']);
        $this->assertFalse($this->order->isPaymentPending());
        $this->assertFalse($this->order->isPaymentPaid());
        $this->assertFalse($this->order->isPaymentFailed());
        $this->assertTrue($this->order->isPaymentRefunded());
    }

    public function test_order_can_get_status_history()
    {
        $history = $this->order->getStatusHistory();
        $this->assertIsArray($history);
        $this->assertArrayHasKey('status', $history);
        $this->assertArrayHasKey('payment_status', $history);
        $this->assertArrayHasKey('created_at', $history);
    }

    public function test_order_can_get_tracking_information()
    {
        $tracking = $this->order->getTrackingInformation();
        $this->assertIsArray($tracking);
        $this->assertArrayHasKey('order_number', $tracking);
        $this->assertArrayHasKey('status', $tracking);
        $this->assertArrayHasKey('shipping_method', $tracking);
        $this->assertArrayHasKey('tracking_code', $tracking);
    }

    public function test_order_can_get_customer_information()
    {
        $customerInfo = $this->order->getCustomerInformation();
        $this->assertIsArray($customerInfo);
        $this->assertArrayHasKey('name', $customerInfo);
        $this->assertArrayHasKey('email', $customerInfo);
        $this->assertArrayHasKey('phone', $customerInfo);
    }

    public function test_order_can_get_billing_information()
    {
        $billingInfo = $this->order->getBillingInformation();
        $this->assertIsArray($billingInfo);
        $this->assertArrayHasKey('address', $billingInfo);
        $this->assertArrayHasKey('city', $billingInfo);
        $this->assertArrayHasKey('state', $billingInfo);
        $this->assertArrayHasKey('zipcode', $billingInfo);
    }

    public function test_order_can_get_shipping_information()
    {
        $shippingInfo = $this->order->getShippingInformation();
        $this->assertIsArray($shippingInfo);
        $this->assertArrayHasKey('address', $shippingInfo);
        $this->assertArrayHasKey('city', $shippingInfo);
        $this->assertArrayHasKey('state', $shippingInfo);
        $this->assertArrayHasKey('zipcode', $shippingInfo);
    }

    public function test_order_can_get_items_summary()
    {
        $itemsSummary = $this->order->getItemsSummary();
        $this->assertIsArray($itemsSummary);
        $this->assertCount(1, $itemsSummary);
        $this->assertArrayHasKey('product_name', $itemsSummary[0]);
        $this->assertArrayHasKey('quantity', $itemsSummary[0]);
        $this->assertArrayHasKey('unit_price', $itemsSummary[0]);
        $this->assertArrayHasKey('total_price', $itemsSummary[0]);
    }

    public function test_order_can_get_payment_summary()
    {
        $paymentSummary = $this->order->getPaymentSummary();
        $this->assertIsArray($paymentSummary);
        $this->assertArrayHasKey('method', $paymentSummary);
        $this->assertArrayHasKey('status', $paymentSummary);
        $this->assertArrayHasKey('total', $paymentSummary);
        $this->assertArrayHasKey('currency', $paymentSummary);
    }

    public function test_order_can_get_discount_breakdown()
    {
        $discountBreakdown = $this->order->getDiscountBreakdown();
        $this->assertIsArray($discountBreakdown);
        $this->assertArrayHasKey('coupon_code', $discountBreakdown);
        $this->assertArrayHasKey('discount_type', $discountBreakdown);
        $this->assertArrayHasKey('discount_value', $discountBreakdown);
        $this->assertArrayHasKey('discount_amount', $discountBreakdown);
    }

    public function test_order_can_get_tax_breakdown()
    {
        $taxBreakdown = $this->order->getTaxBreakdown();
        $this->assertIsArray($taxBreakdown);
        $this->assertArrayHasKey('tax_rate', $taxBreakdown);
        $this->assertArrayHasKey('tax_amount', $taxBreakdown);
        $this->assertArrayHasKey('subtotal', $taxBreakdown);
    }

    public function test_order_can_get_shipping_breakdown()
    {
        $shippingBreakdown = $this->order->getShippingBreakdown();
        $this->assertIsArray($shippingBreakdown);
        $this->assertArrayHasKey('method', $shippingBreakdown);
        $this->assertArrayHasKey('amount', $shippingBreakdown);
        $this->assertArrayHasKey('estimated_days', $shippingBreakdown);
    }

    public function test_order_can_get_total_breakdown()
    {
        $totalBreakdown = $this->order->getTotalBreakdown();
        $this->assertIsArray($totalBreakdown);
        $this->assertArrayHasKey('subtotal', $totalBreakdown);
        $this->assertArrayHasKey('tax_amount', $totalBreakdown);
        $this->assertArrayHasKey('shipping_amount', $totalBreakdown);
        $this->assertArrayHasKey('discount_amount', $totalBreakdown);
        $this->assertArrayHasKey('total_amount', $totalBreakdown);
    }

    public function test_order_can_get_formatted_breakdown()
    {
        $formattedBreakdown = $this->order->getFormattedBreakdown();
        $this->assertIsArray($formattedBreakdown);
        $this->assertArrayHasKey('subtotal', $formattedBreakdown);
        $this->assertArrayHasKey('tax_amount', $formattedBreakdown);
        $this->assertArrayHasKey('shipping_amount', $formattedBreakdown);
        $this->assertArrayHasKey('discount_amount', $formattedBreakdown);
        $this->assertArrayHasKey('total_amount', $formattedBreakdown);
        $this->assertEquals('R$ 200,00', $formattedBreakdown['subtotal']);
        $this->assertEquals('R$ 20,00', $formattedBreakdown['tax_amount']);
        $this->assertEquals('R$ 15,00', $formattedBreakdown['shipping_amount']);
        $this->assertEquals('R$ 20,00', $formattedBreakdown['discount_amount']);
        $this->assertEquals('R$ 215,00', $formattedBreakdown['total_amount']);
    }

    public function test_order_can_scope_by_status()
    {
        $pendingOrders = Order::byStatus('pending')->get();
        $this->assertTrue($pendingOrders->contains($this->order));
    }

    public function test_order_can_scope_by_payment_status()
    {
        $pendingPaymentOrders = Order::byPaymentStatus('pending')->get();
        $this->assertTrue($pendingPaymentOrders->contains($this->order));
    }

    public function test_order_can_scope_by_date_range()
    {
        $todayOrders = Order::byDateRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay()
        )->get();
        $this->assertTrue($todayOrders->contains($this->order));
    }

    public function test_order_can_scope_by_customer()
    {
        $customerOrders = Order::byCustomer($this->customer->id)->get();
        $this->assertTrue($customerOrders->contains($this->order));
    }

    public function test_order_can_scope_by_total_range()
    {
        $ordersInRange = Order::byTotalRange(200, 300)->get();
        $this->assertTrue($ordersInRange->contains($this->order));
    }

    public function test_order_can_scope_by_payment_method()
    {
        $creditCardOrders = Order::byPaymentMethod('credit_card')->get();
        $this->assertTrue($creditCardOrders->contains($this->order));
    }

    public function test_order_can_scope_by_shipping_method()
    {
        $standardShippingOrders = Order::byShippingMethod('standard')->get();
        $this->assertTrue($standardShippingOrders->contains($this->order));
    }

    public function test_order_can_scope_recent()
    {
        $recentOrders = Order::recent(10)->get();
        $this->assertTrue($recentOrders->contains($this->order));
    }

    public function test_order_can_scope_today()
    {
        $todayOrders = Order::today()->get();
        $this->assertTrue($todayOrders->contains($this->order));
    }

    public function test_order_can_scope_this_week()
    {
        $thisWeekOrders = Order::thisWeek()->get();
        $this->assertTrue($thisWeekOrders->contains($this->order));
    }

    public function test_order_can_scope_this_month()
    {
        $thisMonthOrders = Order::thisMonth()->get();
        $this->assertTrue($thisMonthOrders->contains($this->order));
    }

    public function test_order_can_scope_this_year()
    {
        $thisYearOrders = Order::thisYear()->get();
        $this->assertTrue($thisYearOrders->contains($this->order));
    }

    public function test_order_can_scope_pending_payment()
    {
        $pendingPaymentOrders = Order::pendingPayment()->get();
        $this->assertTrue($pendingPaymentOrders->contains($this->order));
    }

    public function test_order_can_scope_paid()
    {
        $this->order->update(['payment_status' => 'paid']);
        $paidOrders = Order::paid()->get();
        $this->assertTrue($paidOrders->contains($this->order));
    }

    public function test_order_can_scope_failed_payment()
    {
        $this->order->update(['payment_status' => 'failed']);
        $failedPaymentOrders = Order::failedPayment()->get();
        $this->assertTrue($failedPaymentOrders->contains($this->order));
    }

    public function test_order_can_scope_refunded()
    {
        $this->order->update(['payment_status' => 'refunded']);
        $refundedOrders = Order::refunded()->get();
        $this->assertTrue($refundedOrders->contains($this->order));
    }

    public function test_order_can_scope_pending_orders()
    {
        $pendingOrders = Order::pendingOrders()->get();
        $this->assertTrue($pendingOrders->contains($this->order));
    }

    public function test_order_can_scope_processing_orders()
    {
        $this->order->update(['status' => 'processing']);
        $processingOrders = Order::processingOrders()->get();
        $this->assertTrue($processingOrders->contains($this->order));
    }

    public function test_order_can_scope_shipped_orders()
    {
        $this->order->update(['status' => 'shipped']);
        $shippedOrders = Order::shippedOrders()->get();
        $this->assertTrue($shippedOrders->contains($this->order));
    }

    public function test_order_can_scope_delivered_orders()
    {
        $this->order->update(['status' => 'delivered']);
        $deliveredOrders = Order::deliveredOrders()->get();
        $this->assertTrue($deliveredOrders->contains($this->order));
    }

    public function test_order_can_scope_cancelled_orders()
    {
        $this->order->update(['status' => 'cancelled']);
        $cancelledOrders = Order::cancelledOrders()->get();
        $this->assertTrue($cancelledOrders->contains($this->order));
    }

    public function test_order_can_scope_high_value()
    {
        $highValueOrders = Order::highValue(200)->get();
        $this->assertTrue($highValueOrders->contains($this->order));
    }

    public function test_order_can_scope_low_value()
    {
        $lowValueOrders = Order::lowValue(50)->get();
        $this->assertFalse($lowValueOrders->contains($this->order));
    }

    public function test_order_can_scope_with_coupon()
    {
        $ordersWithCoupon = Order::withCoupon()->get();
        $this->assertTrue($ordersWithCoupon->contains($this->order));
    }

    public function test_order_can_scope_without_coupon()
    {
        $orderWithoutCoupon = Order::factory()->create(['coupon_id' => null]);
        $ordersWithoutCoupon = Order::withoutCoupon()->get();
        $this->assertTrue($ordersWithoutCoupon->contains($orderWithoutCoupon));
    }

    public function test_order_can_scope_by_coupon()
    {
        $ordersByCoupon = Order::byCoupon($this->coupon->id)->get();
        $this->assertTrue($ordersByCoupon->contains($this->order));
    }

    public function test_order_can_scope_by_order_number()
    {
        $orderByNumber = Order::byOrderNumber($this->order->order_number);
        $this->assertEquals($this->order->id, $orderByNumber->id);
    }

    public function test_order_can_scope_by_tracking_code()
    {
        $this->order->update(['tracking_code' => 'BR123456789']);
        $orderByTracking = Order::byTrackingCode('BR123456789');
        $this->assertEquals($this->order->id, $orderByTracking->id);
    }

    public function test_order_can_get_order_by_number()
    {
        $order = Order::getOrderByNumber($this->order->order_number);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($this->order->id, $order->id);
    }

    public function test_order_can_get_order_by_tracking_code()
    {
        $this->order->update(['tracking_code' => 'BR123456789']);
        $order = Order::getOrderByTrackingCode('BR123456789');
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($this->order->id, $order->id);
    }

    public function test_order_can_get_orders_by_customer()
    {
        $orders = Order::getOrdersByCustomer($this->customer->id, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_status()
    {
        $orders = Order::getOrdersByStatus('pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_status()
    {
        $orders = Order::getOrdersByPaymentStatus('pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_range()
    {
        $orders = Order::getOrdersByDateRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_total_range()
    {
        $orders = Order::getOrdersByTotalRange(200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method()
    {
        $orders = Order::getOrdersByPaymentMethod('credit_card', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method()
    {
        $orders = Order::getOrdersByShippingMethod('standard', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_recent_orders()
    {
        $orders = Order::getRecentOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_today_orders()
    {
        $orders = Order::getTodayOrders();
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_this_week_orders()
    {
        $orders = Order::getThisWeekOrders();
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_this_month_orders()
    {
        $orders = Order::getThisMonthOrders();
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_this_year_orders()
    {
        $orders = Order::getThisYearOrders();
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_pending_payment_orders()
    {
        $orders = Order::getPendingPaymentOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_paid_orders()
    {
        $this->order->update(['payment_status' => 'paid']);
        $orders = Order::getPaidOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_failed_payment_orders()
    {
        $this->order->update(['payment_status' => 'failed']);
        $orders = Order::getFailedPaymentOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_refunded_orders()
    {
        $this->order->update(['payment_status' => 'refunded']);
        $orders = Order::getRefundedOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_pending_orders()
    {
        $orders = Order::getPendingOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_processing_orders()
    {
        $this->order->update(['status' => 'processing']);
        $orders = Order::getProcessingOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_shipped_orders()
    {
        $this->order->update(['status' => 'shipped']);
        $orders = Order::getShippedOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_delivered_orders()
    {
        $this->order->update(['status' => 'delivered']);
        $orders = Order::getDeliveredOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_cancelled_orders()
    {
        $this->order->update(['status' => 'cancelled']);
        $orders = Order::getCancelledOrders(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_high_value_orders()
    {
        $orders = Order::getHighValueOrders(200, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_low_value_orders()
    {
        $orders = Order::getLowValueOrders(50, 5);
        $this->assertFalse($orders->contains($this->order));
    }

    public function test_order_can_get_orders_with_coupon()
    {
        $orders = Order::getOrdersWithCoupon(5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_without_coupon()
    {
        $orderWithoutCoupon = Order::factory()->create(['coupon_id' => null]);
        $orders = Order::getOrdersWithoutCoupon(5);
        $this->assertTrue($orders->contains($orderWithoutCoupon));
    }

    public function test_order_can_get_orders_by_coupon()
    {
        $orders = Order::getOrdersByCoupon($this->coupon->id, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_order_number()
    {
        $orders = Order::getOrdersByOrderNumber($this->order->order_number);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_tracking_code()
    {
        $this->order->update(['tracking_code' => 'BR123456789']);
        $orders = Order::getOrdersByTrackingCode('BR123456789');
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_email()
    {
        $orders = Order::getOrdersByCustomerEmail($this->customer->email, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_phone()
    {
        $orders = Order::getOrdersByCustomerPhone($this->customer->phone, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_billing_zipcode()
    {
        $orders = Order::getOrdersByBillingZipcode($this->billingAddress->zipcode, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_zipcode()
    {
        $orders = Order::getOrdersByShippingZipcode($this->shippingAddress->zipcode, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_billing_city()
    {
        $orders = Order::getOrdersByBillingCity($this->billingAddress->city, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_city()
    {
        $orders = Order::getOrdersByShippingCity($this->shippingAddress->city, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_billing_state()
    {
        $orders = Order::getOrdersByBillingState($this->billingAddress->state, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_state()
    {
        $orders = Order::getOrdersByShippingState($this->shippingAddress->state, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_billing_country()
    {
        $orders = Order::getOrdersByBillingCountry($this->billingAddress->country, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_country()
    {
        $orders = Order::getOrdersByShippingCountry($this->shippingAddress->country, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_currency()
    {
        $orders = Order::getOrdersByCurrency('BRL', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_and_status()
    {
        $orders = Order::getOrdersByPaymentMethodAndStatus('credit_card', 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_and_status()
    {
        $orders = Order::getOrdersByShippingMethodAndStatus('standard', 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }


    public function test_order_can_get_orders_by_total_range_and_status()
    {
        $orders = Order::getOrdersByTotalRangeAndStatus(200, 300, 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_and_status()
    {
        $orders = Order::getOrdersByCustomerAndStatus($this->customer->id, 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_and_status()
    {
        $orders = Order::getOrdersByCouponAndStatus($this->coupon->id, 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_and_payment_status()
    {
        $orders = Order::getOrdersByPaymentMethodAndPaymentStatus('credit_card', 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_and_payment_status()
    {
        $orders = Order::getOrdersByShippingMethodAndPaymentStatus('standard', 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_and_total_range()
    {
        $orders = Order::getOrdersByDateAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_and_total_range()
    {
        $orders = Order::getOrdersByCustomerAndTotalRange($this->customer->id, 200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_and_total_range()
    {
        $orders = Order::getOrdersByCouponAndTotalRange($this->coupon->id, 200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodAndTotalRange('credit_card', 200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodAndTotalRange('standard', 200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_status_and_payment_status()
    {
        $orders = Order::getOrdersByStatusAndPaymentStatus('pending', 'pending', 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_status_and_total_range()
    {
        $orders = Order::getOrdersByStatusAndTotalRange('pending', 200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentStatusAndTotalRange('pending', 200, 300, 5);
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_status_and_total_range()
    {
        $orders = Order::getOrdersByDateStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDatePaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_status_and_total_range()
    {
        $orders = Order::getOrdersByCustomerStatusAndTotalRange(
            $this->customer->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_status_and_total_range()
    {
        $orders = Order::getOrdersByCouponStatusAndTotalRange(
            $this->coupon->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodStatusAndTotalRange(
            'credit_card',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_status_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodStatusAndTotalRange(
            'standard',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_customer_and_status()
    {
        $orders = Order::getOrdersByDateCustomerAndStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->customer->id,
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_coupon_and_status()
    {
        $orders = Order::getOrdersByDateCouponAndStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->coupon->id,
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_payment_method_and_status()
    {
        $orders = Order::getOrdersByDatePaymentMethodAndStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'credit_card',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_shipping_method_and_status()
    {
        $orders = Order::getOrdersByDateShippingMethodAndStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'standard',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCustomerPaymentStatusAndTotalRange(
            $this->customer->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCouponPaymentStatusAndTotalRange(
            $this->coupon->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodPaymentStatusAndTotalRange(
            'credit_card',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodPaymentStatusAndTotalRange(
            'standard',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_status_and_payment_status()
    {
        $orders = Order::getOrdersByDateStatusAndPaymentStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_status_and_payment_status()
    {
        $orders = Order::getOrdersByCustomerStatusAndPaymentStatus(
            $this->customer->id,
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_status_and_payment_status()
    {
        $orders = Order::getOrdersByCouponStatusAndPaymentStatus(
            $this->coupon->id,
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_status_and_payment_status()
    {
        $orders = Order::getOrdersByPaymentMethodStatusAndPaymentStatus(
            'credit_card',
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_status_and_payment_status()
    {
        $orders = Order::getOrdersByShippingMethodStatusAndPaymentStatus(
            'standard',
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_customer_status_and_payment_status()
    {
        $orders = Order::getOrdersByDateCustomerStatusAndPaymentStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->customer->id,
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_coupon_status_and_payment_status()
    {
        $orders = Order::getOrdersByDateCouponStatusAndPaymentStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->coupon->id,
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_payment_method_status_and_payment_status()
    {
        $orders = Order::getOrdersByDatePaymentMethodStatusAndPaymentStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'credit_card',
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_shipping_method_status_and_payment_status()
    {
        $orders = Order::getOrdersByDateShippingMethodStatusAndPaymentStatus(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'standard',
            'pending',
            'pending',
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCustomerPaymentStatusAndTotalRange(
            $this->customer->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCouponPaymentStatusAndTotalRange(
            $this->coupon->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodPaymentStatusAndTotalRange(
            'credit_card',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodPaymentStatusAndTotalRange(
            'standard',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCustomerStatusPaymentStatusAndTotalRange(
            $this->customer->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCouponStatusPaymentStatusAndTotalRange(
            $this->coupon->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodStatusPaymentStatusAndTotalRange(
            'credit_card',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodStatusPaymentStatusAndTotalRange(
            'standard',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_customer_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateCustomerStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->customer->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_coupon_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateCouponStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->coupon->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_payment_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDatePaymentMethodStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'credit_card',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_shipping_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateShippingMethodStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'standard',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCustomerPaymentStatusAndTotalRange(
            $this->customer->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCouponPaymentStatusAndTotalRange(
            $this->coupon->id,
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodPaymentStatusAndTotalRange(
            'credit_card',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodPaymentStatusAndTotalRange(
            'standard',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_customer_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCustomerStatusPaymentStatusAndTotalRange(
            $this->customer->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_coupon_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByCouponStatusPaymentStatusAndTotalRange(
            $this->coupon->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_payment_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByPaymentMethodStatusPaymentStatusAndTotalRange(
            'credit_card',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_shipping_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByShippingMethodStatusPaymentStatusAndTotalRange(
            'standard',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_customer_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateCustomerStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->customer->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_coupon_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateCouponStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            $this->coupon->id,
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_payment_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDatePaymentMethodStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'credit_card',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }

    public function test_order_can_get_orders_by_date_shipping_method_status_payment_status_and_total_range()
    {
        $orders = Order::getOrdersByDateShippingMethodStatusPaymentStatusAndTotalRange(
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
            'standard',
            'pending',
            'pending',
            200,
            300,
            5
        );
        $this->assertTrue($orders->contains($this->order));
    }
}