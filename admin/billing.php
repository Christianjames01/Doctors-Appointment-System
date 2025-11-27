<!DOCTYPE html>
<html>
<head>
  <title>Stripe Example</title>
  <link rel="stylesheet" href="css/billing.css">
  <meta charset="UTF-8">
</head>
<body>

  <div class="container">
    <h1>Stripe Example</h1>
    
    <form method="post" action="checkout.php" class="checkout-form">
      <p class="product-name"></p>
      <p class="price"><strong>US$20.00</strong></p>

      <label for="quantity">Quantity:</label>
      <input type="number" id="quantity" name="quantity" value="1" min="1" class="quantity-input">

      <button type="submit" class="submit-button">Pay</button>
    </form>


  </div>

</body>
</html>
