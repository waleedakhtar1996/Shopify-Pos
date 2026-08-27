<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;

class SalesSyncService
{
    protected function api(User $shop)
    {
        return $shop->api();
    }

    public function syncOrders(User $shop, int $limit = 250)
    {
        $api = $this->api($shop);
        $count = 0;
        $params = ['status' => 'any', 'limit' => $limit];

        do {
            $response = $api->rest('GET', '/admin/api/2024-04/orders.json', $params);
            $orders = $response['body']['orders'] ?? [];

            foreach ($orders as $orderData) {
                $orderData = is_array($orderData) ? $orderData : $orderData->toArray();
                $this->saveOrder($shop, $orderData);
                $count++;
            }

            // Cursor-based pagination via link object
            $params = [];
            $nextPageInfo = $response['link']['next'] ?? null;
            if ($nextPageInfo) {
                $params = ['limit' => $limit, 'page_info' => $nextPageInfo];
            }
        } while (!empty($params));

        return $count;
    }

    protected function saveOrder(User $shop, array $data)
    {
        $customer = null;

        if (!empty($data['customer'])) {
            $customerData = is_array($data['customer']) ? $data['customer'] : (array) $data['customer'];

            if (!empty($data['shipping_address'])) {
                $addr = is_array($data['shipping_address']) ? $data['shipping_address'] : (array) $data['shipping_address'];
                if (empty($customerData['phone']) && !empty($addr['phone'])) {
                    $customerData['phone'] = $addr['phone'];
                }
                $customerData['city'] = $addr['city'] ?? null;
                $customerData['country'] = $addr['country'] ?? null;
                $customerData['address'] = implode(', ', array_filter([
                    $addr['address1'] ?? null,
                    $addr['province'] ?? null,
                    $addr['zip'] ?? null,
                ]));
            }

            if (empty($customerData['phone']) && !empty($data['phone'])) {
                $customerData['phone'] = $data['phone'];
            }

            $customer = $this->saveCustomer($shop, $customerData);
        }

        $paymentMethod = $data['payment_gateway_names'][0] ?? ($data['gateway'] ?? null);

        $shippingAddress = null;
        if (!empty($data['shipping_address'])) {
            $addr = is_array($data['shipping_address']) ? $data['shipping_address'] : (array) $data['shipping_address'];
            $shippingAddress = implode(', ', array_filter([
                $addr['address1'] ?? null,
                $addr['city'] ?? null,
                $addr['province'] ?? null,
                $addr['zip'] ?? null,
                $addr['country'] ?? null,
            ]));
        }

        $totalRefunded = 0;
        if (!empty($data['refunds'])) {
            foreach ($data['refunds'] as $refund) {
                $refund = is_array($refund) ? $refund : (array) $refund;
                foreach (($refund['transactions'] ?? []) as $txn) {
                    $txn = is_array($txn) ? $txn : (array) $txn;
                    if (($txn['kind'] ?? '') === 'refund') {
                        $totalRefunded += (float) ($txn['amount'] ?? 0);
                    }
                }
            }
        }

        $order = Order::updateOrCreate(
            [
                'user_id' => $shop->id,
                'shopify_order_id' => $data['id'],
            ],
            [
                'customer_id' => $customer?->id,
                'order_number' => $data['name'] ?? ($data['order_number'] ?? null),
                'financial_status' => $data['financial_status'] ?? null,
                'fulfillment_status' => $data['fulfillment_status'] ?? null,
                'payment_method' => $paymentMethod,
                'subtotal_price' => $data['subtotal_price'] ?? 0,
                'total_tax' => $data['total_tax'] ?? 0,
                'total_discounts' => $data['total_discounts'] ?? 0,
                'total_price' => $data['total_price'] ?? 0,
                'total_refunded' => $totalRefunded,
                'currency' => $data['currency'] ?? null,
                'customer_name' => $customer ? $customer->full_name : ($data['email'] ?? null),
                'customer_email' => $data['email'] ?? null,
                'shipping_address' => $shippingAddress,
                'shopify_created_at' => $data['created_at'] ?? null,
            ]
        );

        $lineItems = $data['line_items'] ?? [];
        $keepIds = [];

        foreach ($lineItems as $item) {
            $item = is_array($item) ? $item : (array) $item;

            $orderItem = OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'shopify_line_item_id' => $item['id'] ?? null,
                ],
                [
                    'title' => $item['title'] ?? null,
                    'variant_title' => $item['variant_title'] ?? null,
                    'sku' => $item['sku'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                ]
            );
            $keepIds[] = $orderItem->id;
        }

        $order->items()->whereNotIn('id', $keepIds)->delete();

        if ($customer) {
            $stats = Order::where('customer_id', $customer->id)
                ->selectRaw('COUNT(*) as cnt, SUM(total_price) as total')
                ->first();
            $customer->update([
                'orders_count' => $stats->cnt ?? 0,
                'total_spent' => $stats->total ?? 0,
            ]);
        }

        return $order;
    }

    protected function saveCustomer(User $shop, array $data)
    {
        if (empty($data['id'])) {
            return null;
        }

        $existing = Customer::where('user_id', $shop->id)->where('shopify_customer_id', $data['id'])->first();

        return Customer::updateOrCreate(
            [
                'user_id' => $shop->id,
                'shopify_customer_id' => $data['id'],
            ],
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? ($existing->phone ?? null),
                'city' => $data['city'] ?? ($existing->city ?? null),
                'country' => $data['country'] ?? ($existing->country ?? null),
                'address' => $data['address'] ?? ($existing->address ?? null),
                'orders_count' => $data['orders_count'] ?? 0,
                'total_spent' => $data['total_spent'] ?? 0,
            ]
        );
    }

    public function syncCustomers(User $shop, int $limit = 250)
    {
        $api = $this->api($shop);
        $count = 0;

        $response = $api->rest('GET', '/admin/api/2024-04/customers.json', [
            'limit' => $limit,
        ]);

        $customers = $response['body']['customers'] ?? [];

        foreach ($customers as $customerData) {
            $customerData = is_array($customerData) ? $customerData : $customerData->toArray();

            if (!empty($customerData['default_address'])) {
                $addr = is_array($customerData['default_address']) ? $customerData['default_address'] : (array) $customerData['default_address'];
                $customerData['city'] = $addr['city'] ?? null;
                $customerData['country'] = $addr['country'] ?? null;
                if (empty($customerData['phone']) && !empty($addr['phone'])) {
                    $customerData['phone'] = $addr['phone'];
                }
                $customerData['address'] = implode(', ', array_filter([
                    $addr['address1'] ?? null,
                    $addr['province'] ?? null,
                    $addr['zip'] ?? null,
                ]));
            }

            $this->saveCustomer($shop, $customerData);
            $count++;
        }

        return $count;
    }
}
