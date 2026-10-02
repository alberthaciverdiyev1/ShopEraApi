<script lang="ts">
	import { translate } from '$lib/i18n';
</script>

<!-- Order Tracking Section Start -->
<!-- Order Tracking Section Start -->
 <div class="order-tracking-section section-padding fix">
<div class="container">
<div class="order-tracking-form">
<p>{$translate('To track your order please enter your Order ID in the box below and press the “Track” button. This was given to your receipt and in the confirmation email you should have received')}</p>
<form class="row g-4">
    <div class="col-12">
        <label for="inputOrderid" class="form-label">Order ID</label>
        <input type="number" class="form-control" placeholder="Found In Your order confirmation email." id="inputOrderid">
    </div>  
    <div class="col-12">
      <label for="inputEmail4" class="form-label">Email address</label>
      <input type="email" class="form-control" placeholder="Email used during checkout" id="inputEmail4">
    </div>  
    <div class="col-12">
      <button type="submit" class="theme-btn style6">Track</button>
    </div>
  </form> 
</div> 
</div>
 </div>
