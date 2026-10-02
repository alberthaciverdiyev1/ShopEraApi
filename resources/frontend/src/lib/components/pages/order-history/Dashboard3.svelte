<script lang="ts">
	import { translate } from '$lib/i18n';
</script>

<!-- Dashboard Section Start -->
<!-- Dashboard Section Start -->
<!-- Dashboard Section Start -->
<div class="dashboard-section section-padding fix">
<div class="container">
<div class="row">
<div class="col-xl-3">
    <div class="dashboard-navigation-sidebar">
        <h3>Navigation</h3>
        <div>
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist"
                aria-orientation="vertical">
                <button class="nav-link" id="v-pills-dashboard-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-dashboard" type="button" role="tab"
                    aria-controls="v-pills-dashboard" aria-selected="true"> <i
                        class="fa-sharp fa-solid fa-grid-2"></i>Dashboard</button>

                <button class="nav-link active" id="v-pills-order-history-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-order-history" type="button" role="tab"
                    aria-controls="v-pills-order-history" aria-selected="false"> <i
                        class="fa-solid fa-sync"></i>Order History</button>

                <button class="nav-link" id="v-pills-order-details-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-order-details" type="button" role="tab"
                    aria-controls="v-pills-order-details" aria-selected="false"><i
                        class="fa-solid fa-list"></i>Order Details</button>

                <button class="nav-link" id="v-pills-wishlist-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-wishlist" type="button" role="tab"
                    aria-controls="v-pills-wishlist" aria-selected="false"><i
                        class="fa-light fa-heart"></i>Wishlist</button>

                <button class="nav-link" id="v-pills-settings-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-settings" type="button" role="tab"
                    aria-controls="v-pills-settings" aria-selected="false"><i
                        class="fa-regular fa-gear"></i>Settings</button>

                <button class="nav-link" id="v-pills-logout-tab" data-bs-toggle="pill"
                    data-bs-target="#v-pills-logout" type="button" role="tab"
                    aria-controls="v-pills-logout" aria-selected="false"><i
                        class="fa-solid fa-sign-out-alt"></i>Log Out</button>
            </div>
        </div>
    </div>
</div>
<div class="col-xl-9">

    <div class="tab-content" id="v-pills-tabContent">
        <div class="tab-pane fade" id="v-pills-dashboard" role="tabpanel"
            aria-labelledby="v-pills-dashboard-tab" tabindex="0">
            <div class="dashboard-wrapper">
                <div class="dashboard-top">
                    <div class="row">
                        <div class="col-xl-7">
                            <div class="dashboard-profile">
                                <div class="thumb"><img
                                        src="/assets/images/dashboard/dashboard-profileThumb.jpg"
                                        alt="thumb"></div>
                                <h3>Dianne Russell</h3>
                                <p>Customer</p>
                                <a href="#">Edit Profile</a>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <div class="dashboard-profile-info">
                                <h6>Delivery Address</h6>
                                <h5>Dainne Russell</h5>
                                <a href="#" class="address">4140 Parker Rd. Allentown, New Mexico
                                    31134</a>
                                <a href="mailto:abcd@gmail.com"
                                    class="email">dainne.ressell@gmail.com</a>
                                <a href="tel:234343423" class="phone">(671) 555-0110</a>
                                <button class="edit">Edit Address</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="order-history">
                    <div class="header">
                        <h2>Recent Order History</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ORDER ID</th>
                                <th>DATE</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#738</td>
                                <td>8 Sep, 2024</td>
                                <td>$135.00 (5 Products)</td>
                                <td><span class="status processing">Processing</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#703</td>
                                <td>24 May, 2024</td>
                                <td>$25.00 (1 Product)</td>
                                <td><span class="status on-the-way">On the way</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#130</td>
                                <td>22 Oct, 2024</td>
                                <td>$250.00 (4 Products)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#561</td>
                                <td>1 Feb, 2024</td>
                                <td>$35.00 (1 Product)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#536</td>
                                <td>21 Sep, 2024</td>
                                <td>$578.00 (13 Products)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#492</td>
                                <td>22 Oct, 2024</td>
                                <td>$345.00 (7 Products)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show active" id="v-pills-order-history" role="tabpanel"
            aria-labelledby="v-pills-order-history-tab" tabindex="0">
            <div class="order-history2">
                <div class="header">
                    <h2>Order History</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ORDER ID</th>
                            <th>DATE</th>
                            <th>TOTAL</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#3933</td>
                            <td>4 April, 2024</td>
                            <td>$135.00 (5 Products)</td>
                            <td><span class="status processing">Processing</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#5045</td>
                            <td>27 Mar, 2024</td>
                            <td>$25.00 (1 Product)</td>
                            <td><span class="status on-the-way">On the way</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#5028</td>
                            <td>20 Mar, 2024</td>
                            <td>$250.00 (4 Products)</td>
                            <td><span class="status completed">Completed</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#4600</td>
                            <td>19 Mar, 2024</td>
                            <td>$35.00 (1 Product)</td>
                            <td><span class="status completed">Completed</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#3933</td>
                            <td>4 April, 2024</td>
                            <td>$135.00 (5 Products)</td>
                            <td><span class="status processing">Processing</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#5045</td>
                            <td>27 Mar, 2024</td>
                            <td>$25.00 (1 Product)</td>
                            <td><span class="status on-the-way">On the way</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#5028</td>
                            <td>20 Mar, 2024</td>
                            <td>$250.00 (4 Products)</td>
                            <td><span class="status completed">Completed</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#4600</td>
                            <td>19 Mar, 2024</td>
                            <td>$35.00 (1 Product)</td>
                            <td><span class="status completed">Completed</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#3933</td>
                            <td>4 April, 2024</td>
                            <td>$135.00 (5 Products)</td>
                            <td><span class="status processing">Processing</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#5045</td>
                            <td>27 Mar, 2024</td>
                            <td>$25.00 (1 Product)</td>
                            <td><span class="status on-the-way">On the way</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#5028</td>
                            <td>20 Mar, 2024</td>
                            <td>$250.00 (4 Products)</td>
                            <td><span class="status completed">Completed</span> <a href="#">View
                                    Details</a></td>
                        </tr>
                        <tr>
                            <td>#4600</td>
                            <td>19 Mar, 2024</td>
                            <td>$35.00 (1 Product)</td>
                            <td><span class="status completed">Completed</span> <a href="#">View
                                    Details</a></td>
                        </tr>
<!-- Add more rows as necessary -->
                        <!-- Add more rows as necessary -->
                    </tbody>
                </table>
                <div class="pagination">
                    <a href="#" class="prev"><i class="fa-solid fa-chevron-left"></i></a>
                    <a href="#" class="page active">01</a>
                    <a href="#" class="page">02</a>
                    <a href="#" class="page">03</a>
                    <a href="#" class="page">04</a>
                    <a href="#" class="next"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="v-pills-order-details" role="tabpanel"
            aria-labelledby="v-pills-order-details-tab" tabindex="0">
            <div class="order-details">
                <div class="header">
                    <h2>Order Details <span>{$translate('• April 24, 2021 • 3 Products')}</span></h2>
                    <a href="#" class="back-to-list">Back to List</a>
                </div>
                <div class="details-wrapper">
                    <div class="address-section">
                        <div class="box">
                            <h4>Delivery Address</h4>
                            <div class="content">
                                <h5>Dainne Russell</h5>
                                <p>4140 Parker Rd. Allentown, New Mexico 31134</p>
                                <p>Email: <a
                                        href="mailto:dainne.russell@gmail.com">dainne.russell@gmail.com</a>
                                </p>
                                <p>Phone: <a href="tel:435346543r"> {$translate('(671) 555–0110')}</a></p>
                            </div> 
                        </div>
                        <div class="box">
                            <h4>Shipping Address</h4>
                            <div class="content">
                                <h5>Dainne Russell</h5>
                                <p>4140 Parker Rd. Allentown, New Mexico 31134</p>
                                <p>Email: <a
                                        href="mailto:dainne.russell@gmail.com">dainne.russell@gmail.com</a>
                                </p>
                                <p>Phone: <a href="tel:435346543r"> {$translate('(671) 555–0110')}</a></p>
                            </div> 
                        </div>
                    </div>
                    <div class="summary-section">
                        <h5>Order Summary</h5>
                        <p><strong>Order ID:</strong> #4152</p>
                        <p><strong>Payment Method:</strong> Paypal</p>
                        <p><strong>Subtotal:</strong> $365.00</p>
                        <p><strong>Discount:</strong> 20%</p>
                        <p><strong>Shipping:</strong> Free</p>
                        <p><strong>Total:</strong> <span class="total-amount">$84.00</span></p>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="step completed">
                        <i class="fa-solid fa-check"></i>
                        <span>Order Received</span>
                    </div>
                    <div class="step active">
                        <span class="circle">02</span>
                        <span>Processing</span>
                    </div>
                    <div class="step">
                        <span class="circle">03</span>
                        <span>On the Way</span>
                    </div>
                    <div class="step">
                        <span class="circle">04</span>
                        <span>Delivered</span>
                    </div>
                </div>
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>PRICE</th>
                                <th>QUANTITY</th>
                                <th>SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <img src="/assets/images/dashboard/dashboard-order-product1.png" alt="Product">
                                    High-Neck puff jacket
                                </td>
                                <td>$14.00</td>
                                <td>x5</td>
                                <td>$70.00</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="/assets/images/dashboard/dashboard-order-product2.png" alt="Product">
                                    flowy dress that often
                                </td>
                                <td>$14.00</td>
                                <td>x2</td>
                                <td>$28.00</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="/assets/images/dashboard/dashboard-order-product3.png" alt="Product">
                                    close-fitting dress
                                </td>
                                <td>$26.70</td>
                                <td>x10</td>
                                <td>$267.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="v-pills-wishlist" role="tabpanel"
            aria-labelledby="v-pills-wishlist-tab" tabindex="0">
<!-- Wishlist Section Start -->
            <!-- Wishlist Section Start -->
            <div class="wishlist-wrapper fix bg-white">
                <div class="container">
                    <form action="#" class="woocommerce-cart-form">
                        <table class="wishlist_table">
                            <thead>
                                <tr>
                                    <th class="cart-col-image">Product</th>
                                    <th class="cart-col-price">Price</th>
                                    <th class="cart-col-total">Sub total</th>
                                    <th class="cart-col-stock">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_1.jpg"
                                                alt="Image"></a>
                                        shorter dress above
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>30.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>20.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock">In stock</a>
                                    </td>
                                </tr>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_2.jpg"
                                                alt="Image"></a>
                                        close-fitting dress
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>50.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>60.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock_out">out of stock</a>
                                    </td>
                                </tr>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_3.jpg"
                                                alt="Image"></a>
                                        flowy dress that often
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>70.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>30.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock">In stock</a>
                                    </td>
                                </tr>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_4.jpg"
                                                alt="Image"></a>
                                        Tight and form
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>10.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>80.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock_out">out of stock</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="v-pills-settings" role="tabpanel"
            aria-labelledby="v-pills-settings-tab" tabindex="0">
            <div class="container">
<!-- Account Settings -->
                <!-- Account Settings -->
                <div class="account-settings">
                    <h4 class="section-title">Account Settings</h4>
                    <div class="form-wrapper">
                        <div class="row">
                            <div class="col-md-8">
                                <form>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="firstName" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="firstName"
                                                placeholder="Dianne">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lastName" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="lastName"
                                                placeholder="Russell">
                                        </div>
                                        <div class="col-md-12">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email"
                                                placeholder="dianne.russell@gmail.com">
                                        </div>
                                        <div class="col-md-12">
                                            <label for="phoneNumber" class="form-label">Phone
                                                Number</label>
                                            <input type="tel" class="form-control" id="phoneNumber"
                                                placeholder="(603) 805-0123">
                                        </div>
                                    </div>
                                    <button type="submit" class="theme-btn mt-3">Save Changes</button>
                                </form>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="profile-image-wrapper">
                                    <img src="/assets/images/dashboard/dashboard-settings-profile.png"
                                        alt="Profile" class="img-fluid rounded-circle mb-3">
                                    <button class="theme-btn">Choose Image</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Delivery Address -->
                <div class="delivery-address mt-4">
                    <h4 class="section-title">Delivery Address</h4>
                    <div class="form-wrapper">
                        <div class="row mb-4">
                            <form class="row g-3">
                                <div class="col-md-6">
                                    <label for="deliveryFirstNameHist" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="deliveryFirstNameHist"
                                        placeholder="Dianne">
                                </div>
                                <div class="col-md-6">
                                    <label for="deliveryLastNameHist" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="deliveryLastNameHist"
                                        placeholder="Dianne">
                                </div>
                                <div class="col-md-6">
                                    <label for="companyName" class="form-label">Company Name
                                        (optional)</label>
                                    <input type="text" class="form-control" id="companyName"
                                        placeholder="Zakirsoft">
                                </div>
                                <div class="col-md-6">
                                    <label for="streetAddress" class="form-label">Street Address</label>
                                    <input type="text" class="form-control" id="streetAddress"
                                        placeholder="4140 Parker">
                                </div>
                                <div class="col-md-4">
                                    <label for="country" class="form-label">Country/Region</label>
                                    <select id="country" class="form-select">
                                        <option selected>United States</option>
                                        <option>Canada</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="state" class="form-label">State</label>
                                    <select id="state" class="form-select">
                                        <option selected>Washington DC</option>
                                        <option>California</option>
                                        <option>New York</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="zipCode" class="form-label">Zip Code</label>
                                    <input type="text" class="form-control" id="zipCode"
                                        placeholder="20033">
                                </div>
                                <div class="col-md-6">
                                    <label for="deliveryEmailHist" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="deliveryEmailHist"
                                        placeholder="dianne.russell@gmail.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="deliveryPhoneHist" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="deliveryPhoneHist"
                                        placeholder="(603) 805-0123">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="theme-btn mt-3">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
<!-- Change Password -->
                <!-- Change Password -->
                <div class="change-password mt-4">
                    <h4 class="section-title">Change Password</h4>
                    <div class="form-wrapper">
                        <form class="row g-3">
                            <div class="col-md-4">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword"
                                    placeholder="Password">
                            </div>
                            <div class="col-md-4">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword"
                                    placeholder="Password">
                            </div>
                            <div class="col-md-4">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword"
                                    placeholder="Password">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="theme-btn mt-3">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="v-pills-logout" role="tabpanel"
            aria-labelledby="v-pills-logout-tab" tabindex="0"> 
        </div>
    </div>  
</div>
</div>
</div>
</div>
