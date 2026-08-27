<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;

class CustomerSyncService
{
    public function syncCustomers(User $shop): array
    {
        $synced = 0;
        $params = ['limit' => 250];

        do {
            $response = $shop->api()->rest('GET', '/admin/api/2025-10/customers.json', $params);

            $body = $response['body'];
            if (is_object($body) && method_exists($body, 'toArray')) {
                $body = $body->toArray();
            } elseif (is_string($body)) {
                $body = json_decode($body, true);
            } elseif (is_object($body)) {
                $body = json_decode(json_encode($body), true);
            }

            $customers = $body['customers'] ?? [];

            foreach ($customers as $c) {
                $address = $c['default_address'] ?? [];

                Customer::updateOrCreate(
                    [
                        'user_id' => $shop->id,
                        'shopify_customer_id' => $c['id'],
                    ],
                    [
                        'first_name'   => $c['first_name'] ?? null,
                        'last_name'    => $c['last_name'] ?? null,
                        'email'        => $c['email'] ?? null,
                        'phone'        => $c['phone'] ?? null,
                        'orders_count' => $c['orders_count'] ?? 0,
                        'total_spent'  => $c['total_spent'] ?? 0,
                        'address'      => $address['address1'] ?? null,
                        'city'         => $address['city'] ?? null,
                        'country'      => $address['country'] ?? null,
                    ]
                );
                $synced++;
            }

            $params = [];
            $headers = $response['headers'] ?? [];
            $link = $headers['Link'] ?? $headers['link'] ?? null;
            if (is_array($link)) {
                $link = implode(',', $link);
            }
            if ($link && str_contains($link, 'rel="next"')) {
                preg_match('/page_info=([^&>]+).*rel="next"/', $link, $m);
                if (!empty($m[1])) {
                    $params = ['limit' => 250, 'page_info' => $m[1]];
                }
            }
        } while (!empty($params));

        return ['synced' => $synced];
    }
}
