<?php

require __DIR__ ."/vendor/autoload.php";

\Stripe\Stripe::setApiKey($stripe_secret_key);

$checkout_session = \Stripe\Checkout\Session::create([
  "mode"  => "payment",
  "success_url" => "http://localhost/doctors-appointment-system/success.php",
  "cancel_url" => "http://localhost/doctors-appointment-system/index.php",
  "line_items" => [
    [
    "quantity" => 1,
    "price_data" => [
      "currency" => "usd",
      "unit_amount" => 2000,
      "product_data" => [
        "name" => "T-shirt",
      ]
    ]
      ],
      [
        "quantity" => 2,
        "price_data" => [
          "currency" => "usd",
          "unit_amount" => 700,
          "product_data" => [
            "name" => "Short",
          ]
        ]
      ]
]


]);
http_response_code(303);
header("Location: " . $checkout_session->url);